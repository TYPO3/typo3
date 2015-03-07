===============================================
Deprecation: #47712 - Deprecate old Locking API
===============================================

Description
===========

The old class ``\TYPO3\CMS\Core\Locking\Locker`` is deprecated.

The configuration option [SYS][lockingMode] is deprecated and only affects the old Locker class, which is
unused in the Core now.

Moreover two unused methods of \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController are deprecated:
 * acquirePageGenerationLock()
 * releasePageGenerationLock()


Impact
======

Using the old class will trigger deprecation log messages.

Migration
=========

Use the new Locking Service API instead.
