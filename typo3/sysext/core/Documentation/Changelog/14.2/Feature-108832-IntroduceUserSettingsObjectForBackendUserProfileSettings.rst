..  include:: /Includes.rst.txt

..  _feature-108832-1738500000:

==================================================================================
Feature: #108832 - Introduce UserSettings object for backend user profile settings
==================================================================================

See :issue:`108832`

Description
===========

A new :php:`UserSettings` object provides structured access to backend user
profile settings defined via :php:`$GLOBALS['TYPO3_USER_SETTINGS']`.

UserSettings Object
-------------------

The :php:`UserSettings` object can be retrieved via the backend user:

..  code-block:: php

    $userSettings = $GLOBALS['BE_USER']->getUserSettings();

    // Check if a setting exists
    if ($userSettings->has('colorScheme')) {
        $scheme = $userSettings->get('colorScheme');
    }

    // Get all settings as array
    $allSettings = $userSettings->toArray();

    // Typed access via dedicated methods
    $emailOnLogin = $userSettings->isEmailMeAtLoginEnabled();
    $showUploadFields = $userSettings->isUploadFieldsInTopOfEBEnabled();

The class implements :php:`Psr\Container\ContainerInterface` with :php:`has()`
and :php:`get()` methods. The :php:`get()` method throws
:php:`UserSettingsNotFoundException` if the setting does not exist.

New JSON Storage with Backward Compatibility
--------------------------------------------

Profile settings are now stored in a new :sql:`be_users.user_settings` JSON
field, providing a structured and queryable format. For backward compatibility,
the existing serialized :sql:`uc` blob continues to be written alongside:

..  code-block:: php

    // Writing still uses the uc mechanism
    $GLOBALS['BE_USER']->uc['colorScheme'] = 'dark';
    $GLOBALS['BE_USER']->writeUC();
    // Both uc (serialized) and user_settings (JSON) are updated

An upgrade wizard "Migrate user profile settings to JSON format" migrates
existing settings from the :sql:`uc` blob to the new :sql:`user_settings` field.

Impact
======

Backend user profile settings can now be accessed via the :php:`UserSettings`
object, providing type safety and IDE support. The new JSON storage format
improves data accessibility while maintaining full backward compatibility
through dual-write to both storage formats.

..  index:: Backend, PHP-API, ext:core
