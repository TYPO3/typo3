<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'LinkValidator',
    'description' => 'The LinkValidator checks the links in your website for validity. It can validate all kinds of links: internal, external and file links. The Scheduler is supported to run LinkValidator via cron job, including the option to send status mails, if broken links were detected.',
    'category' => 'module',
    'author' => 'TYPO3 Core Team',
    'author_email' => 'typo3cms@typo3.org',
    'author_company' => '',
    'state' => 'stable',
    'version' => '11.5.12',
    'constraints' => [
        'depends' => [
            'typo3' => '11.5.12',
            'info' => '11.5.12',
        ],
        'conflicts' => [],
        'suggests' => [
            'scheduler' => '',
        ],
    ],
];
