..  include:: /Includes.rst.txt

..  _deprecation-109438-1774951763:

====================================================
Deprecation: #109438 - ext_tables.php in extensions
====================================================

See :issue:`109438`

Description
===========

Extensions that still ship an :file:`ext_tables.php` file will now trigger
a PHP :php:`E_USER_DEPRECATED` error when the file is loaded during
a non-cached request or cache warm-up.

The :file:`ext_tables.php` file was historically used to register backend
modules, page doktypes, user settings, and other runtime configuration.
All of these use cases now have dedicated alternatives in modern TYPO3:

*  Backend modules: :file:`Configuration/Backend/Modules.php`
*  Backend routes: :file:`Configuration/Backend/Routes.php`
*  User settings: :file:`Configuration/TCA/Overrides/be_users.php`
   (see :issue:`108843`)
*  Page doktype allowed record types: :file:`Configuration/TCA/Overrides/pages.php`
   (see :issue:`108557`)

Impact
======

A PHP :php:`E_USER_DEPRECATED` error is triggered for every third-party
extension that still provides an :file:`ext_tables.php` file whenever
:file:`ext_tables.php` files are loaded without caching, for example during
cache warm-up or in a development context.

Support for :file:`ext_tables.php` will be removed in TYPO3 v15.0.

Affected installations
======================

All installations with third-party extensions that still ship an
:file:`ext_tables.php` file.

Migration
=========

Move all registration from :file:`ext_tables.php` to the appropriate
configuration files.

User settings
-------------

User settings previously registered via
:php:`TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addFieldsToUserSettings()` in
:file:`ext_tables.php` should now be registered via
:php:`TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addUserSetting()` in
:file:`Configuration/TCA/Overrides/be_users.php`.

Before:

..  code-block:: php
    :caption: ext_tables.php

    use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

    $GLOBALS['TYPO3_USER_SETTINGS']['columns']['myCustomSetting'] = [
        'type' => 'check',
        'label' => 'LLL:EXT:my_ext/Resources/Private/Language/locallang.xlf:myCustomSetting',
    ];
    ExtensionManagementUtility::addFieldsToUserSettings(
        'myCustomSetting',
        'after:emailMeAtLogin'
    );


After:

..  code-block:: php
    :caption: Configuration/TCA/Overrides/be_users.php

    use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

    ExtensionManagementUtility::addUserSetting(
        'myCustomSetting',
        [
            'label' => 'LLL:EXT:my_ext/Resources/Private/Language/locallang.xlf:myCustomSetting',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
            ],
        ],
        'after:emailMeAtLogin'
    );

Page doktype allowed record types
----------------------------------

Page doktypes previously registered via :php:`PageDoktypeRegistry->add()` in
:file:`ext_tables.php` should now use the TCA option
:php:`allowedRecordTypes` in :file:`Configuration/TCA/Overrides/pages.php`.

Before:

..  code-block:: php
    :caption: ext_tables.php

    \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
        \TYPO3\CMS\Core\DataHandling\PageDoktypeRegistry::class
    )->add(116, [
        'allowedTables' => ['tt_content', 'my_custom_record'],
    ]);

After:

..  code-block:: php
    :caption: Configuration/TCA/Overrides/pages.php

    $GLOBALS['TCA']['pages']['types']['116']['allowedRecordTypes'] = [
        'tt_content',
        'my_custom_record',
    ];

Once all registrations have been moved, the :file:`ext_tables.php` file
can be removed from the extension.

..  index:: PHP-API, NotScanned, ext:core
