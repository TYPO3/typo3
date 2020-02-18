<?php
defined('TYPO3_MODE') or die();

$openGraphCropConfiguration = [
    'config' => [
        'cropVariants' => [
            'default' => [
                'disabled' => true,
            ],
            'social' => [
                'title' => 'LLL:EXT:core/Resources/Private/Language/locallang_wizards.xlf:imwizard.crop_variant.social',
                'coverAreas' => [],
                'cropArea' => [
                    'x' => '0.0',
                    'y' => '0.0',
                    'width' => '1.0',
                    'height' => '1.0'
                ],
                'allowedAspectRatios' => [
                    '1.91:1' => [
                        'title' => 'LLL:EXT:core/Resources/Private/Language/locallang_wizards.xlf:imwizard.ratio.191_1',
                        'value' => 1.91 / 1
                    ],
                    'NaN' => [
                        'title' => 'LLL:EXT:core/Resources/Private/Language/locallang_wizards.xlf:imwizard.ratio.free',
                        'value' => 0.0
                    ],
                ],
                'selectedRatio' => '1.91:1',
            ],
        ],
    ],
];

$tca = [
    'palettes' => [
        'seo' => [
            'label' => 'LLL:EXT:seo/Resources/Private/Language/locallang_tca.xlf:pages.palettes.seo',
            'showitem' => 'seo_title;LLL:EXT:seo/Resources/Private/Language/locallang_tca.xlf:pages.seo_title',
        ],
        'robots' => [
            'label' => 'LLL:EXT:seo/Resources/Private/Language/locallang_tca.xlf:pages.palettes.robots',
            'showitem' => 'no_index;LLL:EXT:seo/Resources/Private/Language/locallang_tca.xlf:pages.no_index_formlabel, no_follow;LLL:EXT:seo/Resources/Private/Language/locallang_tca.xlf:pages.no_follow_formlabel',
        ],
        'canonical' => [
            'label' => 'LLL:EXT:seo/Resources/Private/Language/locallang_tca.xlf:pages.palettes.canonical',
            'showitem' => 'canonical_link',
        ],
        'opengraph' => [
            'label' => 'LLL:EXT:seo/Resources/Private/Language/locallang_tca.xlf:pages.palettes.opengraph',
            'showitem' => 'og_title, --linebreak--, og_description, --linebreak--, og_image',
        ],
        'twittercards' => [
            'label' => 'LLL:EXT:seo/Resources/Private/Language/locallang_tca.xlf:pages.palettes.twittercards',
            'showitem' => 'twitter_title, --linebreak--, twitter_description, --linebreak--, twitter_image',
        ],
    ],
    'columns' => [
        'seo_title' => [
            'exclude' => true,
            'l10n_mode' => 'prefixLangTitle',
            'label' => 'LLL:EXT:seo/Resources/Private/Language/locallang_tca.xlf:pages.seo_title',
            'config' => [
                'type' => 'input',
                'size' => 40,
                'max' => 255,
                'eval' => 'trim'
            ]
        ],
        'no_index' => [
            'exclude' => true,
            'l10n_mode' => 'exclude',
            'onChange' => 'reload',
            'label' => 'LLL:EXT:seo/Resources/Private/Language/locallang_tca.xlf:pages.no_index_formlabel',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
                'items' => [
                    [
                        '0' => '',
                        '1' => '',
                        'invertStateDisplay' => true
                    ]
                ]
            ]
        ],
        'no_follow' => [
            'exclude' => true,
            'l10n_mode' => 'exclude',
            'label' => 'LLL:EXT:seo/Resources/Private/Language/locallang_tca.xlf:pages.no_follow_formlabel',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
                'items' => [
                    [
                        '0' => '',
                        '1' => '',
                        'invertStateDisplay' => true
                    ]
                ]
            ]
        ],
        'canonical_link' => [
            'exclude' => true,
            'label' => 'LLL:EXT:seo/Resources/Private/Language/locallang_tca.xlf:pages.canonical_link',
            'displayCond' => 'FIELD:no_index:=:0',
            'config' => [
                'type' => 'input',
                'renderType' => 'inputLink',
                'size' => 50,
                'max' => 1024,
                'eval' => 'trim',
                'fieldControl' => [
                    'linkPopup' => [
                        'options' => [
                            'title' => 'LLL:EXT:seo/Resources/Private/Language/locallang_tca.xlf:pages.canonical_link',
                            'blindLinkFields' => 'class,target,title',
                            'blindLinkOptions' => 'mail,folder,file'
                        ],
                    ],
                ],
                'softref' => 'typolink'
            ]
        ],
        'og_title' => [
            'exclude' => true,
            'l10n_mode' => 'prefixLangTitle',
            'label' => 'LLL:EXT:seo/Resources/Private/Language/locallang_tca.xlf:pages.og_title',
            'config' => [
                'type' => 'input',
                'size' => 40,
                'max' => 255,
                'eval' => 'trim'
            ]
        ],
        'og_description' => [
            'exclude' => true,
            'l10n_mode' => 'prefixLangTitle',
            'label' => 'LLL:EXT:seo/Resources/Private/Language/locallang_tca.xlf:pages.og_description',
            'config' => [
                'type' => 'text',
                'cols' => 40,
                'rows' => 3
            ]
        ],
        'og_image' => [
            'exclude' => true,
            'label' => 'LLL:EXT:seo/Resources/Private/Language/locallang_tca.xlf:pages.og_image',
            'config' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getFileFieldTCAConfig(
                'og_image',
                [
                    // Use the imageoverlayPalette instead of the basicoverlayPalette
                    'overrideChildTca' => [
                        'types' => [
                            '0' => [
                                'showitem' => '
                                    --palette--;;imageoverlayPalette,
                                    --palette--;;filePalette'
                            ],
                            \TYPO3\CMS\Core\Resource\File::FILETYPE_IMAGE => [
                                'showitem' => '
                                    --palette--;;imageoverlayPalette,
                                    --palette--;;filePalette'
                            ]
                        ],
                        'columns' => [
                            'crop' => $openGraphCropConfiguration
                        ]
                    ],
                    'behaviour' => [
                        'allowLanguageSynchronization' => true
                    ]
                ],
                $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext']
            )
        ],
        'twitter_title' => [
            'exclude' => true,
            'l10n_mode' => 'prefixLangTitle',
            'label' => 'LLL:EXT:seo/Resources/Private/Language/locallang_tca.xlf:pages.twitter_title',
            'config' => [
                'type' => 'input',
                'size' => 40,
                'max' => 255,
                'eval' => 'trim'
            ]
        ],
        'twitter_description' => [
            'exclude' => true,
            'l10n_mode' => 'prefixLangTitle',
            'label' => 'LLL:EXT:seo/Resources/Private/Language/locallang_tca.xlf:pages.twitter_description',
            'config' => [
                'type' => 'text',
                'cols' => 40,
                'rows' => 3
            ]
        ],
        'twitter_image' => [
            'exclude' => true,
            'label' => 'LLL:EXT:seo/Resources/Private/Language/locallang_tca.xlf:pages.twitter_image',
            'config' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getFileFieldTCAConfig(
                'twitter_image',
                [
                    // Use the imageoverlayPalette instead of the basicoverlayPalette
                    'overrideChildTca' => [
                        'types' => [
                            '0' => [
                                'showitem' => '
                                    --palette--;;imageoverlayPalette,
                                    --palette--;;filePalette'
                            ],
                            \TYPO3\CMS\Core\Resource\File::FILETYPE_IMAGE => [
                                'showitem' => '
                                    --palette--;;imageoverlayPalette,
                                    --palette--;;filePalette'
                            ]
                        ],
                        'columns' => [
                            'crop' => $openGraphCropConfiguration
                        ]
                    ],
                    'behaviour' => [
                        'allowLanguageSynchronization' => true
                    ]
                ],
                $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext']
            )
        ],
    ],
];

$GLOBALS['TCA']['pages'] = array_replace_recursive($GLOBALS['TCA']['pages'], $tca);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
    'pages',
    '
    --div--;LLL:EXT:seo/Resources/Private/Language/locallang_tca.xlf:pages.tabs.seo,
        --palette--;;seo,
        --palette--;;robots,
        --palette--;;canonical,
    --div--;LLL:EXT:seo/Resources/Private/Language/locallang_tca.xlf:pages.tabs.socialmedia,
        --palette--;;opengraph,
        --palette--;;twittercards',
    (string)\TYPO3\CMS\Frontend\Page\PageRepository::DOKTYPE_DEFAULT,
    'after:title'
);
