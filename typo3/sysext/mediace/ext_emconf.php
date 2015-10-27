<?php
$EM_CONF[$_EXTKEY] = array(
    'title' => 'Media Content Element',
    'description' => 'The media functionality from TYPO3 6.2 and earlier can be found here. This extension provides ContentObjects and Content Elements.',
    'category' => 'fe',
    'author' => 'Friends of TYPO3',
    'author_email' => 'friendsof@typo3.org',
    'state' => 'stable',
    'uploadfolder' => 0,
    'createDirs' => 'uploads/media',
    'clearCacheOnLoad' => 1,
    'version' => '7.6.0',
    'constraints' => array(
        'depends' => array(
            'typo3' => '7.6.0-7.6.99',
        ),
        'conflicts' => array(),
        'suggests' => array(),
    )
);
