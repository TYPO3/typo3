.. include:: /Includes.rst.txt

==================================================================
Breaking: #74533 - Throw exception if user function does not exist
==================================================================

See :issue:`74533`

Description
===========

:php:`GeneralUtility::callUserFunction()` does now always throw an exception if the passed
user function does not exist or is not callable. The parameter `$errorMode` has been removed,
exceptions are now always thrown. The method should not be called with more than three arguments.


Impact
======

Calling a not existing or uncallable user function leads to an exception, breaking the page output.


Affected Installations
======================

All TYPO3 installations are affected.


Migration
=========

Remove or fix invalid `userFunc` calls registered in TypoScript and/or `ext_localconf.php`. Catch exceptions properly
with try/catch.

.. index:: PHP-API, PartiallyScanned
