.. include:: /Includes.rst.txt

================================================
Deprecation: #92607 - GeneralUtility::uniqueList
================================================

See :issue:`92607`

Description
===========

Since longer than a decade, the :php:`GeneralUtility::uniqueList()` method does not
accept an :php:`array` as first argument anymore. The second
parameter is unused just as long. Both throw an :php:`InvalidArgumentException` upon usage.

As the method doesn't belong to :php:`GeneralUtility` at all, a new refactored
version was added to :php:`StringUtility`. Therefore, the exceptions were removed
along with the unused second parameter. The first parameter is now type hinted
:php:`string` and the return type :php:`string` was added. The
PHPDoc was updated accordingly.


Impact
======

Calling the method will trigger a PHP :php:`E_USER_DEPRECATED` error.



Affected Installations
======================

TYPO3 installations with custom third-party extensions calling this method.


Migration
=========

Use the new :php:`StringUtility::uniqueList()` method instead and ensure you
pass a valid string as first argument and omit the second argument.

.. index:: PHP-API, FullyScanned, ext:core
