<?php

class Kudja_Tinify_Model_Queue extends Mage_Core_Model_Abstract
{

    /**
     * Queue size limit
     *
     * @var int
     */
    protected int $maxQueueSize = 10000;

    /**
     * Internal constructor
     */
    protected function _construct()
    {
        $this->_init('tinify/queue');
        $this->maxQueueSize = Mage::getStoreConfig('tinify/general/max_queue_size');
    }

    /**
     * @param array $paths
     *
     * @return $this
     * @throws Mage_Core_Model_Store_Exception
     */
    public function batchAddImages(array $paths)
    {
        if (empty($paths)) {
            return $this;
        }
        if ($this->getPendingCount() >= $this->maxQueueSize) {
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

        $storeId = Mage::app()->getStore()->getId();

        $data = [];
        foreach ($toInsert as $path) {
            $data[] = [
                'path'     => $path,
                'hash'     => md5($path),
                'status'   => 0,
                'store_id' => $storeId
            ];
        }

        $resource        = Mage::getSingleton('core/resource');
        $writeConnection = $resource->getConnection('core_write');
        $table           = $this->getResource()->getMainTable();
        $writeConnection->insertMultiple($table, $data);

        return $this;
    }

    /**
     * @return int
     */
    protected function getPendingCount(): int
    {
        return Mage::getResourceModel('tinify/queue_collection')
                   ->addFieldToFilter('status', 0)
                   ->getSize();
    }

}
