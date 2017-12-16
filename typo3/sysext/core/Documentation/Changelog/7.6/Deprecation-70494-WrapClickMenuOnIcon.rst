
.. include:: ../../Includes.txt

=============================================================
Deprecation: #70494 - DocumentTemplate->wrapClickMenuOnIcon()
=============================================================

See :issue:`70494`

Description
===========

Method `TYPO3\CMS\Backend\Template\DocumentTemplate::wrapClickMenuOnIcon()` has been marked as deprecated.


Affected Installations
======================

Instances with custom backend modules that use this method.


Migration
=========

Use `TYPO3\CMS\Backend\Utility\BackendUtility::wrapClickMenuOnIcon()` instead.
