<?php
$EM_CONF[$_EXTKEY] = array(
    'title' => 'Documentation',
    'description' => 'Backend module for TYPO3 to list and show documentation of loaded extensions as well as custom documents.',
    'category' => 'be',
    'author' => 'Xavier Perseguers, Francois Suter',
    'author_email' => 'xavier@typo3.org, francois.suter@typo3.org',
    'author_company' => '',
    'state' => 'stable',
    'uploadfolder' => '0',
    'createDirs' => '',
    'clearCacheOnLoad' => 0,
    'version' => '8.2.0',
    'constraints' => array(
        'depends' => array(
            'typo3' => '8.2.0-8.2.99',
        ),
        'conflicts' => array(
        ),
        'suggests' => array(
        ),
    ),
);
