.. include:: ../../Includes.txt

===========================================================
Deprecation: #92607 - Deprecated GeneralUtility::uniqueList
===========================================================

See :issue:`92607`

Description
===========

Since over a decade, the :php:`GeneralUtility::uniqueList()` method does not
accept an :php:`array` as first argument anymore. Furthermore the second
parameter was since then not longer be used. Both have thrown an
:php:`InvalidArgumentException`.

As the method doesn't belong to :php:`GeneralUtility` at all, a new refactored
version was added to :php:`StringUtility`. Therefore, the exceptions were removed
along with the unused second parameter. The first parameter is now type hinted
:php:`string` and the return type :php:`string` was added. Furthermore, the
PHPDoc was updated accordingly.


Impact
======

Calling the method will trigger a PHP deprecation notice.



Affected Installations
======================

TYPO3 installations with custom third-party extensions calling this method.


Migration
=========

Use the new :php:`StringUtility::uniqueList()` method instead and ensure you
pass a valid string as first argument and ommit the second argument.

.. index:: PHP-API, FullyScanned, ext:core
