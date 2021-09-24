<?php

declare(strict_types=1);

use OliverHader\IrreTutorial\Controller\ContentController;
use OliverHader\IrreTutorial\Controller\QueueController;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

defined('TYPO3') or die();

ExtensionUtility::configurePlugin(
    'IrreTutorial',
    'Irre',
    [
        QueueController::class => 'index',
        ContentController::class => 'list, show, new, create, edit, update, delete',
    ],
    [
        ContentController::class => 'create, update, delete',
    ]
);
