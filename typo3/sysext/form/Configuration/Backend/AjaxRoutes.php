<?php
use TYPO3\CMS\Form\Controller\WizardController;

/**
 * Definitions for AJAX routes provided by EXT:form
 */
return [
    // Loads the current form wizard data
    'formwizard_load' => [
        'path' => '/wizard/form/load',
        'target' => WizardController::class . '::loadAction'
    ],

    // Save the current form wizard
    'formwizard_save' => [
        'path' => '/wizard/form/save',
        'target' => WizardController::class . '::saveAction'
    ],
];
