<?php
defined('TYPO3_MODE') or die();

call_user_func(function () {
    // Add the FlexForm
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue(
        '*',
        'FILE:EXT:form/Configuration/FlexForms/FormFramework.xml',
        'form_formframework'
    );

    $GLOBALS['TCA']['tt_content']['ctrl']['typeicon_classes']['form_formframework'] = 'content-form';

    $GLOBALS['TCA']['tt_content']['types']['form_formframework']['showitem'] = '
        --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,
            --palette--;;general,
            --palette--;;header,
        --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.plugin,
            pi_flexform,
        --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.appearance,
            --palette--;;frames,
            --palette--;;appearanceLinks,
        --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language,
            --palette--;;language,
        --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,
            --palette--;;hidden,
            --palette--;;access,
        --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:categories,
            categories,
        --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:notes,
            rowDescription,
        --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended,
    ';
    // Register the plugin
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
        'TYPO3.CMS.Form',
        'Formframework',
        'Form',
        'content-form'
    );
});
