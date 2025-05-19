<?php

class Kudja_Tinify_Model_Queue extends Mage_Core_Model_Abstract
{

    public const STATUS_PENDING   = 0;
    public const STATUS_CONVERTED = 1;
    public const STATUS_ERROR     = -1;

    protected function _construct()
    {
        $this->_init('tinify/queue');
    }

    /**
     * @param int|null $storeId
     *
     * @return int
     * @throws Mage_Core_Exception
     */
    public function getPendingCount(int $storeId = null): int
    {
        $collection = $this->getCollection()
                           ->addFieldToFilter('status', self::STATUS_PENDING);
        if ($storeId) {
            $collection->addFieldToFilter('store_id', $storeId);
        }

        return $collection->getSize();
    }

    /**
     * @param int|null $storeId
     * @param int      $limit
     *
     * @return Kudja_Tinify_Model_Resource_Queue_Collection
     * @throws Mage_Core_Exception
     */
    public function getPendingItems(int $storeId = null, int $limit = 10): Kudja_Tinify_Model_Resource_Queue_Collection
    {
        $collection =  $this->getCollection()
                            ->addFieldToFilter('status', self::STATUS_PENDING)
                            ->setPageSize($limit)
                            ->setCurPage(1);
        if ($storeId) {
            $collection->addFieldToFilter('store_id', $storeId);
        }

        return $collection;
    }

}
