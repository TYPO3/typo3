.. include:: /Includes.rst.txt

.. _important-98960-1667212946:

===================================================================
Important: #98960 - Default type definition of custom Content Types
===================================================================

See :issue:`98960`

Description
===========

Due to the deprecation of Switchable Controller Actions for Extbase, it is
recommended to use custom content types as plugins. When using Extbase's API
:php:`\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin()` with
the 5th argument being set to
:php:`\TYPO3\CMS\Extbase\Utility\ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT`
or TYPO3's native API :php:`\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin()`
with the second argument being set to `CType`, the entered icon identifier
is now automatically added as `typeicon_classes` for the given `CType` and
the `TCA` types definition (`showitem`) of the default `header` type is
automatically applied, so extension authors do not need to add all default
fields anymore.

Note
----

These defaults are only applied if they are not set manually, so the changes
are optional defaults.

In addition, the API method :php:`\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes()`
now also allows to add custom fields after a palette, so common variants such
as the `Plugin` tab can be added after a specific palette.

Example
-------

Example for a custom Extbase plugin with TYPO3's Core "felogin" extension
in `EXT:felogin/Configuration/TCA/Overrides/tt_content.php`:

..  code-block:: php
    :caption: EXT:felogin/Configuration/TCA/Overrides/tt_content.php

    call_user_func(static function () {
        $contentTypeName = 'felogin_login';
        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
            'Felogin',
            'Login',
            'LLL:EXT:felogin/Resources/Private/Language/Database.xlf:tt_content.CType.felogin_login.title',
            'mimetypes-x-content-login',
            'forms'
        );

        // Add the FlexForm for the new content type
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue(
            '*',
            'FILE:EXT:felogin/Configuration/FlexForms/Login.xml',
            $contentTypeName
        );

        // Add the FlexForm to the showitem list
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
            'tt_content',
            '--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.plugin, pi_flexform',
            $contentTypeName,
            'after:palette:headers'
        );
    });

It is configured to be a content element with its own ctype by having the 5th
parameter set to :php:`ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT`.

..  code-block:: php
    :caption: EXT:felogin/ext_localconf.php
    :emphasize-lines: 14

    use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

    ExtensionUtility::configurePlugin(
        'Felogin',
        'Login',
        [
            LoginController::class => 'login, overview',
            PasswordRecoveryController::class => 'recovery,showChangePassword,changePassword',
        ],
        [
            LoginController::class => 'login, overview',
            PasswordRecoveryController::class => 'recovery,showChangePassword,changePassword',
        ],
        ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT
    );

.. index:: Backend, TCA, ext:core
