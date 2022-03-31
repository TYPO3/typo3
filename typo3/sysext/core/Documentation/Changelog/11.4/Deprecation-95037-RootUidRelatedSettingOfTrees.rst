.. include:: /Includes.rst.txt

======================================================
Deprecation: #95037 - rootUid related setting of trees
======================================================

See :issue:`95037`

Description
===========

The setting :php:`rootUid` used in FormEngine's :php:`treeConfig` is superseded by
:php:`startingPoints` and has been marked as deprecated.

In :php:`TYPO3\CMS\Core\Tree\TableConfiguration\DatabaseTreeDataProvider` the
following methods have been marked as deprecated:

* :php:`setRootUid()`
* :php:`getRootUid()`


Impact
======

Using `treeConfig/rootUid` in TCA will trigger a TCA migration to
`treeConfig/startingPoints` and raise a PHP :php:`E_USER_DEPRECATED` error.

The same applies to the according page TSconfig option.

The extension scanner detects any call to :php:`setRootUid()`
or :php:`getRootUid()` as weak match.


Affected Installations
======================

All extensions defining `rootUid` in their `TCA` or `TSconfig` are affected.
Furthermore all extensions directly calling one of the mentioned methods in
:php:`TYPO3\CMS\Core\Tree\TableConfiguration\DatabaseTreeDataProvider`.


Migration
=========

The setting `treeConfig/rootUid` can be migrated to `treeConfig/startingPoints`
passing the value as string, since `treeConfig/startingPoints` takes a
comma-separated value. The methods :php:`setRootUid()` and :php:`getRootUid()`
can be replaced by their successors :php:`setStartingPoints()` and
:php:`getStartingPoints()`.

.. index:: Backend, PartiallyScanned, ext:backend
