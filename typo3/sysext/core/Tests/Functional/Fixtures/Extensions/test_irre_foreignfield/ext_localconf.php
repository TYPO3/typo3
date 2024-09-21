<?php

declare(strict_types=1);

use TYPO3\CMS\Extbase\Utility\ExtensionUtility;
use TYPO3Tests\TestIrreForeignfield\Controller\ContentController;
use TYPO3Tests\TestIrreForeignfield\Controller\QueueController;

defined('TYPO3') or die();

ExtensionUtility::configurePlugin(
    'TestIrreForeignfield',
    'Test',
    [
        QueueController::class => ['index'],
        ContentController::class => ['list', 'show', 'new', 'create', 'edit', 'update', 'delete'],
    ],
    [
        ContentController::class => ['create', 'update', 'delete'],
    ],
    ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT
);
