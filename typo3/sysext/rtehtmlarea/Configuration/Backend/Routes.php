<?php

/**
 * Definitions of routes
 */
return [
    // Register RTE browse links wizard
    'rtehtmlarea_wizard_browse_links' => [
        'path' => '/rte/wizard/link',
        'target' => \TYPO3\CMS\Rtehtmlarea\Controller\BrowseLinksController::class . '::mainAction'
    ],
    // Register RTE select image wizard
    'rtehtmlarea_wizard_select_image' => [
        'path' => '/rte/wizard/image',
        'target' => \TYPO3\CMS\Rtehtmlarea\Controller\SelectImageController::class . '::mainAction'
    ],
    // Register RTE user elements wizard
    'rtehtmlarea_wizard_user_elements' => [
        'path' => '/rte/wizard/userelements',
        'target' => \TYPO3\CMS\Rtehtmlarea\Controller\UserElementsController::class . '::mainAction'
    ],
    // Register RTE parse html wizard
    'rtehtmlarea_wizard_parse_html' => [
        'path' => '/rte/wizard/parsehtml',
        'target' => \TYPO3\CMS\Rtehtmlarea\Controller\ParseHtmlController::class . '::mainAction'
    ],
];
