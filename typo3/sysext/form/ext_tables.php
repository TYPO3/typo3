<?php

defined('TYPO3_MODE') or die();

// Register the backend module Web->Forms
\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
    'Form',
    'web',
    'formbuilder',
    '',
    [
        \TYPO3\CMS\Form\Controller\FormManagerController::class => 'index, show, create, duplicate, references, delete',
        \TYPO3\CMS\Form\Controller\FormEditorController::class => 'index, saveForm, renderFormPage, renderRenderableOptions',
    ],
    [
        'access' => 'user,group',
        'icon' => 'EXT:form/Resources/Public/Icons/module-form.svg',
        'labels' => 'LLL:EXT:form/Resources/Private/Language/locallang_module.xlf',
        'navigationComponentId' => '',
        'inheritNavigationComponentFromMainModule' => false
    ]
);
