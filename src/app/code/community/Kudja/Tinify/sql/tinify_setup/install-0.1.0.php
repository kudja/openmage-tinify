<?php
/** @var Mage_Core_Model_Resource_Setup $installer */
$installer = $this;
$installer->startSetup();

$table = $installer->getConnection()
    ->newTable($installer->getTable('tinify/queue'))
    ->addColumn(
        'entity_id',
        Varien_Db_Ddl_Table::TYPE_INTEGER,
        null,
        [
            'identity' => true,
            'unsigned' => true,
            'nullable' => false,
            'primary'  => true,
        ],
        'Entity ID')
    ->addColumn(
        'path',
        Varien_Db_Ddl_Table::TYPE_VARCHAR,
        2000,
        [
            'nullable' => false,
        ],
        'Image Path')
    ->addColumn(
        'hash',
        Varien_Db_Ddl_Table::TYPE_CHAR,
        32,
        [
           'nullable' => false,
        ],
        'Image Path Hash')
    ->addColumn(
        'store_id',
        Varien_Db_Ddl_Table::TYPE_SMALLINT,
        null,
        [
            'unsigned' => true,
            'nullable' => false,
        ],
        'Store ID')
    ->addColumn(
        'status',
        Varien_Db_Ddl_Table::TYPE_TINYINT,
        null,
        [
            'unsigned' => false,
            'nullable' => false,
            'default'  => '0',
        ],
        'Processing Status')
    ->addIndex(
        $installer->getIdxName(
            'tinify/queue',
            ['hash'],
            Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE
        ),
        ['hash'],
        ['type' => Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE]
    )->setComment('Images Optimization Queue');

$installer->getConnection()->createTable($table);

$installer->endSetup();
