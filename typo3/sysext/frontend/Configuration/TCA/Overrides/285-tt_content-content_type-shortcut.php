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
    --div--;core.form.tabs:appearance,
        --palette--;;frames,
        --palette--;;appearanceLinks,
    --div--;core.form.tabs:categories,
        categories',
    [
        'columnsOverrides' => [
            'header' => [
                'description' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:header.description.ALT',
            ],
        ],
    ]
);
