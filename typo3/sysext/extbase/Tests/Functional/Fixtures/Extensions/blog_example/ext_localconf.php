<?php
defined('TYPO3_MODE') or die();

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
    'ExtbaseTeam.' . $_EXTKEY, 'Blogs',
    [
        'Blog' => 'list',
    ],
    []
);
