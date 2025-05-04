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

        // TODO: Check ajax

        if (!Mage::getStoreConfigFlag('tinify/general/enabled')) {
            return;
        }

        $response = $observer->getEvent()->getResponse();
        $html = $response->getBody();

        /** @var Kudja_Tinify_Model_Processor_Webp $processor */
        $processor = Mage::getSingleton('tinify/processor_webp');
        $html = $processor->process($html);

        $response->setBody($html);
    }

}
