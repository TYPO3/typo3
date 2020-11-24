<?php

defined('TYPO3') or die();

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::makeCategorizable(
    'core',
    'sys_file_metadata',
    'categories',
    [
        'position' => 'replace:categories'
    ]
);
