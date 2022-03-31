
.. include:: /Includes.rst.txt

====================================================
Deprecation: #68760 - Deprecate class ModuleSettings
====================================================

See :issue:`68760`

Description
===========

In older TYPO3 versions `t3lib_modSettings` (as ModuleSettings class was called before) was used to save the current
settings of backend modules. This kind of settings is nowadays stored in backend users uc array.
For that reason ModuleSettings is now marked for removal in TYPO3 CMS 8.

Impact
======

Using `ModuleSettings` will trigger a deprecation log entry.


Affected Installations
======================

Any TYPO3 installation with custom extensions using this class and its methods.


Migration
=========

Remove usage of this class from custom extensions.


.. index:: PHP-API, Backend
