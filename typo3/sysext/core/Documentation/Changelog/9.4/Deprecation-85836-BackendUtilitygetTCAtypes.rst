.. include:: ../../Includes.txt

=================================================
Deprecation: #85836 - BackendUtility::getTCAtypes
=================================================

See :issue:`85836`

Description
===========

The method :php:`BackendUtility::getTCAtypes()` has been marked as deprecated and will be removed in TYPO3 v10.


Impact
======

Calling the mentioned method will trigger a PHP :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

Third party code which accesses the method.


Migration
=========

No migration available.

.. index:: Backend, FullyScanned, ext:backend
