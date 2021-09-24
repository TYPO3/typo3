<?php
/**
 * Definitions for routes provided by EXT:workspaces
 */
return [
    'workspace_previewcontrols' => [
        'path' => '/workspace/preview-control/',
        'target' => \TYPO3\CMS\Workspaces\Controller\PreviewController::class . '::handleRequest',
    ],
];
