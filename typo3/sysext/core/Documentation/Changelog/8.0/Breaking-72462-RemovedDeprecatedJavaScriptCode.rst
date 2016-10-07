
.. include:: ../../Includes.txt

=====================================================
Breaking: #72462 - Removed deprecated JavaScript code
=====================================================

See :issue:`72462`

Description
===========

Removed deprecated JavaScript code

The following JavaScript functions have been removed:

`showClickmenu_raw`
`Clickmenu.show`
`Clickmenu.populateData`
`ShortcutManager.createShortcut`
`jsfunc.tbe_editor.getBackendPath`


Impact
======

Using one of the methods above will result in JavaScript errors in the TYPO3 CMS backend.


Affected Installations
======================

Instances which use custom calls to one of the methods above.


Migration
=========

For `Clickmenu.show` use `TYPO3.ClickMenu` instead.
For `Clickmenu.populateData` use `TYPO3.ClickMenu` instead.
For `showClickmenu_raw` use `TYPO3.ClickMenu` instead.
For `ShortcutManager.createShortcut` use `TYPO3.ShortcutMenu` directly.

.. index:: JavaScript, Backend
