.. include:: /Includes.rst.txt

=====================================================================
Deprecation: #90007 - Global constants TYPO3_version and TYPO3_branch
=====================================================================

See :issue:`90007`

Description
===========

Two of the most "stable" global constants in the TYPO3 Core - :php:`TYPO3_version` and :php:`TYPO3_branch` have been marked as deprecated.

The change was mainly driven by the necessity to minimize runtime-generated constants in order to optimize performance, also for op-caching.

The same information is available in a new PHP class :php:`TYPO3\CMS\Core\Information\Typo3Version`, which also defines
the constants for backwards-compatibility reasons.


Impact
======

No PHP :php:`E_USER_DEPRECATED` error is triggered, however the constants will work during
TYPO3 v10 and TYPO3 v11, but will be removed with TYPO3 v12.


Affected Installations
======================

TYPO3 installations with custom extensions accessing the constants,
which is common for having extension support for multiple TYPO3 versions.


Migration
=========

It is highly recommended to use the :php:`Typo3Version` class instead of
the constants, as they will be removed in a future TYPO3 version.

Check the Extension Scanner in the Upgrade section of TYPO3 to see
if any extensions you use might be affected.

.. index:: PHP-API, FullyScanned, ext:core
