============================================================================================
Deprecation: #73067 - Deprecate GeneralUtility::requireOnce and  GeneralUtility::requireFile
============================================================================================

Description
===========

The following methods from ``TYPO3\CMS\Core\Utility\GeneralUtility`` have been deprecated.

``GeneralUtility::requireOnce()``
``GeneralUtility::requireFile()``


Affected Installations
======================

Instances which use one of the aforementioned methods.


Migration
=========

Use native require_once if needed, e.g. if autoloading does not work.
