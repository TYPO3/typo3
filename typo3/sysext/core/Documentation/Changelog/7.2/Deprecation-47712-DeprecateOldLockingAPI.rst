
.. include:: ../../Includes.txt

===============================================
Deprecation: #47712 - Deprecate old Locking API
===============================================

See :issue:`47712`

Description
===========

The old class `\TYPO3\CMS\Core\Locking\Locker` has been marked as deprecated.

The configuration option `[SYS][lockingMode]` is now marked as deprecated and only affects the old Locker class, which is
unused in the Core now.

Moreover two unused methods of `\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController` have been marked as deprecated:
 * acquirePageGenerationLock()
 * releasePageGenerationLock()


Impact
======

Using the old class will trigger deprecation log messages.


Migration
=========

Use the new Locking Service API instead.
