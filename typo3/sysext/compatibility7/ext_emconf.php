<?php
$EM_CONF[$_EXTKEY] = array(
    'title' => 'Compatibility Mode for TYPO3 CMS 7.x',
    'description' => 'Provides an additional backwards-compatibility layer with legacy functionality for sites that haven\'t fully migrated to v8 yet.',
    'category' => 'be',
    'state' => 'stable',
    'uploadfolder' => 0,
    'createDirs' => '',
    'clearCacheOnLoad' => 0,
    'author' => 'TYPO3 CMS Team',
    'author_email' => '',
    'author_company' => '',
    'version' => '8.2.0',
    'constraints' => array(
        'depends' => array(
            'typo3' => '8.2.0-8.2.99',
        ),
        'conflicts' => array(
            'compatibility6' => '0.0.0',
        ),
        'suggests' => array(
            'indexed_search'
        ),
    ),
);
