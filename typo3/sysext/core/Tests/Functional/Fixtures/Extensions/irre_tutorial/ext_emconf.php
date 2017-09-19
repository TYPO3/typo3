<?php
$EM_CONF[$_EXTKEY] = [
    'title' => 'Fixture extension for functional tests for Inline Relational Record Editing IRRE',
    'description' => 'based on irre_tutorial extension created by Oliver Hader, see http://forge.typo3.org/projects/extension-irre_tutorial',
    'category' => 'example',
    'version' => '7.6.24',
    'state' => 'beta',
    'uploadfolder' => 0,
    'createDirs' => '',
    'clearcacheonload' => 0,
    'author' => 'Oliver Hader',
    'author_email' => 'oliver@typo3.org',
    'author_company' => '',
    'constraints' => [
        'depends' => [
            'typo3' => '7.6.0-7.6.24',
            'workspaces' => '0.0.0-',
            'version' => '7.6.24',
        ],
        'conflicts' => [
        ],
        'suggests' => [
        ],
    ],
];
