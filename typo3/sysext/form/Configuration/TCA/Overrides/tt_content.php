<?php

defined('TYPO3') or die();

call_user_func(static function () {
    // Register the plugin
    $contentTypeName = \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
        'Form',
        'Formframework',
        'LLL:EXT:form/Resources/Private/Language/locallang.xlf:form_new_wizard_title',
        'content-form',
        'forms',
        'LLL:EXT:form/Resources/Private/Language/locallang.xlf:form_new_wizard_description',
        'FILE:EXT:form/Configuration/FlexForms/FormFramework.xml',
    );

    $GLOBALS['TCA']['tt_content']['types'][$contentTypeName]['previewRenderer'] = \TYPO3\CMS\Form\Hooks\FormPagePreviewRenderer::class;
});
