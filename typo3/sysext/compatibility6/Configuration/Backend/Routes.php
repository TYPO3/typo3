<?php
/**
 * Definitions of routes
 */
return [
    // Register wizard
    'wizard_forms' => [
        'path' => '/wizard/forms',
        'target' => \TYPO3\CMS\Compatibility6\Controller\Wizard\FormsController::class . '::mainAction'
    ]
];
