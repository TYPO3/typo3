<?php

defined('TYPO3') or die();

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns(
    'tt_content',
    [
        'bullets_type' => [
            'exclude' => true,
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:bullets_type',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:bullets_type.0', 'value' => 0],
                    ['label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:bullets_type.1', 'value' => 1],
                    ['label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:bullets_type.2', 'value' => 2],
                ],
                'default' => 0,
            ],
        ],
    ]
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addRecordType(
    [
        'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:CType.bullets',
        'description' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:CType.bullets.description',
        'value' => 'bullets',
        'icon' => 'mimetypes-x-content-list-bullets',
        'group' => 'lists',
    ],
    '
    --palette--;;headers,
        bullets_type,
        bodytext;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:bodytext.ALT.bulletlist_formlabel,
    --div--;core.form.tabs:appearance,
        --palette--;;frames,
        --palette--;;appearanceLinks,
    --div--;core.form.tabs:categories,
        categories',
    [
        'columnsOverrides' => [
            'bodytext' => [
                'config' => [
                    'wrap' => 'off',
                ],
            ],
        ],
    ]
);
