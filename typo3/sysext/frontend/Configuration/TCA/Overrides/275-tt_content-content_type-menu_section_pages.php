<?php

defined('TYPO3') or die();

$contentType = 'menu_section_pages';

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTcaSelectItem(
    'tt_content',
    'CType',
    [
        'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:CType.menu_section_pages',
        'description' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:CType.menu_section_pages.description',
        'value' => $contentType,
        'icon' => 'content-menu-section',
        'group' => 'menu',
    ],
);

$GLOBALS['TCA']['tt_content'] = array_replace_recursive(
    $GLOBALS['TCA']['tt_content'],
    [
        'ctrl' => [
            'typeicon_classes' => [
                $contentType => 'content-menu-section',
            ],
        ],
        'types' => [
            $contentType => [
                'showitem' => '
                        --palette--;;headers,
                        pages;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:pages.ALT.menu_formlabel,
                    --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:appearance,
                        --palette--;;frames,
                        --palette--;;appearanceLinks,
                    --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:categories,
                        categories,
                    --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended,
                ',
            ],
        ],
    ]
);
