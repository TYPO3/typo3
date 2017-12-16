
.. include:: ../../Includes.txt

====================================================================
Breaking: #61781 - include_once array in ClickMenuController removed
====================================================================

See :issue:`61781`

Description
===========

The include_once array of the ClickMenuController, which is filled with paths from the
:code:`$GLOBALS['TBE_MODULES_EXT']['xMOD_alt_clickmenu']['extendCMclasses']['path']` setting, has been removed.

Impact
======

Extension classes relying on the :code:`$GLOBALS['TBE_MODULES_EXT']['xMOD_alt_clickmenu']['extendCMclasses']['path']` registration for autoloading will not be loaded anymore.


Affected installations
======================

A TYPO3 instance is affected if a 3rd party extension uses the removed class loading registration method.


Migration
=========

All classes are autoloaded automatically by TYPO3 CMS Core.
