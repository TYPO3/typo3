.. include:: ../../Includes.txt

===============================================================
Breaking: #37180 - ExtDirectDebug and $GLOBALS['error'] removed
===============================================================

See :issue:`37180`

Description
===========

The class :php:`TYPO3\CMS\Core\ExtDirect\ExtDirectDebug` has been removed and within the change, also the usage of the
global variable :php:`$GLOBALS['error']` has been removed.

The following global methods are removed as well:

- :php:`debugBegin()`
- :php:`debugEnd()`


Impact
======

Accessing the class :php:`TYPO3\CMS\Core\ExtDirect\ExtDirectDebug`, the global variable :php:`$GLOBALS['error']` or the
global methods :php:`debugBegin()` and :php:`debugEnd()` will lead to an exception.


Affected Installations
======================

All instances, that use the mentioned class, global methods or access the global variable.
The extension scanner of the install tool will find affected extensions.

.. index:: PHP-API, Backend, FullyScanned
