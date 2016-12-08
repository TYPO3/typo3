<?php

/**
 * Definitions of routes
 */
return [
    // Register RTE browse links wizard
    'rteckeditor_wizard_browse_links' => [
        'path' => '/rte/wizard/browselinks',
        'target' => \TYPO3\CMS\RteCKEditor\Controller\BrowseLinksController::class . '::mainAction'
    ],
];
