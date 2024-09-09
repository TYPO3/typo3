<?php

defined('TYPO3') or die();

$contentType = 'menu_sitemap';

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTcaSelectItem(
    'tt_content',
    'CType',
    [
        'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:CType.menu_sitemap',
        'description' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:CType.menu_sitemap.description',
        'value' => $contentType,
        'icon' => 'content-menu-sitemap',
        'group' => 'menu',
    ],
);

$GLOBALS['TCA']['tt_content'] = array_replace_recursive(
    $GLOBALS['TCA']['tt_content'],
    [
        'ctrl' => [
            'typeicon_classes' => [
                $contentType => 'content-menu-sitemap',
            ],
        ],
        'types' => [
            $contentType => [
                'showitem' => '
                        --palette--;;headers,
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
