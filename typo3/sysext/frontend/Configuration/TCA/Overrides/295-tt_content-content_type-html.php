<?php

defined('TYPO3') or die();

$contentType = 'html';

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTcaSelectItem(
    'tt_content',
    'CType',
    [
        'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:CType.html',
        'description' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:CType.html.description',
        'value' => $contentType,
        'icon' => 'content-special-html',
        'group' => 'special',
    ],
);

$GLOBALS['TCA']['tt_content'] = array_replace_recursive(
    $GLOBALS['TCA']['tt_content'],
    [
        'ctrl' => [
            'typeicon_classes' => [
                $contentType => 'mimetypes-x-content-html',
            ],
        ],
        'types' => [
            $contentType => [
                'showitem' => '
                        header;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:header.ALT.html_formlabel,
                        bodytext;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:bodytext.ALT.html_formlabel,
                    --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:appearance,
                        --palette--;;frames,
                        --palette--;;appearanceLinks,
                    --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:categories,
                        categories,
                    --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended,
                ',
                'columnsOverrides' => [
                    'header' => [
                        'description' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:header.description.ALT',
                    ],
                ],
            ],
        ],
    ]
);
