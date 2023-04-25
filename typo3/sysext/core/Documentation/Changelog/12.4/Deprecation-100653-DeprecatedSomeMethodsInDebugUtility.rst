.. include:: /Includes.rst.txt

.. _deprecation-100653-1681805677:

==============================================================
Deprecation: #100653 - Deprecated some methods in DebugUtility
==============================================================

See :issue:`100653`

Description
===========

The following methods in :php:`\TYPO3\CMS\Core\Utility\DebugUtility` have been
marked as deprecated:

* :php:`debugInPopUpWindow()`
* :php:`debugRows()`
* :php:`printArray()`

While :php:`debugRows()` and :php:`printArray()` duplicate already existing
methods, :php:`debugInPopUpWindow()` is discouraged to use as either external
debuggers, e.g. Xdebug or :php:`\TYPO3\CMS\Extbase\Utility\DebuggerUtility` may
be used instead.


Impact
======

Calling any of the aforementioned methods will trigger deprecation log entries.


Affected installations
======================

Instances using any of the aforementioned methods are affected.

The extension scanner will find and report usages.


Migration
=========

In case of :php:`debugRows()`, the identical method :php:`debug()` can be used.
The method :php:`printArray()` can be replaced with :php:`viewArray()`. However,
the former method directly outputs the contents, which is not the case with
:php:`viewArray()`.

The method :php:`debugInPopUpWindow()` is deprecated without a direct
replacement, consider using an external debugger or
:php:`\TYPO3\CMS\Extbase\Utility\DebuggerUtility::var_dump()` instead.

.. index:: PHP-API, FullyScanned, ext:core
