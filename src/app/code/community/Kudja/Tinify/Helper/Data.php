<?php

class Kudja_Tinify_Helper_Data extends Mage_Core_Helper_Abstract
{

    /**
     * @return array
     */
    public function getAllowedTags(): array
    {
        $tags = Mage::getStoreConfig('tinify/general/allowed_tags');
        return array_map('trim', explode(',', $tags ?? ''));
    }

    /**
     * @return array
     */
    public function getAllowedAttributes(): array
    {
        $attributes = Mage::getStoreConfig('tinify/general/allowed_attributes');
        return array_map('trim', explode(',', $attributes ?? ''));
    }

}
