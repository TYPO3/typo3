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
    );

    // Add the FlexForm
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue(
        '*',
        'FILE:EXT:form/Configuration/FlexForms/FormFramework.xml',
        $contentTypeName
    );

    // Add the FlexForm to the show item list
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
        'tt_content',
        '--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.plugin, pi_flexform',
        $contentTypeName,
        'after:palette:headers'
    );

    $GLOBALS['TCA']['tt_content']['types'][$contentTypeName]['previewRenderer'] = \TYPO3\CMS\Form\Hooks\FormPagePreviewRenderer::class;
});
