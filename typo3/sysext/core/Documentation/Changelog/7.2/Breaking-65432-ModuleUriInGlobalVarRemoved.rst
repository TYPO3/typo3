
.. include:: /Includes.rst.txt

============================================================================
Breaking: #65432 - Storage of module URI in global variable has been removed
============================================================================

See :issue:`65432`

Description
===========

Previously the URI to a module which was dispatched through mod.php was stored
in a global variable `$GLOBALS['MCONF']['_']`.

In terms of cleanup of global variable usage and module configuration cleanup and streamlining,
this functionality has been removed without substitution.


Impact
======

Any backend module code which accesses `$GLOBALS['MCONF']['_']` to get the module URI will not work any more.


Affected installations
======================

TYPO3 CMS 7 installations using extensions with backend modules which use `$GLOBALS['MCONF']['_']`.


Migration
=========

Extension code needs to be changed in a way that the API `BackendUtility::getModuleUrl('module_name')` is used
instead of accessing `$GLOBALS['MCONF']['_']`.


.. index:: PHP-API, Backend
