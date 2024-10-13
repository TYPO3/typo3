.. include:: /Includes.rst.txt

.. _deprecation-105076-1726923626:

==================================================================
Deprecation: #105076 - Plugin content element and plugin sub types
==================================================================

See :issue:`105076`

Description
===========

Historically, plugins have been registered using the :php:`list` content
element and the plugin subtype :php:`list_type` field. This functionality
has been kept for backwards compatibility reasons. However, since the release
of TYPO3 v12.4, the recommended way to create a plugin is by using a dedicated
content type (`CType`) for each plugin.

This old "General Plugin" approach has always been ugly from a UX perspective point
of view since it hides plugin selection behind "General plugin" content element,
forcing a second selection step and making such plugins something special.

Therefore, the plugin content element (:php:`list`) and the plugin sub types
field (:php:`list_type`) have been marked as deprecated in TYPO3 v13.4 and will
be removed in TYPO3 v14.0.

Additionally, the related PHP constant
:php:`TYPO3\CMS\Extbase\Utility\ExtensionUtility::PLUGIN_TYPE_PLUGIN` has been
deprecated as well.

Impact
======

Plugins added using
:php:`TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin()` where
the second parameter is :php:`list_type` (which is still the default) will
trigger a deprecation level log entry in TYPO3 v13 and will fail in v14.

Therefore, the same applies on using
:php:`TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin()` (to
configure the plugin for frontend rendering), where no fifth parameter is
provided or where the fifth parameter is :php:`list_type`
(:php:`ExtensionUtility::PLUGIN_TYPE_PLUGIN`), which is still the default.


.. note::

    :php:`addPlugin()` is also internally called when registering a plugin
    via :php:`TYPO3\CMS\Core\Utility\ExtensionManagementUtility::registerPlugin()`.
    In that case the plugin type to use is either the one defined via
    :php:`TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin()`
    or also falls back to :php:`list_type`.


Affected installations
======================

Extensions registering plugins as :php:`list_type` plugin sub type.


Migration
=========

Existing plugins must be migrated to use the :php:`CType` record type.
Extension authors must implement the following changes:

* Register plugins using the :php:`CType` record type
* Create update wizard which extends :php:`\TYPO3\CMS\Install\Updates\AbstractListTypeToCTypeUpdate`
  and add :php:`list_type` to :php:`CType` mapping for each plugin to migrate.
  The migration wizard for indexed_search in class :php:`IndexedSearchCTypeMigration`
  can be used as reference example.
* Migrate possible FlexForm registration and add dedicated :php:`showitem` TCA
  configuration
* Migrate possible PreviewRenderer registration in TCA
* Adapt possible content element wizard items in Page TSConfig, where
  :php:`list_type` is used
* Adapt possible content element restrictions in backend layouts or container
  elements defined by third-party extensions like ext:content_defender

Common example
^^^^^^^^^^^^^^

.. code-block:: php

    // Before

    $GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist']['my_plugin'] = 'pi_flexform';
    $GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist']['my_plugin'] = 'pages,layout,recursive';

    // After
    $GLOBALS['TCA']['tt_content']['types']['my_plugin']['showitem'] = '<Some Fields>,pi_flexform,<Other Fields>';

.. index:: Fluid, PHP-API, PartiallyScanned, ext:extbase
