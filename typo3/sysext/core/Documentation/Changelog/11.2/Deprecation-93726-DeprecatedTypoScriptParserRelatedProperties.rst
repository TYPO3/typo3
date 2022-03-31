.. include:: /Includes.rst.txt

====================================================================
Deprecation: #93726 - Deprecated TypoScriptParser related properties
====================================================================

See :issue:`93726`

Description
===========

A cleanup of the backend 'Template' module leads to a deprecation
of some TypoScript parser related class properties:

* :php:`TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser->breakPointLN`
* :php:`TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser->parentObject`
* :php:`TYPO3\CMS\Core\TypoScript\TemplateService->ext_constants_BRP`
* :php:`TYPO3\CMS\Core\TypoScript\TemplateService->ext_config_BRP`


Impact
======

The properties are not handled any longer and will be dropped with TYPO3 v12.


Affected Installations
======================

It is very unlikely extensions used these properties since they were specific
to the backend 'Template' module and of little use otherwise.

The extension scanner will still find usages except the :php:`parentObject`
since this property name is too generic and would trigger too many false
positive matches.


Migration
=========

The functionality of these properties has been dropped.

.. index:: Backend, PHP-API, PartiallyScanned, ext:core
