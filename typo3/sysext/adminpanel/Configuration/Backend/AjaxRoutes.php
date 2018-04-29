<?php

/**
 * Definitions for routes provided by EXT:adminpanel
 * Contains all AJAX-based routes for entry points
 */
return [
    'adminPanel_saveForm' => [
        'path' => '/adminpanel/form/save',
        'target' => \TYPO3\CMS\Adminpanel\Controller\AjaxController::class . '::saveDataAction'
    ],
    'adminPanel_toggle' => [
        'path' => '/adminpanel/toggleActiveState',
        'target' => \TYPO3\CMS\Adminpanel\Controller\AjaxController::class . '::toggleActiveState'
    ],
];
