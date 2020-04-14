<?php

declare(strict_types=1);
defined('TYPO3_MODE') or die();

$GLOBALS['SiteConfiguration']['site']['columns']['tx_b_a'] = [
    'label' => 'a',
    'config' => [
        'type' => 'input',
        'size' => 25,
        'max' => 255,
    ]
];

$GLOBALS['SiteConfiguration']['site']['types']['0']['showitem'] .= ',tx_b_a';

$GLOBALS['SiteConfiguration']['site']['columns']['tx_a_a']['description'] = 'Awesome description by extension b';
