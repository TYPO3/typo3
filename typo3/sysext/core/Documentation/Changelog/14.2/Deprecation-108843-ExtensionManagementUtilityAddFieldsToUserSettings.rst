..  include:: /Includes.rst.txt

..  _deprecation-108843-1738600000:

==========================================================================
Deprecation: #108843 - ExtensionManagementUtility::addFieldsToUserSettings
==========================================================================

See :issue:`108843`
See :issue:`108832`

Description
===========

The method :php:`\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addFieldsToUserSettings()`
has been deprecated in favor of the new :php:`addUserSetting()` method.

The legacy method required two separate steps to add a field to user settings:
first adding the field configuration to the columns array, then calling
:php:`addFieldsToUserSettings()` to add it to the showitem list. The new method
combines both steps into a single call and uses TCA as the storage location.

Impact
======

Calling the deprecated method will trigger a deprecation-level log entry.
The method will be removed in TYPO3 v15.0.

The extension scanner reports usages as a **strong** match.

Affected installations
======================

Instances or extensions that use :php:`ExtensionManagementUtility::addFieldsToUserSettings()`
or directly modify :php:`$GLOBALS['TYPO3_USER_SETTINGS']` to add custom fields to the
backend user profile settings are affected.

Migration
=========

Replace the two-step approach with the new :php:`addUserSetting()` method.
Note that the new method uses TCA-style configuration and should be called from
:file:`Configuration/TCA/Overrides/be_users.php` instead of :file:`ext_tables.php`.

Before
~~~~~~

..  code-block:: php

    // In ext_tables.php
    $GLOBALS['TYPO3_USER_SETTINGS']['columns']['myCustomSetting'] = [
        'type' => 'check',
        'label' => 'LLL:EXT:my_ext/Resources/Private/Language/locallang.xlf:myCustomSetting',
    ];
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addFieldsToUserSettings(
        'myCustomSetting',
        'after:emailMeAtLogin'
    );

After
~~~~~

..  code-block:: php

    // In Configuration/TCA/Overrides/be_users.php
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

Field type mapping
------------------

When migrating, use the following type mappings:

==============  ==============================================
Legacy type     TCA config
==============  ==============================================
text            :php:`['type' => 'input']`
email           :php:`['type' => 'email']`
number          :php:`['type' => 'number']`
password        :php:`['type' => 'password']`
check           :php:`['type' => 'check', 'renderType' => 'checkboxToggle']`
select          :php:`['type' => 'select', 'renderType' => 'selectSingle']`
language        :php:`['type' => 'language']`
==============  ==============================================

..  index:: PHP-API, FullyScanned, ext:core
