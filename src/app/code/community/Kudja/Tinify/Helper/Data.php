<?php

class Kudja_Tinify_Helper_Data extends Mage_Core_Helper_Abstract
{

    /**
     * @param int|null $storeId
     *
     * @return bool
     */
    public function isEnabled(?int $storeId = null): bool
    {
        return Mage::getStoreConfigFlag('tinify/general/enabled', $storeId);
    }

    /**
     * @param int|null $storeId
     *
     * @return array
     */
    public function getAllowedTags(?int $storeId = null): array
    {
        $tags = Mage::getStoreConfig('tinify/general/allowed_tags', $storeId);
        return array_map('trim', explode(',', $tags ?? ''));
    }

    /**
     * @param int|null $storeId
     *
     * @return array
     */
    public function getAllowedAttributes(?int $storeId = null): array
    {
        $attributes = Mage::getStoreConfig('tinify/general/allowed_attributes', $storeId);
        return array_map('trim', explode(',', $attributes ?? ''));
    }

    /**
     * @param array  $headers
     * @param string $name
     *
     * @return string|null
     */
    public function getHeader(array $headers, string $name): ?string
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
}
