<?php

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

$columns = [
    'foreign_table_parent_uid' => [
        'label' => 'LLL:EXT:sitepackage/Resources/Private/Language/locallang_db.xlf:tt_content.foreign_table_parent_uid',
        'exclude' => true,
        'displayCond' => 'FIELD:foreign_table_parent_uid:>:0',
        'config' => [
            'readOnly' => true,
            'type' => 'select',
            'renderType' => 'selectSingle',
            'foreign_table' => 'tt_content',
        ],
    ],
];

ExtensionManagementUtility::addTCAcolumns('tt_content', $columns);
ExtensionManagementUtility::addFieldsToPalette('tt_content', 'general', 'foreign_table_parent_uid', 'after:CType');
