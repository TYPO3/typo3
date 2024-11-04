<?php

declare(strict_types=1);

use TYPO3\CMS\Extbase\Utility\ExtensionUtility;
use TYPO3Tests\TestTranslate\Controller\TranslateController;

defined('TYPO3') or die();

ExtensionUtility::configurePlugin(
    'TestTranslate',
    'Test',
    [
        TranslateController::class => ['translate'],
    ],
    [],
);
