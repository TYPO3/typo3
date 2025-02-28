<?php

defined('TYPO3') or die();

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addRecordType(
    [
        'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:CType.shortcut',
        'description' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:CType.shortcut.description',
        'value' => 'shortcut',
        'icon' => 'mimetypes-x-content-link',
        'group' => 'special',
    ],
    '
        header;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:header.ALT.shortcut_formlabel,
        records;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:records_formlabel,
    --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:appearance,
        --palette--;;frames,
        --palette--;;appearanceLinks,
    --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:categories,
        categories',
    [
        'columnsOverrides' => [
            'header' => [
                'description' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:header.description.ALT',
            ],
        ],
    ]
);
