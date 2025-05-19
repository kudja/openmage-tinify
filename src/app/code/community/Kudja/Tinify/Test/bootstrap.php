<?php

if (strpos(__DIR__, '/.modman/')) {
    define("MAGENTO_ROOT", dirname(__DIR__, 9));
} elseif (strpos(__DIR__, '/vendor/')) {
    define("MAGENTO_ROOT", dirname(__DIR__, 9));
} else {
    define("MAGENTO_ROOT", dirname(__DIR__, 6));
}

require_once MAGENTO_ROOT . '/app/Mage.php';
Mage::app('default');

error_reporting(E_ALL);
ini_set('display_errors', 1);
