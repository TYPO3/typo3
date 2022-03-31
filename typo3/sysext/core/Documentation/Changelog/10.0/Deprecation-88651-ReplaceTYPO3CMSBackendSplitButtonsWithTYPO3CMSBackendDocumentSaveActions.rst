.. include:: /Includes.rst.txt

=======================================================================================================
Deprecation: #88651 - Replace TYPO3/CMS/Backend/SplitButtons with TYPO3/CMS/Backend/DocumentSaveActions
=======================================================================================================

See :issue:`88651`

Description
===========

Since FormEngine doesn't use split buttons anymore with TYPO3 v9, the JavaScript module
:js:`TYPO3/CMS/Backend/SplitButtons` has been replaced with :js:`TYPO3/CMS/Backend/DocumentSaveActions`.


Impact
======

Loading :js:`TYPO3/CMS/Backend/SplitButtons` will trigger a deprecation log entry in the browser's console.


Affected Installations
======================

All 3rd party extensions using :js:`TYPO3/CMS/Backend/SplitButtons` are affected.


Migration
=========

Use :js:`TYPO3/CMS/Backend/DocumentSaveActions` instead. Since the module is a singleton, the instance can be fetched by
calling :js:`DocumentSaveActions.getInstance()`.

.. index:: Backend, JavaScript, NotScanned, ext:backend
