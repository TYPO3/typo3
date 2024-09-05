<?php

defined('TYPO3') or die();

$contentType = 'textpic';

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTcaSelectItem(
    'tt_content',
    'CType',
    [
        'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:CType.textpic',
        'description' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:CType.textpic.description',
        'value' => $contentType,
        'icon' => 'content-textpic',
        'group' => 'default',
    ],
);

$GLOBALS['TCA']['tt_content'] = array_replace_recursive(
    $GLOBALS['TCA']['tt_content'],
    [
        'ctrl' => [
            'typeicon_classes' => [
                $contentType => 'mimetypes-x-content-text-picture',
            ],
        ],
        'types' => [
            $contentType => [
                'showitem' => '
                        --palette--;;headers,
                        bodytext;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:bodytext_formlabel,
                    --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:images,
                        image,
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
