
.. include:: ../../Includes.txt

=====================================================
Deprecation: #37171 - Deprecate t3editor->isEnabled()
=====================================================

See :issue:`37171`

Description
===========

TYPO3\CMS\T3editor\T3editor->isEnabled() has been marked as deprecated and should not be called anymore.


Impact
======

The method isEnabled() always returns TRUE and will be removed with TYPO3 CMS 8.


Affected Installations
======================

Any installation using third-party code that works with t3editor and calls isEnabled().


Migration
=========

The method call should be removed.
