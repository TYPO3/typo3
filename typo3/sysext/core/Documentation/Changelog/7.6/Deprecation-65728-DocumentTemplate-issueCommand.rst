
.. include:: ../../Includes.txt

======================================================
Deprecation: #65728 - DocumentTemplate->issueCommand()
======================================================

See :issue:`65728`

Description
===========

Method `TYPO3\CMS\Backend\Template\DocumentTemplate::issueCommand()` has been marked as deprecated.


Affected Installations
======================

Instances with custom backend modules that use this method.


Migration
=========

Use `TYPO3\CMS\Backend\Utility\BackendUtility::getLinkToDataHandlerAction()` instead.
