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

This method was historically used to assign FlexForm definitions
to plugins registered as subtypes in the `list_type` field of
`tt_content`. With the removal of subtypes (see :ref:`breaking-105377-1729513863`)
and the shift to registering plugins as dedicated record types via
`CType`, as well as the removal of the `ds_pointerField` and the multi-entry
`ds` array format (see :ref:`breaking-107047-1751982363`), this separate method
call is no longer necessary.

Impact
======

Calling this method will trigger a deprecation warning. The extension scanner
will also report any usages. The method will be removed in TYPO3 v15.

Affected installations
======================

Extensions that call
:php:`ExtensionManagementUtility::addPiFlexFormValue()` to assign FlexForm
definitions to plugins and content elements.

Migration
=========

Instead of using this method, define the FlexForm via one of these approaches:

Option 1: Provide the FlexForm in the :php:`registerPlugin()`` call, which is possible since
:ref:`feature-107047-1751984817`:


.. code-block:: php

    ExtensionUtility::registerPlugin(
        'MyExtension',
        'MyPlugin',
        'My Plugin Title',
        'my-extension-icon',
        'plugins',
        'Plugin description',
        'FILE:EXT:myext/Configuration/FlexForm.xml'
    );

Option 2: Use :php:`columnsOverrides` in TCA directly

.. code-block:: php

    $GLOBALS['TCA']['tt_content']['types']['my_plugin']['columnsOverrides']['pi_flexform']['config']['ds'] =
        'FILE:EXT:myext/Configuration/FlexForm.xml';

..  index:: Backend, FlexForm, TCA, FullyScanned, ext:core
