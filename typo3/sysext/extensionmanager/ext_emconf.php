<?php
$EM_CONF[$_EXTKEY] = array(
    'title' => 'Extension Manager',
    'description' => 'TYPO3 Extension Manager',
    'category' => 'module',
    'state' => 'stable',
    'uploadfolder' => 0,
    'createDirs' => '',
    'clearCacheOnLoad' => 0,
    'author' => '',
    'author_email' => '',
    'author_company' => '',
    'version' => '8.1.0',
    'constraints' => array(
        'depends' => array(
            'typo3' => '8.1.0-8.1.99',
        ),
        'conflicts' => array(),
        'suggests' => array(),
    ),
);
