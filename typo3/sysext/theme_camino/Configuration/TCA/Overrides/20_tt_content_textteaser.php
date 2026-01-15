<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3') or die();

ExtensionManagementUtility::addRecordType(
    [
        'label' => 'theme_camino.backend_fields:tt_content.CType.camino_textteaser.label',
        'description' => 'theme_camino.backend_fields:tt_content.CType.camino_textteaser.description',
        'value' => 'camino_textteaser',
        'icon' => 'content-textmedia',
        'group' => 'camino_teaser',
    ],
    '
        --palette--;;headers,
        bodytext,
        --palette--;;camino_linklabeliconconfig,
        --div--;core.form.tabs:appearance,
        --palette--;;frames
        ',
    [
        'columnsOverrides' => [
            'bodytext' => [
                'config' => [
                    'enableRichtext' => true,
                ],
            ],
        ],
    ],
);
