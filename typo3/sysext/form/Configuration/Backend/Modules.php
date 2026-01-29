<?php

use TYPO3\CMS\Form\Controller\FormEditorController;
use TYPO3\CMS\Form\Controller\FormManagerController;

/**
 * Definitions for modules provided by EXT:form
 */
return [
    'web_FormFormbuilder' => [
        'parent' => 'content',
        'position' => ['after' => 'workspaces_admin'],
        'access' => 'user',
        'path' => '/module/form',
        'iconIdentifier' => 'module-form',
        'labels' => 'form.module',
        'inheritNavigationComponentFromMainModule' => false,
    ],
    'form_manager' => [
        'parent' => 'web_FormFormbuilder',
        'access' => 'user',
        'path' => '/module/form/overview',
        'iconIdentifier' => 'module-form',
        'labels' => 'form.modules.form_manager',
        'extensionName' => 'Form',
        'controllerActions' => [
            FormManagerController::class => [
                'index', 'show', 'create', 'duplicate', 'references', 'delete',
            ],
        ],
    ],
    'form_editor' => [
        'parent' => 'web_FormFormbuilder',
        'access' => 'user',
        'path' => '/module/form/editor',
        'iconIdentifier' => 'module-form',
        'navigationComponent' => '@typo3/form/backend/form-editor-tree-container',
        'labels' => 'form.modules.form_editor',
        'extensionName' => 'Form',
        'controllerActions' => [
            FormEditorController::class => [
                'index', 'saveForm', 'renderFormPage',
            ],
        ],
    ],
];
