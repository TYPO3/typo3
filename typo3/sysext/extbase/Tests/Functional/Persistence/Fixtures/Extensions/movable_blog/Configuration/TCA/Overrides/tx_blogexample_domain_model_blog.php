<?php

declare(strict_types=1);

defined('TYPO3') or die();

$GLOBALS['TCA']['tx_blogexample_domain_model_blog']['columns']['pid'] = [
    'config' => [
        'type' => 'passthrough',
    ],
];
