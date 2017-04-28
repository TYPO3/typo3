<?php
defined('TYPO3_MODE') or die();

// Register the backend module Web->Forms
\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
    'TYPO3.CMS.Form',
    'web',
    'formbuilder',
    '',
    [
        'FormManager' => 'index, show, create, duplicate, references, delete',
        'FormEditor' => 'index, saveForm, renderFormPage, renderRenderableOptions',
    ],
    [
        'access' => 'user,group',
        'icon' => 'EXT:form/Resources/Public/Icons/module-form.svg',
        'labels' => 'LLL:EXT:form/Resources/Private/Language/locallang_module.xlf',
        'navigationComponentId' => '',
        'inheritNavigationComponentFromMainModule' => false
    ]
);
