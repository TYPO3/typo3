<?php

declare(strict_types=1);

use TYPO3\CMS\Extbase\Utility\ExtensionUtility;
use TYPO3\TestIrreForeignfield\Controller\ContentController;
use TYPO3\TestIrreForeignfield\Controller\QueueController;

defined('TYPO3') or die();

ExtensionUtility::configurePlugin(
    'TestIrreForeignfield',
    'Test',
    [
        QueueController::class => 'index',
        ContentController::class => 'list, show, new, create, edit, update, delete',
    ],
    [
        ContentController::class => 'create, update, delete',
    ]
);
