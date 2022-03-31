.. include:: /Includes.rst.txt

==============================================================
Deprecation: #77987 - Deprecated record listing in page module
==============================================================

See :issue:`77987`

Description
===========

The usage of :php:`$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['cms']['db_layout']['addTables']` to render
records in the page module has been marked as deprecated.

Accessing property :php:`TYPO3\CMS\Backend\Controller\PageLayoutController->externalTables` has
been deprecated.


Affected Installations
======================

Instances using :php:`$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['cms']['db_layout']['addTables']`
or accessing property :php:`TYPO3\CMS\Backend\Controller\PageLayoutController->externalTables`.


Migration
=========

No migration available.

.. index:: LocalConfiguration, Backend, PHP-API
