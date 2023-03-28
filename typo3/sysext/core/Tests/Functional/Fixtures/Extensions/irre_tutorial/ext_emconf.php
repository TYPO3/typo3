<?php

declare(strict_types=1);

$EM_CONF[$_EXTKEY] = [
    'title' => 'Fixture extension for functional tests for Inline Relational Record Editing IRRE',
    'description' => 'based on irre_tutorial extension created by Oliver Hader, see https://forge.typo3.org/projects/extension-irre_tutorial',
    'category' => 'example',
    'version' => '12.4.0',
    'state' => 'beta',
    'author' => 'Oliver Hader',
    'author_email' => 'oliver@typo3.org',
    'author_company' => '',
    'constraints' => [
        'depends' => [
            'typo3' => '12.4.0',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
