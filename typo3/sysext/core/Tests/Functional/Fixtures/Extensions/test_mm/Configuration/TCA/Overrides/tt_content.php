<?php

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('tt_content', [
    'surfing' => [
        'exclude' => true,
        'label' => 'LLL:EXT:test_mm/Resources/Private/Language/locallang_db.xlf:tt_content.surfing',
        'config' => [
            'type' => 'select',
            'renderType' => 'selectMultipleSideBySide',
            'default' => 0,
            'foreign_table' => 'tx_test_mm_surf',
            'foreign_table_where' => 'AND {#tx_test_mm_surf}.{#sys_language_uid} IN (-1, 0)',
            'maxitems' => 99999,
            'MM' => 'surf_content_mm',
            'MM_match_fields' => [
                'fieldname' => 'surfing',
                'tablenames' => 'tt_content',
            ],
            'MM_opposite_field' => 'relations',
        ],
    ],
    'surfers' => [
        'label' => 'Surfers',
        'config' => [
            'type' => 'select',
            'renderType' => 'selectMultipleSideBySide',
            'foreign_table' => 'tx_test_mm_surf',
            'foreign_table_where' => 'AND tx_test_mm_surf.sys_language_uid IN (-1, 0) ORDER BY tx_test_mm_surf.title ASC',
            'MM' => 'surf_content_surfers_mm',
            'MM_opposite_field' => 'posts',
            'behaviour' => [
                'allowLanguageSynchronization' => true,
            ],
        ],
    ],
]);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('tt_content', 'surfing,surfers');
