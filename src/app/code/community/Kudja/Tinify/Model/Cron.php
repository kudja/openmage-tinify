<?php

class Kudja_Tinify_Model_Cron
{
    /**
     * @var ?string
     */
    protected ?string $baseDir = null;

    /**
     * @var Kudja_Tinify_Helper_Data
     */
    protected $helper;

    /**
     * @var Kudja_Tinify_Model_Service_Converter
     */
    protected $converter;

    public function __construct()
    {
        $this->helper = Mage::helper('tinify/data');
        $this->converter = Mage::getSingleton('tinify/service_converter');
    }

    /**
     * @return void
     */
    public function processQueue(): void
    {
        foreach (Mage::app()->getStores() as $store) {
            Mage::app()->setCurrentStore($store->getId());
            $this->processQueueForStore($store->getId());
        }
    }

    /**
     * @param $storeId
     *
     * @return void
     */
    protected function processQueueForStore($storeId): void
    {
        if (!$this->helper->isEnabled($storeId)) {
            return;
        }

        $cwebpCommand = $apiKey = null;
        $conversionMethod = $this->helper->getConversionMethod($storeId);
        if ($conversionMethod === 'cwebp') {
            if (!$cwebpCommand = $this->helper->getCwebpCommand($storeId)) {
                Mage::log("Cwebp command not set [Store ID: $storeId]", Zend_Log::ERR, 'tinify.log');
                return;
            }
        } elseif ($conversionMethod === 'tinify_api') {
            if (!$apiKey = $this->helper->getApiKey()) {
                Mage::log("Tinify API key not set [Store ID: $storeId]", Zend_Log::ERR, 'tinify.log');
                return;
            }
        } else {
            Mage::log("Invalid conversion method [Store ID: $storeId]", Zend_Log::ERR, 'tinify.log');
            return;
        }

        $conversionLimit = $this->helper->getConversionLimit($storeId);

        $collection = Mage::getResourceModel('tinify/queue_collection')
                          ->addFieldToFilter('store_id', $storeId)
                          ->addFieldToFilter('status', 0)
                          ->setPageSize($conversionLimit)
                          ->setCurPage(1);

        foreach ($collection as $item) {
            $relativePath = $item->getPath();

            $success = $this->converter->convert($relativePath, $conversionMethod, $apiKey, $cwebpCommand);

            $item->setStatus($success ? 1 : -1);
            $item->save();
        }

        $this->cleanupQueueSuccess();
    }

    /**
     * @return void
     */
    protected function cleanupQueueSuccess(): void
    {
        $resource = Mage::getSingleton('core/resource');
        $writeConnection = $resource->getConnection('core_write');
        $table = Mage::getModel('tinify/queue')->getResource()->getMainTable();
        $where = $writeConnection->quoteInto('status = ?', 1);

        $writeConnection->delete($table, $where);
    }

}
