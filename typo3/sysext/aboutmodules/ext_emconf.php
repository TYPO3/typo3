<?php
$EM_CONF[$_EXTKEY] = array(
    'title' => 'Help>About Modules',
    'description' => 'Shows an overview of the installed and available modules including description and links.',
    'category' => 'module',
    'state' => 'stable',
    'uploadfolder' => 0,
    'createDirs' => '',
    'clearCacheOnLoad' => 0,
    'author' => 'Kasper Skaarhoj',
    'author_email' => 'kasperYYYY@typo3.com',
    'author_company' => 'Curby Soft Multimedia',
    'version' => '8.0.0',
    'constraints' => array(
        'depends' => array(
            'typo3' => '8.0.0-8.0.99',
        ),
        'conflicts' => array(),
        'suggests' => array(),
    ),
);
