<?php

defined('TYPO3') or die();

call_user_func(static function () {
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
        'Felogin',
        'Login',
        'LLL:EXT:felogin/Resources/Private/Language/Database.xlf:tt_content.CType.felogin_login.title',
        'mimetypes-x-content-login',
        'forms',
        'LLL:EXT:felogin/Resources/Private/Language/Database.xlf:tt_content.CType.felogin_login.description',
        'FILE:EXT:felogin/Configuration/FlexForms/Login.xml',
    );
});
