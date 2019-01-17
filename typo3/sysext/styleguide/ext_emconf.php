<?php
$EM_CONF[$_EXTKEY] = array(
    'title' => 'TYPO3 CMS Backend Styleguide and Testing use cases',
    'description' => 'Presents most supported styles for TYPO3 backend modules. Mocks typography, tables, forms, buttons, flash messages and helpers. More at https://github.com/7elix/TYPO3.CMS.Styleguide',
    'category' => 'plugin',
    'author' => 'Felix Kopp',
    'author_email' => 'felix-source@phorax.com',
    'author_company' => 'PHORAX',
    'state' => 'stable',
    'createDirs' => '',
    'clearCacheOnLoad' => 0,
    'version' => '10.0.0',
    'constraints' => array(
        'depends' => array(
            'typo3' => '10.0.0-10.99.99',
        ),
        'conflicts' => array(
        ),
        'suggests' => array(
        ),
    ),
);
