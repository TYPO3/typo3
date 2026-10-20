<?php

declare(strict_types=1);

defined('TYPO3') or die();

$GLOBALS['TCA']['tx_blogexample_domain_model_person']['columns']['avatar'] = [
    'exclude' => true,
    'label' => 'Avatar',
    'config' => [
        'type' => 'file',
        'maxitems' => 1,
    ],
];
$GLOBALS['TCA']['tx_blogexample_domain_model_person']['columns']['main_tag'] = [
    'exclude' => true,
    'label' => 'Main tag',
    'config' => [
        'type' => 'select',
        'renderType' => 'selectSingle',
        'foreign_table' => 'tx_blogexample_domain_model_tag',
        'items' => [
            ['label' => '--none--', 'value' => 0],
        ],
        'MM' => 'tx_blogexample_domain_model_tag_mm',
        'MM_match_fields' => [
            'fieldname' => 'main_tag',
            'tablenames' => 'tx_blogexample_domain_model_person',
        ],
        'MM_opposite_field' => 'items',
        'maxitems' => 1,
        'default' => 0,
    ],
];
$GLOBALS['TCA']['tx_blogexample_domain_model_person']['types']['1']['showitem'] .= ', main_tag';
$GLOBALS['TCA']['tx_blogexample_domain_model_person']['columns']['pinned_tag'] = [
    'exclude' => true,
    'label' => 'Pinned tag',
    'config' => [
        'type' => 'group',
        'allowed' => 'tx_blogexample_domain_model_tag',
        'maxitems' => 1,
    ],
];
$GLOBALS['TCA']['tx_blogexample_domain_model_person']['types']['1']['showitem'] .= ', pinned_tag';
