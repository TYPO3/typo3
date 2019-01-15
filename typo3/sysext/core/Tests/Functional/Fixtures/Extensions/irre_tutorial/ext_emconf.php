<?php
$EM_CONF[$_EXTKEY] = [
    'title' => 'Fixture extension for functional tests for Inline Relational Record Editing IRRE',
    'description' => 'based on irre_tutorial extension created by Oliver Hader, see http://forge.typo3.org/projects/extension-irre_tutorial',
    'category' => 'example',
    'version' => '10.0.0',
    'state' => 'beta',
    'createDirs' => '',
    'clearCacheOnLoad' => 0,
    'author' => 'Oliver Hader',
    'author_email' => 'oliver@typo3.org',
    'author_company' => '',
    'constraints' => [
        'depends' => [
            'typo3' => '10.0.0',
            'workspaces' => '10.0.0',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
