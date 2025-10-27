<?php

defined('TYPO3') or die();

$openGraphCropConfiguration = [
    'config' => [
        'cropVariants' => [
            'default' => [
                'disabled' => true,
            ],
            'social' => [
                'title' => 'core.wizards:imwizard.crop_variant.social',
                'coverAreas' => [],
                'cropArea' => [
                    'x' => '0.0',
                    'y' => '0.0',
                    'width' => '1.0',
                    'height' => '1.0',
                ],
                'allowedAspectRatios' => [
                    '1.91:1' => [
                        'title' => 'core.wizards:imwizard.ratio.191_1',
                        'value' => 1200 / 630,
                    ],
                    'NaN' => [
                        'title' => 'core.wizards:imwizard.ratio.free',
                        'value' => 0.0,
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
            'label' => 'core.form.palettes:seo',
            'showitem' => 'seo_title',
        ],
        'robots' => [
            'label' => 'core.form.palettes:robots',
            'showitem' => 'no_index, no_follow',
        ],
        'canonical' => [
            'label' => 'core.form.palettes:canonical',
            'showitem' => 'canonical_link',
        ],
        'sitemap' => [
            'label' => 'core.form.palettes:sitemap',
            'showitem' => 'sitemap_changefreq, sitemap_priority',
        ],
        'opengraph' => [
            'label' => 'core.form.palettes:opengraph',
            'showitem' => 'og_title, --linebreak--, og_description, --linebreak--, og_image',
        ],
        'twittercards' => [
            'label' => 'core.form.palettes:twittercards',
            'showitem' => 'twitter_title, --linebreak--, twitter_description, --linebreak--, twitter_image, --linebreak--, twitter_card',
        ],
    ],
    'columns' => [
        'seo_title' => [
            'exclude' => true,
            'l10n_mode' => 'prefixLangTitle',
            'label' => 'seo.db:pages.seo_title',
            'config' => [
                'type' => 'input',
                'size' => 40,
                'max' => 255,
                'eval' => 'trim',
            ],
        ],
        'no_index' => [
            'exclude' => true,
            'l10n_mode' => 'exclude',
            'onChange' => 'reload',
            'label' => 'seo.db:pages.no_index_formlabel',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
                'items' => [
                    [
                        'label' => '',
                        'invertStateDisplay' => true,
                    ],
                ],
            ],
        ],
        'no_follow' => [
            'exclude' => true,
            'l10n_mode' => 'exclude',
            'label' => 'seo.db:pages.no_follow_formlabel',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
                'items' => [
                    [
                        'label' => '',
                        'invertStateDisplay' => true,
                    ],
                ],
            ],
        ],
        'sitemap_changefreq' => [
            'exclude' => true,
            'label' => 'seo.db:pages.sitemap_changefreq',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['label' => 'seo.db:pages.sitemap_changefreq.none', 'value' => ''],
                    ['label' => 'seo.db:pages.sitemap_changefreq.always', 'value' => 'always'],
                    ['label' => 'seo.db:pages.sitemap_changefreq.hourly', 'value' => 'hourly'],
                    ['label' => 'seo.db:pages.sitemap_changefreq.daily', 'value' => 'daily'],
                    ['label' => 'seo.db:pages.sitemap_changefreq.weekly', 'value' => 'weekly'],
                    ['label' => 'seo.db:pages.sitemap_changefreq.monthly', 'value' => 'monthly'],
                    ['label' => 'seo.db:pages.sitemap_changefreq.yearly', 'value' => 'yearly'],
                    ['label' => 'seo.db:pages.sitemap_changefreq.never', 'value' => 'never'],
                ],
                'dbFieldLength' => 10,
            ],
        ],
        'sitemap_priority' => [
            'exclude' => true,
            'label' => 'seo.db:pages.sitemap_priority',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'default' => '0.5',
                'items' => [
                    ['label' => '0.0', 'value' => '0.0'],
                    ['label' => '0.1', 'value' => '0.1'],
                    ['label' => '0.2', 'value' => '0.2'],
                    ['label' => '0.3', 'value' => '0.3'],
                    ['label' => '0.4', 'value' => '0.4'],
                    ['label' => '0.5', 'value' => '0.5'],
                    ['label' => '0.6', 'value' => '0.6'],
                    ['label' => '0.7', 'value' => '0.7'],
                    ['label' => '0.8', 'value' => '0.8'],
                    ['label' => '0.9', 'value' => '0.9'],
                    ['label' => '1.0', 'value' => '1.0'],
                ],
            ],
        ],
        'canonical_link' => [
            'exclude' => true,
            'label' => 'seo.db:pages.canonical_link',
            'description' => 'seo.db:pages.canonical_link.description',
            'displayCond' => 'FIELD:no_index:=:0',
            'config' => [
                'type' => 'link',
                'allowedTypes' => ['page', 'url', 'record'],
                'size' => 50,
                'appearance' => [
                    'browserTitle' => 'seo.db:pages.canonical_link',
                    'allowedOptions' => ['params', 'rel'],
                ],
            ],
        ],
        'og_title' => [
            'exclude' => true,
            'l10n_mode' => 'prefixLangTitle',
            'label' => 'seo.db:pages.og_title',
            'config' => [
                'type' => 'input',
                'size' => 40,
                'max' => 255,
                'eval' => 'trim',
            ],
        ],
        'og_description' => [
            'exclude' => true,
            'l10n_mode' => 'prefixLangTitle',
            'label' => 'seo.db:pages.og_description',
            'config' => [
                'type' => 'text',
                'cols' => 40,
                'rows' => 3,
            ],
        ],
        'og_image' => [
            'exclude' => true,
            'label' => 'seo.db:pages.og_image',
            'config' => [
                'type' => 'file',
                'allowed' => 'common-image-types',
                'behaviour' => [
                    'allowLanguageSynchronization' => true,
                ],
                'overrideChildTca' => [
                    'columns' => [
                        'crop' => $openGraphCropConfiguration,
                    ],
                ],
            ],
        ],
        'twitter_title' => [
            'exclude' => true,
            'l10n_mode' => 'prefixLangTitle',
            'label' => 'seo.db:pages.twitter_title',
            'config' => [
                'type' => 'input',
                'size' => 40,
                'max' => 255,
                'eval' => 'trim',
            ],
        ],
        'twitter_description' => [
            'exclude' => true,
            'l10n_mode' => 'prefixLangTitle',
            'label' => 'seo.db:pages.twitter_description',
            'config' => [
                'type' => 'text',
                'cols' => 40,
                'rows' => 3,
            ],
        ],
        'twitter_image' => [
            'exclude' => true,
            'label' => 'seo.db:pages.twitter_image',
            'config' => [
                'type' => 'file',
                'allowed' => 'common-image-types',
                'behaviour' => [
                    'allowLanguageSynchronization' => true,
                ],
                'overrideChildTca' => [
                    'columns' => [
                        'crop' => $openGraphCropConfiguration,
                    ],
                ],
            ],
        ],
        'twitter_card' => [
            'exclude' => true,
            'label' => 'seo.db:pages.twitter_card',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'default' => '',
                'items' => [
                    ['label' => '', 'value' => ''],
                    ['label' => 'seo.db:pages.twitter_card.summary', 'value' => 'summary'],
                    ['label' => 'seo.db:pages.twitter_card.summary_large_image', 'value' => 'summary_large_image'],
                ],
                'dbFieldLength' => 255,
            ],
        ],
    ],
];

$GLOBALS['TCA']['pages'] = array_replace_recursive($GLOBALS['TCA']['pages'], $tca);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
    'pages',
    '
    --div--;core.form.tabs:seo,
        --palette--;;seo,
        --palette--;;robots,
        --palette--;;canonical,
        --palette--;;sitemap,
    --div--;core.form.tabs:socialmedia,
        --palette--;;opengraph,
        --palette--;;twittercards',
    (string)\TYPO3\CMS\Core\Domain\Repository\PageRepository::DOKTYPE_DEFAULT,
    'after:title'
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addFieldsToPalette('pages', 'seo', '--linebreak--, description', 'after:seo_title');
