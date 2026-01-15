<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3') or die();

ExtensionManagementUtility::addRecordType(
    [
        'label' => 'theme_camino.backend_fields:tt_content.CType.camino_hero_text_only.label',
        'description' => 'theme_camino.backend_fields:tt_content.CType.camino_hero_text_only.description',
        'value' => 'camino_hero_text_only',
        'icon' => 'content-header',
        'group' => 'camino_hero',
    ],
    '
        --palette--;;headers,
        --palette--;;camino_linklabeliconconfig,
    ',
);
