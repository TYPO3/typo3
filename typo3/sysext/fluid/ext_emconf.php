<?php
$EM_CONF[$_EXTKEY] = array(
    'title' => 'Fluid Templating Engine',
    'description' => 'Fluid is a next-generation templating engine which makes the life of extension authors a lot easier!',
    'category' => 'fe',
    'author' => 'Sebastian Kurfürst, Bastian Waidelich',
    'author_email' => 'sebastian@typo3.org, bastian@typo3.org',
    'author_company' => '',
    'state' => 'stable',
    'uploadfolder' => 0,
    'createDirs' => '',
    'clearCacheOnLoad' => 0,
    'version' => '8.2.0',
    'constraints' => array(
        'depends' => array(
            'extbase' => '8.2.0-8.2.99',
        ),
        'conflicts' => array(),
        'suggests' => array(),
    ),
);
