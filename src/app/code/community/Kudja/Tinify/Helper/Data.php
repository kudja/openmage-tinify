<?php

class Kudja_Tinify_Helper_Data extends Mage_Core_Helper_Abstract
{

    /** @var string */
    protected string $baseDir;

    /** @var string */
    protected string $baseUrl;

    /** @var string */
    protected string $scheme;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->baseDir = Mage::getBaseDir();
        $this->baseUrl = str_replace('media/', '', Mage::getBaseUrl('media'));
        $this->scheme  = Mage::app()->getRequest()->getScheme();
    }

    /**
     * @param int|null $storeId
     *
     * @return bool
     */
    public function isEnabled($storeId = null): bool
    {
        return Mage::getStoreConfigFlag('tinify/general/enabled', $storeId);
    }

    /**
     * @param int|null $storeId
     *
     * @return array
     */
    public function getAllowedTags($storeId = null): array
    {
        $tags = Mage::getStoreConfig('tinify/general/allowed_tags', $storeId);
        return array_map('trim', explode(',', $tags ?? ''));
    }

    /**
     * @param int|null $storeId
     *
     * @return array
     */
    public function getAllowedAttributes($storeId = null): array
    {
        $attributes = Mage::getStoreConfig('tinify/general/allowed_attributes', $storeId);
        return array_map('trim', explode(',', $attributes ?? ''));
    }

    /**
     * @param int|null $storeId
     *
     * @return int
     */
    public function getConversionLimit($storeId = null): int
    {
        return (int)Mage::getStoreConfig('tinify/general/conversion_limit', $storeId);
    }

    /**
     * @param int|null $storeId
     *
     * @return string
     */
    public function getConversionMethod($storeId = null): string
    {
        return Mage::getStoreConfig('tinify/general/conversion_method', $storeId);
    }

    /**
     * @param int|null $storeId
     *
     * @return string
     */
    public function getCwebpCommand($storeId = null): string
    {
        return Mage::getStoreConfig('tinify/general/cwebp_cmd', $storeId);
    }

    /**
     * @param int|null $storeId
     *
     * @return string
     */
    public function getApiKey($storeId = null): string
    {
        return Mage::getStoreConfig('tinify/general/api_key', $storeId);
    }

    /**
     * @param int|null $storeId
     *
     * @return int
     */
    public function getMaxQueueSize($storeId = null): int
    {
        return (int)Mage::getStoreConfig('tinify/general/max_queue_size', $storeId);
    }

    /**
     * @param array  $headers
     * @param string $name
     *
     * @return string|null
     */
    public function getHeader(array $headers, string $name)
    {
        $headers = array_reverse($headers);
        $name = strtolower($name);
        foreach ($headers as $header) {
            if (strtolower($header['name']) === $name) {
                return $header['value'];
            }
        }

        return null;
    }

    /**
     * Checks whether a URL is internal (belongs to the current base URL)
     *
     * @param string $url
     *
     * @return bool
     */
    public function isInternalUrl(string $url): bool
    {
        return strpos($url, 'http') !== 0 || strpos($url, $this->baseUrl) === 0;
    }

    /**
     * Converts protocol-relative URL to full URL based on current scheme
     *
     * @param string $url
     *
     * @return string
     */
    public function resolveFullUrl(string $url): string
    {
        return strpos($url, '//') === 0 ? $this->scheme . ':' . $url : $url;
    }

    /**
     * Resolves local filesystem path from relative URL path
     *
     * @param string $path
     *
     * @return string
     */
    public function getLocalFilePath(string $path): string
    {
        return $this->baseDir . DS . ltrim($path, '/');
    }

}
