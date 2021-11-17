<?php

declare(strict_types=1);

use OliverHader\IrreTutorial\Controller\QueueController;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

defined('TYPO3') or die();

ExtensionUtility::configurePlugin(
    'IrreTutorial',
    'Irre',
    [
        QueueController::class => 'index',
    ],
);
