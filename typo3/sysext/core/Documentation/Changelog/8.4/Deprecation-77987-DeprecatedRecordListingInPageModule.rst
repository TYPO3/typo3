.. include:: ../../Includes.txt

==============================================================
Deprecation: #77987 - Deprecated record listing in page module
==============================================================

See :issue:`77987`

Description
===========

The usage of :php:`$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['cms']['db_layout']['addTables']` to render records in the page module has been marked as deprecated.


Affected Installations
======================

All installations using :php:`$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['cms']['db_layout']['addTables']`.


Migration
=========

No migration available.

.. index:: LocalConfiguration, Backend
