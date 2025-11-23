..  include:: /Includes.rst.txt

..  _deprecation-107047-1751984220:

=======================================================================
Deprecation: #107047 - ExtensionManagementUtility::addPiFlexFormValue()
=======================================================================

See :issue:`107047`

Description
===========

The method
:php:`\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue()`
has been deprecated.

This method was historically used to assign FlexForm definitions to plugins
registered as subtypes in the :sql:`list_type` field of :sql:`tt_content`. With
the removal of subtypes (see :ref:`breaking-105377-1729513863`) and the shift
toward registering plugins as dedicated record types via `CType`, as well
as the removal of the `ds_pointerField` and multi-entry `ds` array
format (see :ref:`breaking-107047-1751982363`), this separate method call is no
longer necessary.

Impact
======

Calling this method will trigger a deprecation warning. The extension scanner
will also report any usages. The method will be removed in TYPO3 v15.0.

Affected installations
======================

Extensions that call
:php:`\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue()`
to assign FlexForm definitions to plugins or content elements are affected.

Migration
=========

Instead of using this method, define the FlexForm configuration using one of
the following approaches:

Option 1: Register via :php:`registerPlugin()`
----------------------------------------------

Provide the FlexForm definition directly when registering the plugin.
This has been possible since :ref:`feature-107047-1751984817`.

..  code-block:: php
    :caption: EXT:my_extension/ext_localconf.php

    use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

    ExtensionUtility::registerPlugin(
        'MyExtension',
        'MyPlugin',
        'My Plugin Title',
        'my-extension-icon',
        'plugins',
        'Plugin description',
        'FILE:EXT:my_extension/Configuration/FlexForm.xml'
    );

Option 2: Register via :php:`addPlugin()` for non-Extbase plugins
-----------------------------------------------------------------

This is also supported since :ref:`feature-107047-1751984817`.

..  code-block:: php
    :caption: EXT:my_extension/ext_localconf.php

    use TYPO3\CMS\Core\Imaging\Icon;
    use TYPO3\CMS\Core\Imaging\IconRegistry;
    use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
    use TYPO3\CMS\Core\Utility\GeneralUtility;
    use TYPO3\CMS\Core\Utility\SelectItemUtility\SelectItem;

    ExtensionManagementUtility::addPlugin(
        new SelectItem(
            'select',
            'My Plugin Title',
            'myextension_myplugin',
            'my-extension-icon',
            'plugins',
            'Plugin description'
        ),
        'FILE:EXT:my_extension/Configuration/FlexForm.xml'
    );

Option 3: Define FlexForm via TCA :php:`columnsOverrides`
---------------------------------------------------------

You can also directly define the FlexForm in TCA:

..  code-block:: php
    :caption: EXT:my_extension/Configuration/TCA/Overrides/tt_content.php

    $GLOBALS['TCA']['tt_content']['types']['my_plugin']['columnsOverrides']
        ['pi_flexform']['config']['ds'] =
        'FILE:EXT:my_extension/Configuration/FlexForm.xml';

..  index:: Backend, FlexForm, TCA, FullyScanned, ext:core
