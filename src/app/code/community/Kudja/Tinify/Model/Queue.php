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

            $rows[] = "(:path_{$i}, :hash_{$i}, 0, {$storeId})";
            $bind['path_{$i}'] = $path;
            $bind['hash_{$i}'] = md5($path);

            $i++;
        }

        if (empty($rows)) {
            return $this;
        }

        $resource = Mage::getSingleton('core/resource');
        $write    = $resource->getConnection('core_write');
        $table    = $this->getResource()->getMainTable();

        $sql = sprintf(
            'INSERT IGNORE INTO %s (%s) VALUES %s',
            $table,
            implode(',', $columns),
            implode(',', $rows)
        );

        $write->query($sql, $bind);

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
