<?php

declare(strict_types=1);

use ExtbaseTeam\BlogExample\Controller\BlogController;
use ExtbaseTeam\BlogExample\Controller\ContentController;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

defined('TYPO3') or die();

ExtensionUtility::configurePlugin(
    'BlogExample',
    'Blogs',
    [
        BlogController::class => 'list,testForm,testForward,testForwardTarget,testRelatedObject',
    ]
);
ExtensionUtility::configurePlugin(
    'BlogExample',
    'Content',
    [
        ContentController::class => 'list',
    ]
);
