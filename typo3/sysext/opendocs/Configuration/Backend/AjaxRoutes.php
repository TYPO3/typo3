<?php

/**
 * Definitions for routes provided by EXT:opendocs
 */
return [
    // Render the opendocs toolbar item
    'opendocs_menu' => [
        'path' => '/opendocs/menu',
        'target' => \TYPO3\CMS\Opendocs\Backend\ToolbarItems\OpendocsToolbarItem::class . '::renderMenu'
    ],

    // Close a document
    'opendocs_closedoc' => [
        'path' => '/opendocs/close',
        'target' => \TYPO3\CMS\Opendocs\Backend\ToolbarItems\OpendocsToolbarItem::class . '::closeDocument'
    ],
];
