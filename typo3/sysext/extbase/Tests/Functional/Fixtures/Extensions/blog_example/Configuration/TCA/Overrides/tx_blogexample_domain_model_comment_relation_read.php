<?php

declare(strict_types=1);

defined('TYPO3') or die();

$GLOBALS['TCA']['tx_blogexample_domain_model_comment']['columns']['post_table'] = [
    'config' => [
        'type' => 'input',
        'default' => '',
    ],
];
$GLOBALS['TCA']['tx_blogexample_domain_model_comment']['columns']['post_field'] = [
    'config' => [
        'type' => 'input',
        'default' => '',
    ],
];
