<?php

defined('TYPO3') or die();

call_user_func(static function () {
    $contentTypeName = \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
        'Felogin',
        'Login',
        'LLL:EXT:felogin/Resources/Private/Language/Database.xlf:tt_content.CType.felogin_login.title',
        'mimetypes-x-content-login',
        'forms',
        'LLL:EXT:felogin/Resources/Private/Language/Database.xlf:tt_content.CType.felogin_login.description',
    );

    // Add the FlexForm
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue(
        '*',
        'FILE:EXT:felogin/Configuration/FlexForms/Login.xml',
        $contentTypeName
    );

    // Add the FlexForm to the show item list
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
        'tt_content',
        '--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.plugin, pi_flexform',
        $contentTypeName,
        'after:palette:headers'
    );
});
