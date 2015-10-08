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
    'version' => '7.6.0',
    'constraints' => array(
        'depends' => array(
            'typo3' => '7.6.0-7.6.99',
        ),
        'conflicts' => array(
            'rte_conf' => '',
            'tkr_rteanchors' => '',
            'ad_rtepasteplain' => '',
            'rtehtmlarea_definitionlist' => '',
        ),
        'suggests' => array(
            'rtehtmlarea_api_manual' => '',
            'setup' => '',
        ),
    ),
);
