<?php

declare(strict_types=1);

defined('TYPO3') or die();

// Register own renderType for tx_styleguide_elements_basic user_1 as user1Element
$GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][1536238257] = [
    'nodeName' => 'user1Element',
    'priority' => 40,
    'class' => \TYPO3\CMS\Styleguide\Form\Element\User1Element::class,
];

$GLOBALS['TYPO3_CONF_VARS']['RTE']['Presets']['RTE-Styleguide'] = 'EXT:styleguide/Configuration/RTE/RTE-Styleguide.yaml';

// Register some custom permission options shown in BE group access lists
$GLOBALS['TYPO3_CONF_VARS']['BE']['customPermOptions']['tx_styleguide_custom'] = [
    'header' => 'Custom styleguide permissions',
    'items' => [
        'key1' => [
            'Option 1',
            // Icon has been registered above
            'tcarecords-tx_styleguide_forms-default',
            'Description 1',
        ],
        'key2' => [
            'Option 2',
        ],
    ],
];
