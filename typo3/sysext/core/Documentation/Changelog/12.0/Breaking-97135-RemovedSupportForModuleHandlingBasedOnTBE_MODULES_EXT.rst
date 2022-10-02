.. include:: /Includes.rst.txt

.. _breaking-97135:

===============================================================================
Breaking: #97135 - Removed support for module handling based on TBE_MODULES_EXT
===============================================================================

See :issue:`97135`

Description
===========

Previously it had been possible to add additional functionality to TYPO3
backend modules, such as :guilabel:`Web > Info` or :guilabel:`Web > Template`,
using the :php:`ExtensionManagementUtility::insertModuleFunction()` API method,
which attached a new entry to the global :php:`TBE_MODULES_EXT` array.

Since the introduction of the new
:doc:`Module Registration API <Feature-96733-NewBackendModuleRegistrationAPI>`,
all modules are registered in the dedicated :file:`Configuration/Backend/Modules.php`
configuration file. Additional modules, or "third-level modules" are now also
registered via the new mechanism.

Therefore, the :php:`$GLOBALS['TBE_MODULES_EXT']` has been removed, while the
corresponding :php:`ExtensionManagementUtility::insertModuleFunction()` API
method has no effect.

The related TSconfig options :typoscript:`mod.web_info.menu.function`
as well as :typoscript:`mod.web_ts.menu.function` have been removed in favor
of the existing :typoscript:`hideModules` option and the module access logic,
which due to the new registration, now also covers those modules.

Additionally, the following hooks have been removed, because their use cases
does no longer exist:

- :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['TYPO3\CMS\Tstemplate\Controller\TypoScriptTemplateModuleController']['newStandardTemplateView']`
- :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['TYPO3\CMS\Tstemplate\Controller\TypoScriptTemplateModuleController']['newStandardTemplateHandler']`

Impact
======

The global :php:`TBE_MODULES_EXT` array does no longer exist and the
:php:`ExtensionManagementUtility::insertModuleFunction()` API method no
longer has any effect.

The TSconfig options :typoscript:`mod.web_info.menu.function`
and :typoscript:`mod.web_ts.menu.function` are no longer evaluated.

Using one of mentioned, :php:`TypoScriptTemplateModuleController` related
hooks does no longer have any effect.

Affected Installations
======================

All installations using the global :php:`TBE_MODULES_EXT` array or
calling :php:`ExtensionManagementUtility::insertModuleFunction()` in
custom extension code.

All installations using one of the removed TSconfig options or one
of the removed hooks.

Migration
=========

Register your "third-level" module in our extension's
:file:`Configuration/Backend/Modules.php` file.

Previous configuration in :file:`ext_tables.php`:

..  code-block:: php

    ExtensionManagementUtility::insertModuleFunction(
        'web_info',
        MyAdditonalInfoModuleController::class,
        '',
        'LLL:EXT:extkey/Resources/Private/Language/locallang.xlf:mod_title'
    );

Will now be registered in :file:`Configuration/Backend/Modules.php`:

..  code-block:: php

    'web_info_additional' => [
        'parent' => 'web_info',
        'access' => 'user',
        'path' => '/module/web/info/additional',
        'iconIdentifier' => 'module-my-icon-identifier',
        'labels' => [
            'title' => 'LLL:EXT:extkey/Resources/Private/Language/locallang.xlf:mod_title',
        ],
        'routes' => [
            '_default' => [
                'target' => MyAdditonalInfoModuleController::class . '::handleRequest',
            ],
        ],
    ],

To hide a "third-level" module in the doc header menu, use the
:typoscript:`options.hideModules` option:

..  code-block:: typoscript

    # before
    mod.web_info.menu.function.TYPO3\CMS\Info\Controller\TranslationStatusController = 0

    # after
    options.hideModules := addToList(web_info_translations)

Additionally, use the module access logic to restrict access to those modules.

Remove any registration of the mentioned hooks. There is no direct migration,
since the use cases for those hooks do no longer exist.

.. index:: Backend, PHP-API, PartiallyScanned, ext:backend
