============================================
Deprecation: #76370 - Deprecate CacheFactory
============================================

Description
===========

Class :php:``CacheFactory`` has been deprecated.


Impact
======

The class is no longer used or instantiated by the core.
Instantiating the class will trigger a deprecation log entry.


Affected Installations
======================

TYPO3 instances and extensions typically make no use of this internal class.


Migration
=========

Nothing notable, do not use this class anymore.