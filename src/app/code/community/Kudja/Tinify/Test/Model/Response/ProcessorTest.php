<?php

class Kudja_Tinify_Test_Model_Response_ProcessorTest extends Kudja_Tinify_Test_TestHelpers_TestCase
{
    /**
     * @var Kudja_Tinify_Model_Response_Processor
     */
    protected $processor;


    protected $queueTable;

    protected function setUp(): void
    {
        $this->processor = Mage::getSingleton('tinify/response_processor');
        $this->queueTable = Mage::getSingleton('core/resource')->getTableName('tinify_queue');
        parent::setUp();
    }

    protected function loadTest(string $name)
    {
        $filePath = __DIR__ . '/data/' . $name;
        if (!file_exists($filePath)) {
            throw new \RuntimeException('File not found: ' . $filePath);
        }
        $response = file_get_contents($filePath);

        return str_replace('https://local.host/', Mage::getBaseUrl(), $response);
    }

    protected function getTestResult(string $name)
    {
        $filePath = __DIR__ . '/data/' . $name . '.php';
        if (!file_exists($filePath)) {
            throw new \RuntimeException('File not found: ' . $filePath);
        }

        return require $filePath;
    }

    protected function getNewImagePaths(int $lastId)
    {
        $select = $this->getConnection()->select()
                                        ->from($this->queueTable, ['path'])
                                        ->where('entity_id > ?', $lastId);

        return $this->getConnection()->fetchCol($select);
    }

    protected function getQueueLastId()
    {
        return (int)$this->getConnection()
                         ->fetchOne("SELECT MAX(entity_id) FROM {$this->queueTable}");
    }

    public function testResponseProcessorFindsImagesAndAddsToQueue()
    {
        # Add at least one image so we have lastId
        /** @var Kudja_Tinify_Model_Queue $queue */
        $queue = Mage::getModel('tinify/queue');
        $queue->setPath('/media/catalog/product/test-start.jpg')
              ->setHash(md5('/media/catalog/product/test-start.jpg'))
              ->setStatus(Kudja_Tinify_Model_Queue::STATUS_PENDING)
              ->setStoreId(Mage::app()->getStore()->getId())
              ->save();

        $htmlTests = glob(__DIR__ . '/data/*.{html,json}', GLOB_BRACE);
        if (empty($htmlTests)) {
            throw new \RuntimeException('No test files found in the data directory.');
        }
        foreach ($htmlTests as $testName) {
            $testName = basename($testName);

            $lastId = $this->getQueueLastId();

            $testData = $this->loadTest($testName);
            $testResult = $this->getTestResult($testName);

            if (strpos($testName, '.json') !== false) {
                $this->processor->processJson($testData);
            } else {
                $this->processor->processHtml($testData);
            }
            $this->processor->flushBatch();

            $addedImages = $this->getNewImagePaths($lastId);

            $this->assertEquals($testResult, $addedImages, "Failed asserting queue entries for: $testName");
        }
    }

}
