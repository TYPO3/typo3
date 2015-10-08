<?php
$EM_CONF[$_EXTKEY] = array(
    'title' => 'User>Open Documents',
    'description' => 'Shows opened documents by the user.',
    'category' => 'module',
    'state' => 'stable',
    'uploadfolder' => 0,
    'createDirs' => '',
    'clearCacheOnLoad' => 1,
    'author' => 'Benjamin Mack',
    'author_email' => 'mack@xnos.org',
    'author_company' => '',
    'version' => '7.6.0',
    'constraints' => array(
        'depends' => array(
            'typo3' => '7.6.0-7.6.99',
        ),
        'conflicts' => array(),
        'suggests' => array(),
    ),
);
