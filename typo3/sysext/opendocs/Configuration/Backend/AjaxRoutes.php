<?php

/**
 * Definitions for routes provided by EXT:opendocs
 */
return [
    // Render the opendocs toolbar item
    'opendocs_menu' => [
        'path' => '/opendocs/menu',
        'target' => \TYPO3\CMS\Opendocs\Controller\OpenDocumentController::class . '::renderMenu',
    ],

    // Close a document
    'opendocs_closedoc' => [
        'path' => '/opendocs/close',
        'target' => \TYPO3\CMS\Opendocs\Controller\OpenDocumentController::class . '::closeDocument',
    ],
];
