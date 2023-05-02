<?php

declare(strict_types=1);

use TYPO3\CMS\Extbase\Utility\ExtensionUtility;
use TYPO3Tests\IrreTutorial\Controller\QueueController;

defined('TYPO3') or die();

ExtensionUtility::configurePlugin(
    'IrreTutorial',
    'Irre',
    [
        QueueController::class => 'index',
    ],
);
