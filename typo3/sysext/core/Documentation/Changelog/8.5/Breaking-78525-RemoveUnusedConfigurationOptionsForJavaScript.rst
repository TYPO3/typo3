.. include:: /Includes.rst.txt

======================================================================
Breaking: #78525 - Removed unused configuration options for JavaScript
======================================================================

See :issue:`78525`

Description
===========

Removed all options that are not used anymore from :js:`TYPO3.configuration` in JavaScript context.

* :js:`TYPO3.configuration.moduleMenuWidth`
* :js:`TYPO3.configuration.topBarHeight`


Impact
======

Both settings are not available anymore in JavaScript under :js:`TYPO3.configuration`.


Affected Installations
======================

Any installation that uses one of the mentioned options.


Migration
=========

No migration.

.. index:: Backend, JavaScript
