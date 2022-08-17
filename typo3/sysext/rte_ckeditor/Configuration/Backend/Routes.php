<?php

use TYPO3\CMS\RteCKEditor\Controller\BrowseLinksController;
use TYPO3\CMS\RteCKEditor\Controller\ResourceController;

/**
 * Definitions of routes for rte-ckeditor.
 */

return [
    // Register RTE browse links wizard
    'rteckeditor_wizard_browse_links' => [
        'path' => '/rte/wizard/browselinks',
        'target' => BrowseLinksController::class . '::mainAction',
    ],
    'rteckeditor_resource_stylesheet' => [
        'path' => '/rte/resource/stylesheet',
        'target' => ResourceController::class . '::stylesheetAction',
    ],
];
