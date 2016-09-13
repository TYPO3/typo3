<?php
defined('TYPO3_MODE') or die();

$fields = [
    'select_key' => [
        'exclude' => true,
        'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.code',
        'config' => [
            'type' => 'input',
            'size' => 50,
            'max' => 80,
            'eval' => 'trim'
        ]
    ],
];

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('tt_content', $fields);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('tt_content', 'select_key;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:select_key_formlabel', 'list', 'after:list_type');

// Register "old" FE plugin and hide layout, select_key and pages fields in BE
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(
    ['LLL:EXT:indexed_search/Resources/Private/Language/locallang_main.xlf:mod_indexed_search', 'indexed_search'],
    'list_type',
    'indexed_search'
);
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist']['indexed_search'] = 'layout,pages';
