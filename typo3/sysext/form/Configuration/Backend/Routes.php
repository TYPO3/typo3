<?php
use TYPO3\CMS\Form\Controller\WizardController;

/**
 * Definitions for Routes provided by EXT:form
 */
return [
    // Loads the form HTML page
    'wizard_form' => [
        'path' => '/wizard/form/show',
        'target' => WizardController::class . '::indexAction'
    ],
];
