.. include:: ../../Includes.txt

=====================================================================
Deprecation: #82702 - Second argument of GeneralUtility::mkdir_deep()
=====================================================================

See :issue:`82702`

Description
===========

The second option of :php:`TYPO3\CMS\Core\Utility\GeneralUtility::mkdir_deep()` has been marked
as deprecated.


Impact
======

Calling this method with a second argument which is not empty, will trigger a deprecation entry.


Affected Installations
======================

Any installation with a third-party extension calling this method with two arguments.


Migration
=========

Instead of calling `GeneralUtility::mkdir_deep(PATH_site . 'typo3temp/', 'myfolder');` the simple
syntax GeneralUtility::mkdir_deep(PATH_site . 'typo3temp/myfolder'); can be used directly, also
in TYPO3 v8 and before already.

.. index:: PHP-API, FullyScanned