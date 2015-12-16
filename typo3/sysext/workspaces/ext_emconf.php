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
    'version' => '8.0.0',
    'constraints' => array(
        'depends' => array(
            'typo3' => '8.0.0-8.0.99',
            'version' => '8.0.0-8.0.99',
        ),
        'conflicts' => array(),
        'suggests' => array(),
    ),
);
