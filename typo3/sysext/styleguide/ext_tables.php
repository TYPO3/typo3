<?php

defined('TYPO3') or die();

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
