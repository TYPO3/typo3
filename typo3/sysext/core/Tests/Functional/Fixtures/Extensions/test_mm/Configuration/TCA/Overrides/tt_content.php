<?php

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('tt_content', [
    'group_mm_1_foreign' => [
        'label' => 'Group MM 1 Foreign',
        'description' => '',
        'config' => [
            'type' => 'select',
            'renderType' => 'selectMultipleSideBySide',
            'foreign_table' => 'tx_test_mm',
            'foreign_table_where' => 'AND {#tx_test_mm}.{#sys_language_uid} IN (-1, 0)',
            'MM' => 'group_mm_1_relations_mm',
            'MM_match_fields' => [
                'fieldname' => 'group_mm_1_foreign',
                'tablenames' => 'tt_content',
            ],
            'MM_opposite_field' => 'group_mm_1_local',
        ],
    ],
    'select_mm_1_foreign' => [
        'label' => 'Select MM 1 - Foreign side',
        'description' => 'Foreign side using opposite field to allow bidirectional editing',
        'config' => [
            'type' => 'select',
            'renderType' => 'selectMultipleSideBySide',
            'foreign_table' => 'tx_test_mm',
            'foreign_table_where' => 'AND tx_test_mm.sys_language_uid IN (-1, 0) ORDER BY tx_test_mm.title ASC',
            'MM' => 'select_mm_1_relations_mm',
            'MM_opposite_field' => 'posts',
        ],
    ],
]);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('tt_content', 'group_mm_1_foreign,select_mm_1_foreign');
