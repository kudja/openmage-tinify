<?php

class Kudja_Tinify_Block_Settings extends Mage_Core_Block_Template
{

    /**
     * @return bool
     */
    public function isEnabled(): bool
    {
        return Mage::getStoreConfigFlag('tinify/general/enabled');
    }

    /**
     * @return array
     */
    public function getAllowedTags(): array
    {
        return Mage::helper('tinify/data')->getAllowedTags();
    }

    /**
     * @return array
     */
    public function getAllowedAttributes(): array
    {
        return Mage::helper('tinify/data')->getAllowedAttributes();
    }

}
