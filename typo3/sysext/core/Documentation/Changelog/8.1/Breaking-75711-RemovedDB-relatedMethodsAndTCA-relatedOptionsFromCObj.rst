
.. include:: ../../Includes.txt

===============================================================================
Breaking: #75711 - Removed DB-related methods and TCA-related options from cObj
===============================================================================

See :issue:`75711`

Description
===========

The following methods have been removed from `ContentObjectRenderer` without substitution:

* :php:`DBgetDelete()`
* :php:`DBgetUpdate()`
* :php:`DBgetInsert()`
* :php:`DBmayFEUserEdit()`
* :php:`DBmayFEUserEditSelect()`
* :php:`exec_mm_query()`
* :php:`exec_mm_query_uidList()`

The following TCA options have no effect anymore throughout the TYPO3 Core:

* :php:`$GLOBALS['TCA'][table]['ctrl']['fe_cruser_id']`
* :php:`$GLOBALS['TCA'][table]['ctrl']['fe_crgroup_id']`
* :php:`$GLOBALS['TCA'][table]['ctrl']['fe_admin_lock']`


Impact
======

Calling any of the methods above directly will trigger a PHP fatal error.


Affected Installations
======================

Any TYPO3 installation using DB-related Frontend Administration with the obsolete functionality.

.. index:: PHP-API, TCA, Database
