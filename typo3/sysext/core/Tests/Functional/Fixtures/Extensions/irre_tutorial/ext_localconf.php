<?php

declare(strict_types=1);

defined('TYPO3') or die();

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
    'IrreTutorial',
    'Irre',
    [
        \OliverHader\IrreTutorial\Controller\QueueController::class => 'index',
        \OliverHader\IrreTutorial\Controller\ContentController::class => 'list, show, new, create, edit, update, delete'
    ],
    [
        \OliverHader\IrreTutorial\Controller\ContentController::class => 'create, update, delete'
    ]
);
