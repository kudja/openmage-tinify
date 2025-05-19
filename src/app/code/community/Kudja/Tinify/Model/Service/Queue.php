<?php

class Kudja_Tinify_Model_Service_Queue
{

    /**
     * @var int
     */
    protected $maxQueueSize;

    /**
     * @var Varien_Db_Adapter_Interface
     */
    protected $connection;

    /**
     * @var string
     */
    protected $table;

    /**
     * @var Kudja_Tinify_Helper_Data
     */
    protected $helper;

    /**
     * @var Kudja_Tinify_Model_Queue
     */
    protected $queueModel;

    /**
     * @var Kudja_Tinify_Model_Service_Converter
     */
    protected $converter;

    /**
     * @var string
     */
    protected $baseDir;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->helper = Mage::helper('tinify/data');
        $this->queueModel = Mage::getSingleton('tinify/queue');
        $this->converter = Mage::getSingleton('tinify/service_converter');

        $this->baseDir = Mage::getBaseDir();

        $this->maxQueueSize = $this->helper->getMaxQueueSize();

        $resource = Mage::getSingleton('core/resource');
        $this->connection = $resource->getConnection('core_write');

        $this->table = $this->queueModel->getResource()->getMainTable();
    }

    /**
     * @return void
     */
    public function processQueue(): void
    {
        foreach (Mage::app()->getStores() as $store) {
            $this->processQueueForStore((int)$store->getId());
        }
    }

    /**
     * @param $storeId
     *
     * @return void
     */
    protected function processQueueForStore(int $storeId): void
    {
        if (!$this->helper->isEnabled($storeId)) {
            return;
        }

        $method = $this->helper->getConversionMethod($storeId);
        $cwebp  = $this->helper->getCwebpCommand($storeId);
        $apiKey = $this->helper->getApiKey($storeId);
        $limit  = $this->helper->getConversionLimit($storeId);

        if (!$method || ($method === 'tinify_api' && !$apiKey) || ($method === 'cwebp' && !$cwebp)) {
            Mage::log("Skip store $storeId: method/config not set", Zend_Log::INFO, 'tinify.log');
            return;
        }

        $items = $this->queueModel->getPendingItems($storeId, $limit);
        foreach ($items as $item) {
            $relativePath = $item->getPath();

            $success = $this->converter->convert($relativePath, $method, $apiKey, $cwebp);

            $this->updateItemStatus(
                $relativePath,
                $success ? Kudja_Tinify_Model_Queue::STATUS_CONVERTED : Kudja_Tinify_Model_Queue::STATUS_ERROR
            );
        }
    }

    /**
     * @param array $paths
     *
     * @return $this
     * @throws Mage_Core_Model_Store_Exception
     * @throws Mage_Core_Exception
     */
    public function batchAddImages(array $paths)
    {
        if (empty($paths)) {
            return $this;
        }
        if ($this->queueModel->getPendingCount() >= $this->maxQueueSize) {
            return $this;
        }

        $paths = array_unique($paths);
        $hashes = array_map('md5', $paths);

        $existing = Mage::getResourceModel('tinify/queue_collection')
                        ->addFieldToFilter('hash', ['in' => $hashes])
                        ->getColumnValues('path');

        $toInsert = array_diff($paths, $existing);
        if (empty($toInsert)) {
            return $this;
        }

        $storeId  = Mage::app()->getStore()->getId();
        $columns  = ['path', 'hash', 'status', 'store_id'];

        $rows = [];
        $bind = [];

        $i = 0;
        foreach ($toInsert as $path) {
            $path = trim($path);
            if (!$path) {
                continue;
            }

            $rows[] = "(:path_{$i}, :hash_{$i}, " . Kudja_Tinify_Model_Queue::STATUS_PENDING . ", $storeId)";
            $bind["path_{$i}"] = $path;
            $bind["hash_{$i}"] = md5($path);

            $i++;
        }

        if (empty($rows)) {
            return $this;
        }

        $sql = sprintf(
            'INSERT IGNORE INTO %s (%s) VALUES %s',
            $this->table,
            implode(',', $columns),
            implode(',', $rows)
        );

        $this->connection->query($sql, $bind);

        return $this;
    }

    /**
     * @param string $path
     * @param int $status
     * @return void
     */
    public function updateItemStatus(string $path, int $status): void
    {
        $this->connection->update(
            $this->table,
            ['status' => $status],
            ['hash = ?' => md5($path)]
        );
    }

    /**
     * @return void
     */
    public function cleanupConverted(): void
    {
        $this->connection->delete(
            $this->table,
            'status = ' . Kudja_Tinify_Model_Queue::STATUS_CONVERTED
        );
    }


}
