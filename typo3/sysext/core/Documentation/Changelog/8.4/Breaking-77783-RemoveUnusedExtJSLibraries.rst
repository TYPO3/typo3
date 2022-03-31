.. include:: /Includes.rst.txt

============================================================
Breaking: #77783 - Removed unused ExtJS JavaScript libraries
============================================================

See :issue:`77783`

Description
===========

The ExtJS libraries `app.SearchField`, `grid.RowExpander`, `ux.FitToParent` have been removed from the TYPO3 core
after all usages of them have been removed from the TYPO3 core with Feature #74359.


Impact
======

Including or calling any of the named JavaScript libraries will result in an error.


Affected Installations
======================

Any TYPO3 installations using custom extensions based on ExtJS which rely on the named libraries.


Migration
=========

There is no migration available, consider migrating to a supported modern framework.

.. index:: Backend, JavaScript
