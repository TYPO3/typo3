<?php
use TYPO3\CMS\Form\Controller\WizardController;

/**
 * Definitions for AJAX routes provided by EXT:form
 */
return [
    // Save the current form wizard
    'formwizard_save' => [
        'path' => '/wizard/form/save',
        'target' => WizardController::class . '::saveAction'
    ],
];
