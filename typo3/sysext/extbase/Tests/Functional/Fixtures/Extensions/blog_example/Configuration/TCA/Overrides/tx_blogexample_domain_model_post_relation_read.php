<?php

declare(strict_types=1);

defined('TYPO3') or die();

$GLOBALS['TCA']['tx_blogexample_domain_model_post']['columns']['picked_tags'] = [
    'exclude' => true,
    'label' => 'Picked tags',
    'config' => [
        'type' => 'select',
        'renderType' => 'selectMultipleSideBySide',
        'foreign_table' => 'tx_blogexample_domain_model_tag',
        'maxitems' => 5,
    ],
];
$GLOBALS['TCA']['tx_blogexample_domain_model_post']['columns']['referenced_tags'] = [
    'exclude' => true,
    'label' => 'Referenced tags',
    'config' => [
        'type' => 'group',
        'allowed' => 'tx_blogexample_domain_model_tag',
        'maxitems' => 5,
    ],
];
$GLOBALS['TCA']['tx_blogexample_domain_model_post']['columns']['pinned_comments'] = [
    'exclude' => true,
    'label' => 'Pinned comments',
    'config' => [
        'type' => 'inline',
        'foreign_table' => 'tx_blogexample_domain_model_comment',
        'foreign_field' => 'post',
        'foreign_table_field' => 'post_table',
        'foreign_match_fields' => ['post_field' => 'pinned_comments'],
        'foreign_default_sortby' => 'uid',
    ],
];
$GLOBALS['TCA']['tx_blogexample_domain_model_post']['columns']['highlighted_comments'] = [
    'exclude' => true,
    'label' => 'Highlighted comments',
    'config' => [
        'type' => 'inline',
        'foreign_table' => 'tx_blogexample_domain_model_comment',
        'foreign_field' => 'post',
        'foreign_table_field' => 'post_table',
        'foreign_match_fields' => ['post_field' => 'highlighted_comments'],
        'foreign_default_sortby' => 'uid',
    ],
];
$GLOBALS['TCA']['tx_blogexample_domain_model_post']['types']['1']['showitem'] .= ', picked_tags, referenced_tags, pinned_comments, highlighted_comments';
