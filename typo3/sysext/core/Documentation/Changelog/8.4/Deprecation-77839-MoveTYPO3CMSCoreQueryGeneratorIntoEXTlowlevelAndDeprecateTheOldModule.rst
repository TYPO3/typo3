.. include:: /Includes.rst.txt

=======================================================================================================
Deprecation: #77839 - Move TYPO3/CMS/Core/QueryGenerator into EXT:lowlevel and deprecate the old module
=======================================================================================================

See :issue:`77839`

Description
===========

The AMD module :js:`TYPO3/CMS/Core/QueryGenerator` have been deprecated.
The module have been renamed to :js:`TYPO3/CMS/Lowlevel/QueryGenerator` and moved into EXT:lowlevel.

Impact
======

Using the module will trigger a deprecation log message in the browser console.

Affected Installations
======================

Any TYPO3 installation using custom calls to :js:`TYPO3/CMS/Core/QueryGenerator`


Migration
=========

Use AMD module :js:`TYPO3/CMS/Lowlevel/QueryGenerator` instead.

.. index:: JavaScript, ext:lowlevel
