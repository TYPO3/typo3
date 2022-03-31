.. include:: /Includes.rst.txt

========================================================
Deprecation: #88432 - Replaced md5.js with an AMD module
========================================================

See :issue:`88432`

Description
===========

The file :file:`md5.js` used to generate a MD5 hash in JavaScript has been marked as deprecated and replaced by an AMD module
:js:`TYPO3/CMS/Backend/Hashing/Md5`.


Impact
======

Using the global function `MD5()` will trigger a deprecation log entry in the browser's console.


Affected Installations
======================

All installations using third party extensions that use :js:`MD5()` of :file:`md5.js` are affected.


Migration
=========

Load the AMD module :js:`TYPO3/CMS/Backend/Hashing/Md5` via RequireJS as `Md5` and call :js:`Md5.hash()` instead.

.. index:: Backend, JavaScript, NotScanned, ext:backend
