..  include:: /Includes.rst.txt

..  _feature-108832-1738500000:

==================================================================================
Feature: #108832 - Introduce UserSettings object for backend user profile settings
==================================================================================

See :issue:`108832`

Description
===========

A new :php:`\TYPO3\CMS\Core\Authentication\UserSettings` object provides structured access to backend user
profile settings defined in :php:`$GLOBALS['TYPO3_USER_SETTINGS']`.

UserSettings object
-------------------

The :php-short:`\TYPO3\CMS\Core\Authentication\UserSettings` object can be retrieved via the backend user:

..  code-block:: php

    /** @var \TYPO3\CMS\Core\Authentication\UserSettings $userSettings */
    $userSettings = $GLOBALS['BE_USER']->getUserSettings();

    // Check if a setting exists
    if ($userSettings->has('colorScheme')) {
        $scheme = $userSettings->get('colorScheme');
    }

    // Get all settings as an array
    $allSettings = $userSettings->toArray();

    // Typed access via dedicated methods
    $emailOnLogin = $userSettings->isEmailMeAtLoginEnabled();
    $showUploadFields = $userSettings->isUploadFieldsInTopOfEBEnabled();

The class implements :php-short:`Psr\Container\ContainerInterface` with :php:`has()`
and :php:`get()` methods. The :php:`get()` method throws
:php-short:`\TYPO3\CMS\Core\Authentication\Exception\UserSettingsNotFoundException`
if the setting does not exist.

New JSON storage with backward compatibility
--------------------------------------------

Profile settings are now stored in a new :sql:`be_users.user_settings` JSON
field, providing a structured and queryable format. For backward compatibility,
the existing serialized :sql:`uc` blob continues to be written alongside:

..  code-block:: php

    // Writing still uses the uc mechanism
    $GLOBALS['BE_USER']->uc['colorScheme'] = 'dark';
    $GLOBALS['BE_USER']->writeUC();
    // Both uc (serialized) and user_settings (JSON) are updated

An upgrade wizard, "Migrate user profile settings to JSON format", migrates
existing settings from the :sql:`uc` blob to the new :sql:`user_settings` field.

Impact
======

Backend user profile settings can now be accessed via the
:php-short:`\TYPO3\CMS\Core\Authentication\UserSettings` object, providing
type safety and IDE support. The new JSON storage format improves data
accessibility while maintaining full backward compatibility through dual-write
to both storage formats.

..  index:: Backend, PHP-API, ext:core
