<?php

use TYPO3\CMS\Form\Controller\FormEditorController;
use TYPO3\CMS\Form\Controller\FormManagerController;

/**
 * Definitions for modules provided by EXT:form
 */
return [
    'web_FormFormbuilder' => [
        'parent' => 'web',
        'access' => 'user',
        'iconIdentifier' => 'module-form',
        'inheritNavigationComponentFromMainModule' => false,
        'labels' => 'LLL:EXT:form/Resources/Private/Language/locallang_module.xlf',
        'extensionName' => 'Form',
        'controllerActions' => [
            FormManagerController::class => [
                'index', 'show', 'create', 'duplicate', 'references', 'delete',
            ],
            FormEditorController::class => [
                'index', 'saveForm', 'renderFormPage', 'renderRenderableOptions',
            ],
        ],
    ],
];
