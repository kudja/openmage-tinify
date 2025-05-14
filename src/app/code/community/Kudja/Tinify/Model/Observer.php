<?php

class Kudja_Tinify_Model_Observer
{
    /**
     * @var bool
     */
    public static bool $pageFromCache = false;

    /**
     * @param Varien_Event_Observer $observer
     *
     * @return void
     */
    public function setFpcFlag(Varien_Event_Observer $observer): void
    {
        self::$pageFromCache = true;
    }

    /**
     * @event http_response_send_before
     * @scope frontend
     *
     * @param Varien_Event_Observer $observer
     * @return void
     */
    public function processHtml(Varien_Event_Observer $observer): void
    {
        if (self::$pageFromCache) {
            return;
        }

        $helper = Mage::helper('tinify/data');
        if (!$helper->isEnabled()) {
            return;
        }

        /** @var Mage_Core_Controller_Response_Http $response */
        $response = $observer->getEvent()->getResponse();
        $body = $response->getBody();
        if (empty($body)) {
            return;
        }

        /** @var Kudja_Tinify_Model_Response_Processor $processor */
        $processor = Mage::getSingleton('tinify/response_processor');

        $body = $processor->processHtml($body);
        $processor->flushBatch();

        $response->setBody($body);
    }

}
