<?php

class Kudja_Tinify_Test_TestHelpers_TestCase extends \PHPUnit\Framework\TestCase
{
    /**
     * @return Varien_Db_Adapter_Interface
     */
    protected function getConnection()
    {
        /** @var Mage_Core_Model_Resource $resource */
        $resource = Mage::getSingleton('core/resource');
        return $resource->getConnection('default_write');
    }

    /**
     *
     */
    protected function setUp(): void
    {
        $this->getConnection()->beginTransaction();
    }

    /**
     *
     */
    protected function tearDown(): void
    {
        $this->getConnection()->rollBack();
    }

}
