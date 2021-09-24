<?php

defined('TYPO3') or die();

$fields = [
    'tx_impexp_origuid' => [
        'config' => [
            'type' => 'passthrough',
        ],
    ],
];

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('sys_template', $fields);
