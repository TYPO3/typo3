.. include:: ../../Includes.txt

===============================================================
Deprecation: #95351 - Custom JSWindow options in HMENU settings
===============================================================

See :issue:`95351`

Description
===========

The common HMENU settings for each HMENU level `JSWindow` (including
subproperties) and `target` with a value such as `target = 200x300`, to be set
on e.g. TMENU properties have been marked as deprecated.

Examples:

page.123 = HMENU
page.123.1 = TMENU
page.123.1.JSWindow = 1
page.123.1.JSWindow.params = width=200,height=300,status=0,menubar=0

page.123 = HMENU
page.123.1 = TMENU
page.123.1.target = 200x300


Impact
======

Calling a frontend page with a HMENU and JSwindow popups will trigger a
PHP deprecation warning.


Affected Installations
======================

TYPO3 installations with a HMENU and JSwindow settings which are configured
via TypoScript, which is highly unlikely in 2021.


Migration
=========

Use an external JavaScript file with an event listener to achieve the same
functionality.

.. index:: Frontend, TypoScript, NotScanned, ext:frontend
