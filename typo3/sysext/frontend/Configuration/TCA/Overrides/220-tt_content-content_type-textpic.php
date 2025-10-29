<?php

defined('TYPO3') or die();

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addRecordType(
    [
        'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:CType.textpic',
        'description' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:CType.textpic.description',
        'value' => 'textpic',
        'icon' => 'mimetypes-x-content-text-picture',
    ],
    '
        --palette--;;headers,
        bodytext,
    --div--;core.form.tabs:images,
        image,
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
    ],
);
