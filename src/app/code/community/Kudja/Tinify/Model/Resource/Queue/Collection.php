<?php

class Kudja_Tinify_Model_Resource_Queue_Collection extends Mage_Core_Model_Resource_Db_Collection_Abstract
{

    /**
     * Initialize collection
     */
    protected function _construct()
    {
        parent::_construct();
        $this->_init('tinify/queue');
    }

}
