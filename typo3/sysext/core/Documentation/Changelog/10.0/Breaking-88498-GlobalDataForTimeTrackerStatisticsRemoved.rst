.. include:: /Includes.rst.txt

=================================================================
Breaking: #88498 - Global data for TimeTracker statistics removed
=================================================================

See :issue:`88498`

Description
===========

The TimeTracker used some global variables to store :php:`microtime()` when a Frontend request was started
and ended, as information for the Admin Panel and as HTTP Header, if debug mode is enabled for Frontend.

This information is now encapsulated within the TimeTracker object, making the following global variables
obsolete:

* :php:`$GLOBALS['TYPO3_MISC']['microtime_start']`
* :php:`$GLOBALS['TYPO3_MISC']['microtime_end']`
* :php:`$GLOBALS['TYPO3_MISC']['microtime_BE_USER_start']`
* :php:`$GLOBALS['TYPO3_MISC']['microtime_BE_USER_end']`

This also results in having :php:`$GLOBALS['TYPO3_MISC']` to not be set anymore.


Impact
======

Accessing the global variables will trigger a PHP :php:`E_WARNING` error, as they do not exist anymore.


Affected Installations
======================

Any TYPO3 installation with an extension working with any of the global variables.


Migration
=========

Remove the usages and either use the newly introduced :php:`TimeTracker->finish()` to calculate data, or set
your own variables, if microtime is needed.

.. index:: PHP-API, FullyScanned
