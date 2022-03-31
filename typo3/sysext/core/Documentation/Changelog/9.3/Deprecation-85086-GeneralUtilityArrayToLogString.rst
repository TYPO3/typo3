.. include:: /Includes.rst.txt

========================================================
Deprecation: #85086 - GeneralUtility::arrayToLogString()
========================================================

See :issue:`85086`

Description
===========

The method :php:`GeneralUtility::arrayToLogString()`, responsible for formatting an array to a string
ready for logging or output, has been marked as deprecated.


Impact
======

Calling the method directly will trigger a PHP :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

Any TYPO3 installation with third-party extensions using this method.


Migration
=========

For logging purposes, switch to PSR-3 compatible logging where a log-writer is taking care of outputting / storing
this information properly.

For other purposes, like CLI-command output, it is recommended to implement this functionality directly in the
corresponding CLI command.

.. index:: CLI, PHP-API, FullyScanned, ext:core
