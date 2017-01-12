<?php

defined('TYPO3_MODE') or die();

// add pages.url_scheme
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('pages', [
    'url_scheme' => [
        'exclude' => true,
        'label' => 'LLL:EXT:compatibility7/Resources/Private/Language/locallang_tca.xlf:pages.url_scheme',
        'config' => [
            'type' => 'select',
            'renderType' => 'selectSingle',
            'items' => [
                [
                    'LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.default_value',
                    0
                ],
                [
                    'LLL:EXT:compatibility7/Resources/Private/Language/locallang_tca.xlf:pages.url_scheme.http',
                    1
                ],
                [
                    'LLL:EXT:compatibility7/Resources/Private/Language/locallang_tca.xlf:pages.url_scheme.https',
                    2
                ]
            ],
            'default' => 0
        ]
    ]
]);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addFieldsToPalette('pages', 3, 'url_scheme', 'after:cache_tags');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addFieldsToPalette('pages', 'links', '--linebreak--, url_scheme;LLL:EXT:compatibility7/Resources/Private/Language/locallang_tca.xlf:pages.url_scheme_formlabel', 'after:target');
