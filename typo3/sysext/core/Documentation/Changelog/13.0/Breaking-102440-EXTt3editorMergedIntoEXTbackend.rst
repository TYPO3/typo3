.. include:: /Includes.rst.txt

.. _breaking-102440-1700638677:

========================================================
Breaking: #102440 - EXT:t3editor merged into EXT:backend
========================================================

See :issue:`102440`

Description
===========

TYPO3 comes with a code editor extension called "t3editor" for a long time.
Since then, the extension was always optional. When the extension is installed,
selected text areas are converted to code editors based on CodeMirror.

The optional extension has been merged into `EXT:backend`, making the code
editor always available.


Impact
======

An integrator cannot optionally install the code editor anymore as it's part of
the mandatory "backend" extension now.

By default, this affects the following occurrences:

* TCA: `be_groups.TSconfig`
* TCA: `be_users.TSconfig`
* TCA: `pages.TSconfig`
* TCA: `sys_template.constants`
* TCA: `sys_template.config`
* TCA: `tt_content.bodytext`, if the content element is of type "HTML"
* EXT:filelist: edit file content
* Composer status view in Extension Manager

Also, checks whether the extension is installed via
:php:`\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('t3editor')`
are now obsolete.


Affected installations
======================

All installations are affected.


Migration
=========

Extension checks using :php:`\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('t3editor')`
don't have an effect anymore and must get removed.

In addition, please see :ref:`deprecation-102440-1700638677`.


.. index:: Backend, JavaScript, PHP-API, NotScanned, ext:t3editor
