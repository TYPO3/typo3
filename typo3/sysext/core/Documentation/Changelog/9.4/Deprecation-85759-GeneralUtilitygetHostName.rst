.. include:: ../../Includes.txt

=================================================
Deprecation: #85759 - GeneralUtility::getHostName
=================================================

See :issue:`85759`

Description
===========

The method :php:`GeneralUtility::getHostName()` has been marked as deprecated and will be removed in TYPO3 v10.


Impact
======

Calling the mentioned method will trigger a PHP :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

Third party code which accesses the method.


Migration
=========

No migration available.

.. index:: PHP-API, FullyScanned
