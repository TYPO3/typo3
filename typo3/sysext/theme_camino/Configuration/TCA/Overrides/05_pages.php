<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3') or die();

ExtensionManagementUtility::addTCAcolumns('pages', [
    'tx_themecamino_logo' => [
        'label' => 'theme_camino.backend_fields:pages.tx_themecamino_logo',
        'description' => 'theme_camino.backend_fields:pages.tx_themecamino_logo.description',
        'config' => [
            'type' => 'file',
            'allowed' => ['common-image-types'],
            'behaviour' => [
                'allowLanguageSynchronization' => true,
            ],
        ],
    ],
]);

ExtensionManagementUtility::addFieldsToPalette(
    'pages',
    'layout',
    '--linebreak--, tx_themecamino_logo',
    'after:layout'
);
