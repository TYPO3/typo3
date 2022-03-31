.. include:: /Includes.rst.txt

======================================================================
Deprecation: #92922 - Use of record uid in AbstractTreeView::getIcon()
======================================================================

See :issue:`92922`

Description
===========

To increase the type safety through the whole TYPO3 core and to properly
reflect the expected value for the parameter (based on its name),
calling :php:`AbstractTreeView::getIcon()` with a record uid as first
argument has been marked as deprecated.

Note: Using a record uid had actually no benefit (performance wise)
since the method fetched the record internally in that case anyways,
but without adding any restrictions or respecting any overlays e.g.
for workspaces.


Impact
======

Calling the method with an :php:`integer` for parameter :php:`$row`
will trigger a PHP :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

All installations calling this method with an :php:`integer` for
parameter :php:`$row`.


Migration
=========

Provide the full record row as first argument.

.. index:: Backend, PHP-API, NotScanned, ext:backend
