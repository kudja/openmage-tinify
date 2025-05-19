<?php

class Kudja_Tinify_Model_Cron
{

    /**
     * @return void
     */
    public function processQueue(): void
    {
        /** @var Kudja_Tinify_Model_Service_Queue $queueService */
        $queueService = Mage::getSingleton('tinify/service_queue');

        $queueService->processQueue();
        $queueService->cleanupConverted();
    }

}
