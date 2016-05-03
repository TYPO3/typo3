<?php
$EM_CONF[$_EXTKEY] = array(
    'title' => 'System language labels',
    'description' => 'Contains all the core language labels in a set of files mostly of the "locallang" format. This extension is always required in a TYPO3 install.',
    'category' => 'be',
    'state' => 'stable',
    'uploadfolder' => 0,
    'createDirs' => '',
    'clearCacheOnLoad' => 0,
    'author' => 'Kasper Skaarhoj',
    'author_email' => 'kasperYYYY@typo3.com',
    'author_company' => 'Curby Soft Multimedia',
    'version' => '8.2.0',
    'constraints' => array(
        'depends' => array(
            'typo3' => '8.2.0-8.2.99',
            'extensionmanager' => '8.2.0-8.2.99',
        ),
        'conflicts' => array(),
        'suggests' => array(),
    ),
);
