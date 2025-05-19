<?php

use PHPUnit\Framework\TestCase;

class Kudja_Tinify_Test_Helper_DataTest extends TestCase
{

    public function testGetWebpIfExistsReturnsFalseForExternalUrl()
    {
        $helper = Mage::helper('tinify/data');
        $url = 'https://external.com/image.jpg';

        $result = $helper->getWebpIfExists($url);

        $this->assertNull($result);
    }

}
