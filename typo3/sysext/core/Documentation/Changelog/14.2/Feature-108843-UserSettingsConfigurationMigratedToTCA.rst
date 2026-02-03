..  include:: /Includes.rst.txt

..  _feature-108843-1738600001:

============================================================
Feature: #108843 - User settings configuration migrated to TCA
============================================================

See :issue:`108843`
See :issue:`108832`

Description
===========

The backend user profile settings configuration, previously stored in
:php:`$GLOBALS['TYPO3_USER_SETTINGS']`, is now available in TCA at
:php:`$GLOBALS['TCA']['be_users']['columns']['user_settings']`.

This allows the user settings to benefit from TCA-based tooling and provides
a consistent API that extensions already use for other configurations.

A new method :php:`ExtensionManagementUtility::addUserSetting()` has been
introduced to simplify adding custom fields to the user profile settings.

Impact
======

Extensions can add custom fields to the backend user profile settings using
the new :php:`addUserSetting()` method in :file:`Configuration/TCA/Overrides/be_users.php`:

..  code-block:: php

    // Configuration/TCA/Overrides/be_users.php
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addUserSetting(
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

Alternatively, extensions can directly modify the TCA:

..  code-block:: php

    // Configuration/TCA/Overrides/be_users.php
    $GLOBALS['TCA']['be_users']['columns']['user_settings']['columns']['myCustomSetting'] = [
        'label' => 'LLL:EXT:my_ext/Resources/Private/Language/locallang.xlf:myCustomSetting',
        'config' => [
            'type' => 'check',
            'renderType' => 'checkboxToggle',
        ],
    ];

    // Add to showitem
    $GLOBALS['TCA']['be_users']['columns']['user_settings']['showitem'] .= ',myCustomSetting';

Structure
---------

The :php:`user_settings` TCA column has the following structure:

:php:`columns`
    Array of field configurations, each containing:

    :php:`label`
        The field label (LLL reference or string)

    :php:`config`
        Standard TCA config array (type, renderType, items, etc.)

    :php:`table` (optional)
        Set to :php:`'be_users'` if the field is stored in a be_users table column

:php:`showitem`
    Comma-separated list of fields to display, supports :php:`--div--;` for tabs

Available field types
---------------------

*   :php:`input` - Text input field
*   :php:`number` - Number input field
*   :php:`email` - Email input field
*   :php:`password` - Password input field
*   :php:`check` with :php:`renderType => 'checkboxToggle'` - Checkbox/toggle
*   :php:`select` with :php:`renderType => 'selectSingle'` - Select dropdown
*   :php:`language` - Language selector

Backward compatibility
----------------------

For backward compatibility, the legacy :php:`$GLOBALS['TYPO3_USER_SETTINGS']` array
is still supported. Third-party additions are automatically migrated to TCA after
all :file:`ext_tables.php` files have been loaded. However, this approach is
deprecated and extensions should migrate to the new TCA-based API.

..  index:: Backend, TCA, PHP-API, ext:setup
