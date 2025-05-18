<?php

class Kudja_Tinify_Model_Cron
{
    /**
     * @var ?string
     */
    protected ?string $baseDir = null;

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
        if (!Mage::getStoreConfigFlag('tinify/general/enabled', $storeId)) {
            return;
        }

        $limit = (int)Mage::getStoreConfig('tinify/general/batch_size');

        $collection = Mage::getResourceModel('tinify/queue_collection')
                          ->addFieldToFilter('store_id', $storeId)
                          ->addFieldToFilter('status', 0)
                          ->setPageSize($limit)
                          ->setCurPage(1);

        $conversionMethod = Mage::getStoreConfig('tinify/general/conversion_method');
        if ($conversionMethod === 'cwebp') {
            $cwebpCommand = Mage::getStoreConfig('tinify/general/cwebp_cmd');
            if (!$cmd = trim($cwebpCommand)) {
                Mage::log("Cwebp command not set [Store ID: $storeId]", Zend_Log::ERR, 'tinify.log');
                return;
            }
        } elseif ($conversionMethod === 'tinify_api') {
            $apiKey = Mage::getStoreConfig('tinify/general/api_key');
            if (!$apiKey) {
                Mage::log("Tinify API key not set [Store ID: $storeId]", Zend_Log::ERR, 'tinify.log');
                return;
            }
        }

        foreach ($collection as $item) {
            $this->processItem($item, $conversionMethod);
        }

        $this->cleanupQueueSuccess();
    }

    /**
     * @param Kudja_Tinify_Model_Queue $item
     * @param string                   $method
     *
     * @return void
     */
    protected function processItem(Kudja_Tinify_Model_Queue $item, string $method): void
    {
        if (!$this->baseDir) {
            $this->baseDir = Mage::getBaseDir();
        }
        $src = $this->baseDir . DS . ltrim($item->getPath(), '/');

        if (!is_readable($src)) {
            Mage::log("File not found: $src", Zend_Log::ERR, 'tinify.log');
            $item->setStatus(-1)->save();
            return;
        }

        $target = $src . '.webp';

        try {
            if ($method === 'cwebp') {
                $cmd = Mage::getStoreConfig('tinify/general/cwebp_cmd');
                exec(
                    str_replace(
                        ['{src}', '{target}'],
                        [escapeshellarg($src), escapeshellarg($target)],
                        $cmd
                    )
                );
            } elseif (
                $method === 'tinify_api'
                && $api = Mage::getStoreConfig('tinify/general/api_key')
            ) {
                \Tinify\setKey($api);
                $source = \Tinify\fromFile($src);
                $source->toFile($src);
                $converted = $source->convert(["type" => "image/webp"]);
                $converted->toFile($target);
            }

            if (!file_exists($target)) {
                Mage::log("Failed to convert: $src", Zend_Log::ERR, 'tinify.log');
                $item->setStatus(-1)->save();
                return;
            }


            if (filesize($target) >= filesize($src) ) {
                unlink($target);
                Mage::log("Converted filesize > original: $src",  Zend_Log::WARN, 'tinify.log');
                $item->setStatus(-1)->save();
                return;
            }

            $item->setStatus(1)->save();

        } catch (Exception $e) {
            Mage::log(
                "Error processing item [Path: {$item->getPath()} ID: {$item->getId()}]: {$e->getMessage()}",
                Zend_Log::ERR,
                'tinify.log'
            );
            $item->setStatus(-1)->save();
        }
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
