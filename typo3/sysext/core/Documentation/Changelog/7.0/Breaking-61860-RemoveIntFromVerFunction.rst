===========================================================
Breaking: #61860 - deprecated function int_from_ver removed
===========================================================

Description
===========

Function :php:`int_from_ver()` from :php:`\TYPO3\CMS\Core\Utility\GeneralUtility` has been removed.


Impact
======

Extensions that still use the function :php:`int_from_ver()` won't work.


Affected installations
======================

A TYPO3 instance is affected if a 3rd party extension uses the removed function.


Migration
=========

Replace the usage of the removed function with :php:`\TYPO3\CMS\Core\Utility\VersionNumberUtility::convertVersionNumberToInteger()`