..  include:: /Includes.rst.txt

..  _breaking-106412-1742592429:

================================================================
Breaking: #106412 - TCA interface settings for list view removed
================================================================

See :issue:`106412`

Description
===========

Each TCA definition previously had an optional section named
:php:`['interface']`, which defined parameters for displaying TCA records.

The last remaining options within this section,
:php:`$GLOBALS['TCA'][$table]['interface']['maxSingleDBListItems']` and
:php:`$GLOBALS['TCA'][$table]['interface']['maxDBListItems']`, have been
removed. As a result, the entire :php:`['interface']` section is no longer
supported and will be ignored.

These settings were used to define the number of table rows displayed within
the :guilabel:`Content > List` backend module.

Impact
======

The :php:`$GLOBALS['TCA'][$table]['interface']` section in TCA definitions is
no longer evaluated.

Setting any values under this key in custom extensions has no effect and will
be automatically removed during build time.

Affected installations
======================

TYPO3 installations with custom TCA settings defining
:php:`$GLOBALS['TCA'][$table]['interface']` are affected.

Migration
=========

Visual display settings can still be overridden on a per-user or per-page
basis via TSconfig. This approach is more flexible, as it allows rendering
different numbers of items per site, page tree, or user group.

The TCA option
:php:`$GLOBALS['TCA'][$table]['interface']['maxSingleDBListItems']` has been
removed in favor of :tsconfig:`mod.web_list.itemsLimitSingleTable`.

The TCA option
:php:`$GLOBALS['TCA'][$table]['interface']['maxDBListItems']` has been removed
in favor of :tsconfig:`mod.web_list.itemsLimitPerTable`.

..  index:: TCA, NotScanned, ext:backend
