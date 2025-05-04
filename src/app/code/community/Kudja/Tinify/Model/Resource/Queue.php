<?php

class Kudja_Tinify_Model_Resource_Queue extends Mage_Core_Model_Resource_Db_Abstract
{

    /**
     * Initialize the resource model
     */
    public function _construct()
    {
        $this->_init('tinify/queue', 'entity_id');
    }

}
