..  include:: /Includes.rst.txt

..  _breaking-106412-1742592429:

================================================================
Breaking: #106412 - TCA interface settings for list view removed
================================================================

See :issue:`106412`

Description
===========

Each TCA definition has had an optional entry named `interface` to define
relevant information for displaying the TCA records.

The TCA options :php:`['interface']['maxSingleDBListItems']` and
:php:`['interface']['maxDBListItems']` are removed and not evaluated anymore.

These options have been used for defining the amount of table rows to show
within TYPO3's Web>List module.


Impact
======

Setting these values in custom extensions will have no effect anymore, as they
are automatically removed during build-time.


Affected installations
======================

TYPO3 installations with custom TCA settings from third-party-extensions.


Migration
=========

Overriding visual settings can still be done on a per - User TSconfig or
per - Page TSconfig level, which is much more flexible anyways, as it allows
for rendering different amount of  values per site / pagetree or usergroup.

The TCA option  :php:`['interface']['maxSingleDBListItems']` is removed in
favor of :tsconfig:`mod.web_list.itemsLimitSingleTable`.

The TCA option php:`['interface']['maxDBListItems']`is removed in
favor of :tsconfig:`mod.web_list.itemsLimitPerTable`.

..  index:: TCA, NotScanned, ext:backend
