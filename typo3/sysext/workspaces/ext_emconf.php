<?php
$EM_CONF[$_EXTKEY] = array(
    'title' => 'Workspaces Management',
    'description' => 'Adds workspaces functionality with custom stages to TYPO3.',
    'category' => 'be',
    'author' => 'Workspaces Team',
    'author_email' => '',
    'author_company' => '',
    'state' => 'stable',
    'uploadfolder' => 0,
    'createDirs' => '',
    'clearCacheOnLoad' => 1,
    'version' => '8.2.0',
    'constraints' => array(
        'depends' => array(
            'typo3' => '8.2.0-8.2.99',
            'version' => '8.2.0-8.2.99',
        ),
        'conflicts' => array(),
        'suggests' => array(),
    ),
);
