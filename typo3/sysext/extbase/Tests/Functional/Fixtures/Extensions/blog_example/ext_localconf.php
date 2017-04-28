<?php
defined('TYPO3_MODE') or die();

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
    'ExtbaseTeam.BlogExample',
    'Blogs',
    [
        'Blog' => 'list',
    ],
    []
);
\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
    'ExtbaseTeam.BlogExample',
    'Content',
    [
        'Content' => 'list',
    ],
    []
);
