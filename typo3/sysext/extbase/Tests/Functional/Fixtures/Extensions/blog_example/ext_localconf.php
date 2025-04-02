<?php

declare(strict_types=1);

use TYPO3\CMS\Extbase\Utility\ExtensionUtility;
use TYPO3Tests\BlogExample\Controller\BlogController;
use TYPO3Tests\BlogExample\Controller\BlogPostEditingController;
use TYPO3Tests\BlogExample\Controller\ContentController;

defined('TYPO3') or die();

ExtensionUtility::configurePlugin(
    'BlogExample',
    'Blogs',
    [
        BlogController::class => ['list', 'details', 'testSingle', 'testForm', 'testForward', 'testForwardTarget', 'testRelatedObject'],
    ],
    [],
    ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT
);
ExtensionUtility::configurePlugin(
    'BlogExample',
    'Content',
    [
        ContentController::class => ['list'],
    ],
    [],
    ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT
);
ExtensionUtility::configurePlugin(
    'BlogExample',
    'BlogPostEditing',
    [
        BlogPostEditingController::class => ['list', 'view', 'edit', 'persist', 'new', 'create'],
    ],
    [
        BlogPostEditingController::class => ['list', 'view', 'edit', 'persist', 'new', 'create'],
    ],
    ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT
);
