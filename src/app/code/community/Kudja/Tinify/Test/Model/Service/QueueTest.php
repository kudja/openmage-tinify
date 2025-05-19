<?php

class Kudja_Tinify_Test_Model_Service_QueueTest extends Kudja_Tinify_Test_TestHelpers_TestCase
{
    public function testBatchAddImagesAddItems()
    {
        $images = ['/path/to/image1.jpg', '/path/to/image2.jpg'];

        /** @var Kudja_Tinify_Model_Service_Queue $queueService */
        $queueService = Mage::getSingleton('tinify/service_queue');
        $queueService->batchAddImages($images);

        $result = $this->getQueueImagePaths($images);

        $this->assertEquals($images, $result);
    }

    protected function getQueueImagePaths(array $images): array
    {
        $hashes = array_map('md5', $images);

        /** @var Kudja_Tinify_Model_Queue $queue */
        $queue = Mage::getModel('tinify/queue');
        return $queue->getCollection()
                     ->addFieldToFilter('status', Kudja_Tinify_Model_Queue::STATUS_PENDING)
                     ->addFieldToFilter('hash', ['in' => $hashes])
                     ->getColumnValues('path');
    }

}
