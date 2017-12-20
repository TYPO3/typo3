.. include:: ../../Includes.txt

==========================================================
Deprecation: #80440 - EXT:lowlevel ArrayBrowser->wrapValue
==========================================================

See :issue:`80440`

Description
===========

The method :php:`ArrayBrowser->wrapValue` in EXT:lowlevel has been marked as deprecated, since the sole
logic was to wrap the incoming string into :php:`htmlspecialchars()`.


Impact
======

Calling the method will trigger a deprecation warning.


Affected Installations
======================

Any TYPO3 installation using the EXT:lowlevel ArrayBrowser class in a custom extension.


Migration
=========

Remove the call to the method and directly use :php:`htmlspecialchars()` instead.

.. index:: PHP-API, ext:lowlevel
