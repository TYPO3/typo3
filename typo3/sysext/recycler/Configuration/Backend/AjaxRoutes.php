<?php

/**
 * Definitions for routes provided by EXT:recycler
 */
return [
    // Startup the recycler module
    'recycler' => [
        'path' => '/recycler',
        'target' => \TYPO3\CMS\Recycler\Controller\RecyclerAjaxController::class . '::dispatch'
    ],
];
