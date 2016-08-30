<?php
$EM_CONF[$_EXTKEY] = [
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
    'constraints' => [
        'depends' => [
            'typo3' => '7.6.0-7.6.99',
        ],
        'conflicts' => [
            'rte_conf' => '',
            'tkr_rteanchors' => '',
            'ad_rtepasteplain' => '',
            'rtehtmlarea_definitionlist' => '',
        ],
        'suggests' => [
            'rtehtmlarea_api_manual' => '',
            'setup' => '',
        ],
    ],
];
