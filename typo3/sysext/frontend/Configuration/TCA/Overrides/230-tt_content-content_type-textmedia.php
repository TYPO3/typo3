<?php

defined('TYPO3') or die();

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addRecordType(
    [
        'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:CType.textmedia',
        'description' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:CType.textmedia.description',
        'value' => 'textmedia',
        'icon' => 'mimetypes-x-content-text-media',
    ],
    '
        --palette--;;headers,
        bodytext;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:bodytext_formlabel,
    --div--;core.form.tabs:media,
        assets,
        --palette--;;mediaAdjustments,
        --palette--;;gallerySettings,
        --palette--;;imagelinks,
    --div--;core.form.tabs:appearance,
        --palette--;;frames,
        --palette--;;appearanceLinks,
    --div--;core.form.tabs:categories,
        categories',
    [
        'columnsOverrides' => [
            'bodytext' => [
                'config' => [
                    'enableRichtext' => true,
                ],
            ],
        ],
    ]
);
