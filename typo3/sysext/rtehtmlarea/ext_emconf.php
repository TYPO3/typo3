<?php
$EM_CONF[$_EXTKEY] = array(
    'title' => 'htmlArea RTE',
    'description' => 'Rich Text Editor.',
    'category' => 'be',
    'state' => 'stable',
    'uploadfolder' => 1,
    'createDirs' => '',
    'clearCacheOnLoad' => 0,
    'author' => 'Stanislas Rolland',
    'author_email' => 'typo3(arobas)sjbr.ca',
    'author_company' => 'SJBR',
    'version' => '8.2.0',
    'constraints' => array(
        'depends' => array(
            'typo3' => '8.2.0-8.2.99',
        ),
        'conflicts' => array(),
        'suggests' => array(
            'rtehtmlarea_api_manual' => '',
            'setup' => '',
        ),
    ),
);
