.. include:: ../../Includes.txt

===========================================================
Deprecation: #85759 - Deprecate GeneralUtility::getHostName
===========================================================

See :issue:`85759`

Description
===========

The method :php:`GeneralUtility::getHostName` has been marked as deprecated and will be removed in TYPO3 v10. The method is not in use anymore by the TYPO3 core.


Impact
======

Calling the mentioned method will trigger a deprecation warning.


Affected Installations
======================

Third party code which accesses the method.


Migration
=========

No migration available.

.. index:: PHP-API, FullyScanned