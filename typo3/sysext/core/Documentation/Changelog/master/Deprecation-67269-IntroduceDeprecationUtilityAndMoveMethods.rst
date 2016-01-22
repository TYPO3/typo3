===================================================================
Deprecation: #67269 - Introduce DeprecationUtility and move methods
===================================================================

Description
===========

The following methods have been marked as deprecated and moved into DeprecationUtility

* ``GeneralUtility::deprecationLog``
* ``GeneralUtility::logDeprecatedFunction``
* ``GeneralUtility::getDeprecationLogFileName``


Impact
======

Calling this method directly will trigger a deprecation log entry.


Affected Installations
======================

Any TYPO3 instance using this methods.


Migration
=========

Use one of the following methods:

* ``DeprecationUtility::logMessage``
* ``DeprecationUtility::logFunction``
* ``DeprecationUtility::getDeprecationLogFileName``
