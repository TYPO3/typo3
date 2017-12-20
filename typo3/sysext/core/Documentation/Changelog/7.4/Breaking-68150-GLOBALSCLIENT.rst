
.. include:: ../../Includes.txt

=====================================
Breaking: #68150 - $GLOBALS['CLIENT']
=====================================

See :issue:`68150`

Description
===========

The initialization of the `$GLOBALS['CLIENT']` variable has been dropped.


Impact
======

Extensions that use `$GLOBALS['CLIENT']` will cause a PHP notice or may not function properly any more.


Affected Installations
======================

Installations with extensions that use `$GLOBALS['CLIENT']` are affected.


Migration
=========

Extensions can still use `GeneralUtility::clientInfo()` API to retrieve the same information.


.. index:: PHP-API
