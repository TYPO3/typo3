<?php

declare(strict_types=1);

use TYPO3\CMS\Extbase\Utility\ExtensionUtility;
use TYPO3\CMS\Form\Controller\FormEditorController;
use TYPO3\CMS\Form\Controller\FormManagerController;

defined('TYPO3') or die();

// Register the backend module Web->Forms
ExtensionUtility::registerModule(
    'Form',
    'web',
    'formbuilder',
    '',
    [
        FormManagerController::class => 'index, show, create, duplicate, references, delete',
        FormEditorController::class => 'index, saveForm, renderFormPage, renderRenderableOptions',
    ],
    [
        'access' => 'user,group',
        'iconIdentifier' => 'module-form',
        'labels' => 'LLL:EXT:form/Resources/Private/Language/locallang_module.xlf',
        'navigationComponentId' => '',
        'inheritNavigationComponentFromMainModule' => false,
    ]
);
