<?php

use TYPO3\CMS\Recycler\Controller\RecyclerAjaxController;
use TYPO3\CMS\Recycler\Controller\RecyclerModuleController;

/**
 * Definitions for modules provided by EXT:recycler
 */
return [
    'recycler' => [
        'parent' => 'content',
        'position' => ['after' => 'content_status'],
        'access' => 'user',
        'workspaces' => 'live',
        'path' => '/module/web/recycler',
        'iconIdentifier' => 'module-recycler',
        'labels' => 'recycler.module',
        'aliases' => ['web_RecyclerRecycler'],
        'routes' => [
            '_default' => [
                'target' => RecyclerModuleController::class . '::handleRequest',
            ],
            'getTables' => [
                'target' => RecyclerAjaxController::class . '::getTablesAction',
                'methods' => ['GET'],
                'ajax' => true,
            ],
            'getDeletedRecords' => [
                'target' => RecyclerAjaxController::class . '::getDeletedRecordsAction',
                'methods' => ['GET'],
                'ajax' => true,
            ],
            'undoRecords' => [
                'target' => RecyclerAjaxController::class . '::undoRecordsAction',
                'methods' => ['POST'],
                'ajax' => true,
            ],
            'deleteRecords' => [
                'target' => RecyclerAjaxController::class . '::deleteRecordsAction',
                'methods' => ['POST'],
                'ajax' => true,
            ],
        ],
        'moduleData' => [
            'depthSelection' => 0,
            'tableSelection' => '',
            'language' => '',
        ],
    ],
];
