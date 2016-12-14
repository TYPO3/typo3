<?php
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::makeCategorizable(
    'core',
    'tt_content',
    'categories',
    [
        'position' => 'replace:categories'
    ]
);
