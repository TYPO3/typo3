
.. include:: ../../Includes.txt

===========================================================
Deprecation: #60712 - DocumentTemplate->getDynamicTabMenu()
===========================================================

See :issue:`60712`

Description
===========

Methods `TYPO3\CMS\Backend\Template\DocumentTemplate::getDynamicTabMenu()` and
`TYPO3\CMS\Backend\Template\DocumentTemplate::getDynTabMenuId()` have been marked as deprecated.


Affected Installations
======================

Instances with custom backend modules that use these methods.


Migration
=========

Use `TYPO3\CMS\Backend\Utility\ModuleTemplate::getDynamicTabMenu()` instead.


.. index:: PHP-API, Backend
