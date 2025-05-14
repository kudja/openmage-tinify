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

        $contentType = $helper->getHeader($response->getHeaders(), 'Content-Type');
        if (!$contentType) {
            if (stripos($body, '<html') !== false) {
                $contentType = 'text/html';
            } elseif (
                (str_starts_with($body, '{') && str_ends_with($body, '}'))
                || (str_starts_with($body, '[') && str_ends_with($body, ']'))
            ) {
                $contentType = 'application/json';
            } else {
                return;
            }
        }

        /** @var Kudja_Tinify_Model_Response_Processor $processor */
        $processor = Mage::getSingleton('tinify/response_processor');

        if (str_starts_with($contentType, 'text/html')) {
            $body = $processor->processHtml($body);
        } elseif (
            str_starts_with($contentType, 'application/json')
            && preg_match('~\.(jpe?g|png)(?!\.webp)~i', $body)
        ) {
            $body = $processor->processJson($body);
        }

        $processor->flushBatch();

        $response->setBody($body);
    }

}
