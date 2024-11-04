<?php

use TYPO3\CMS\Extbase\Utility\ExtensionUtility;
use TYPO3Tests\ParentChildTranslation\Controller\MainController;

defined('TYPO3') or die();

ExtensionUtility::configurePlugin(
    'ParentChildTranslation',
    'ParentChildTranslation',
    [
        MainController::class => ['list'],
    ],
    // non-cacheable actions
    [
        MainController::class => ['list'],
    ],
);
