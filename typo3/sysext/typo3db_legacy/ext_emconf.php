<?php
$EM_CONF[$_EXTKEY] = [
    'title' => 'TYPO3_DB compatibility layer for TYPO3 v9.x',
    'description' => 'Provides $GLOBALS[\'TYPO3_DB\'] as backwards-compatibility with legacy functionality for extensions that haven\'t fully migrated to doctrine yet.',
    'category' => 'be',
    'state' => 'stable',
    'uploadfolder' => 0,
    'createDirs' => '',
    'clearCacheOnLoad' => 0,
    'author' => 'TYPO3 CMS Team',
    'author_email' => '',
    'author_company' => '',
    'version' => '9.0.0',
    'constraints' => [
        'depends' => [
            'typo3' => '9.0.0-9.6.99',
            'backend' => '9.0.0-9.6.99',
        ],
    ],
];
