====================================================================
Breaking: #61781 - include_once array in ClickMenuController removed
====================================================================

Description
===========

The include_once array of the ClickMenuController, which is filled with paths from the
$GLOBALS['TBE_MODULES_EXT']['xMOD_alt_clickmenu']['extendCMclasses']['path'] setting, is removed.

Impact
======

Extension classes relying on the $GLOBALS['TBE_MODULES_EXT']['xMOD_alt_clickmenu']['extendCMclasses']['path'] registration
for autoloading will not be loaded anymore.


Affected installations
======================

A TYPO3 instance is affected if a 3rd party extension uses the removed class loading registration method.


Migration
=========

All classes are autoloaded automatically by TYPO3 CMS Core.
