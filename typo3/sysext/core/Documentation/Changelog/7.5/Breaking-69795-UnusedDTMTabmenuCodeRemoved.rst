
.. include:: ../../Includes.txt

==================================================
Breaking: #69795 - Unused DTM Tabmenu code removed
==================================================

See :issue:`69795`

Description
===========

All DynTabMenu JavaScript and CSS code which was previously used to render Tab
Menus in the TYPO3 Backend has been removed without substitution.


Impact
======

All logic that requires EXT:backend/Resources/Public/JavaScript/tabmenu.js
directly and/or use the JavaScript code of `DTM_activate()` or `DTM_toggle()`
directly have been removed.


Affected Installations
======================

TYPO3 Installations with custom extensions that use the logic mentioned above.


Migration
=========

Use ModuleTemplate::getDynamicTabMenu() directly to use the Bootstrap-based API
shipped with the TYPO3 Core.
