<?php

defined('TYPO3') or die();

$contentType = 'textmedia';

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTcaSelectItem(
    'tt_content',
    'CType',
    [
        'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:CType.textmedia',
        'description' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:CType.textmedia.description',
        'value' => $contentType,
        'icon' => 'content-textmedia',
        'group' => 'default',
    ],
);

$GLOBALS['TCA']['tt_content'] = array_replace_recursive(
    $GLOBALS['TCA']['tt_content'],
    [
        'ctrl' => [
            'typeicon_classes' => [
                $contentType => 'mimetypes-x-content-text-media',
            ],
        ],
        'types' => [
            $contentType => [
                'showitem' => '
                        --palette--;;headers,
                        bodytext;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:bodytext_formlabel,
                    --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:media,
                        assets,
                        --palette--;;mediaAdjustments,
                        --palette--;;gallerySettings,
                        --palette--;;imagelinks,
                    --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:appearance,
                        --palette--;;frames,
                        --palette--;;appearanceLinks,
                    --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:categories,
                        categories,
                    --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended,
                ',
                'columnsOverrides' => [
                    'bodytext' => [
                        'config' => [
                            'enableRichtext' => true,
                        ],
                    ],
                ],
            ],
        ],
    ]
);
