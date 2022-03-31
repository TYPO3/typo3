.. include:: /Includes.rst.txt

================================================================================
Deprecation: #94137 - Switch behavior of ArrayUtility::arrayDiffAssocRecursive()
================================================================================

See :issue:`94137`

Description
===========

Despite its name, the method
:php:`\TYPO3\CMS\Core\Utility\ArrayUtility::arrayDiffAssocRecursive()`
mimics the behavior of :php:`array_diff_key()` and not of
:php:`array_diff_assoc()`.


Impact
======

The method has been adjusted to act like :php:`array_diff_assoc()`. As this is
considered being a breaking change, the behavior must be enabled explicitly by
passing a third parameter :php:`$useArrayDiffAssocBehavior` being true. If the
argument is either omitted or :php:`false`, the old behavior is kept but a
deprecation warning will be thrown.


Affected Installations
======================

Every 3rd party extension using
:php:`\TYPO3\CMS\Core\Utility\ArrayUtility::arrayDiffAssocRecursive()`
without its third argument being :php:`true` is affected.


Migration
=========

To keep the previous :php:`array_diff_key()` based behavior, use the introduced
method :php:`\TYPO3\CMS\Core\Utility\ArrayUtility::arrayDiffKeyRecursive()`.
To make use of the :php:`array_diff_assoc()` based behavior, which will become
the default behavior in TYPO3 v12, pass :php:`true` as the third argument.

.. index:: PHP-API, FullyScanned, ext:core
