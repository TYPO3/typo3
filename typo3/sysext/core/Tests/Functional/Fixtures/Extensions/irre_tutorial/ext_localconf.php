<?php

defined('TYPO3_MODE') or die();

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
