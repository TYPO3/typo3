<?php
$EM_CONF[$_EXTKEY] = array(
    'title' => 'TYPO3 Frontend library',
    'description' => 'Classes for the frontend of TYPO3.',
    'category' => 'fe',
    'state' => 'stable',
    'uploadfolder' => 0,
    'createDirs' => '',
    'clearCacheOnLoad' => 1,
    'author' => 'Kasper Skaarhoj',
    'author_email' => 'kasperYYYY@typo3.com',
    'author_company' => '',
    'version' => '8.2.0',
    'constraints' => array(
        'depends' => array(
            'typo3' => '8.2.0-8.2.99',
        ),
        'conflicts' => array(),
        'suggests' => array(),
    ),
);
