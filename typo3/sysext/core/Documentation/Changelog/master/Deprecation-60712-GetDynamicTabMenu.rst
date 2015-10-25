=============================================================
Deprecation: #70494 - DocumentTemplate->wrapClickMenuOnIcon()
=============================================================

Description
===========

Methods ``TYPO3\CMS\Backend\Template\DocumentTemplate::getDynamicTabMenu()`` and
``TYPO3\CMS\Backend\Template\DocumentTemplate::getDynTabMenuId()`` have been deprecated.


Affected Installations
======================

Instances with custom backend modules that use these methods.


Migration
=========

Use ``TYPO3\CMS\Backend\Utility\ModuleTemplate::getDynamicTabMenu()`` instead.
