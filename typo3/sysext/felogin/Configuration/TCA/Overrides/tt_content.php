<?php

defined('TYPO3_MODE') or die();

call_user_func(static function () {
    $feloginExtbase = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Configuration\Features::class)
        ->isFeatureEnabled('felogin.extbase');

    if ($feloginExtbase) {
        // Extbase plugin has a different CType
        $contentTypeName = 'felogin_login';
        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
            'Felogin',
            'Login',
            'Login Form',
            null,
            'forms'
        );
    } else {
        $contentTypeName = 'login';
        // Add CType=login
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTcaSelectItem(
            'tt_content',
            'CType',
            [
                'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:CType.I.10',
                'login',
                'content-elements-login',
                'forms'
            ]
        );
    }
    // Add the FlexForm
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue(
        '*',
        'FILE:EXT:felogin/Configuration/FlexForms/Login.xml',
        $contentTypeName
    );
    $GLOBALS['TCA']['tt_content']['ctrl']['typeicon_classes'][$contentTypeName] = 'mimetypes-x-content-login';

    $GLOBALS['TCA']['tt_content']['types'][$contentTypeName]['showitem'] = '
        --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,
            --palette--;;general,
            --palette--;;headers,
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
});
