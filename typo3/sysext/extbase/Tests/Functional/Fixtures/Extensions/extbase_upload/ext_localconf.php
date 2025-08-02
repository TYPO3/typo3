<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;
use TYPO3Tests\ExtbaseUpload\Controller\SingleFileUploadController;

defined('TYPO3') or die();

ExtensionUtility::configurePlugin(
    'extbase_upload',
    'Pi1',
    [
        SingleFileUploadController::class => 'list,new,create,show,edit,update',
    ],
    // non-cacheable actions
    [
        SingleFileUploadController::class => 'list,new,create,show,edit,update',
    ],
    ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT
);

ExtensionManagementUtility::addTypoScript(
    'extbase_upload',
    'setup',
    "@import 'EXT:extbase_upload/Configuration/TypoScript/setup.typoscript'"
);
