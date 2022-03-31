.. include:: /Includes.rst.txt

===================================================================
Deprecation: #83905 - TypoScriptFrontendController->page_cache_reg1
===================================================================

See :issue:`83905`

Description
===========

Property :php:`TypoScriptFrontendController->page_cache_reg1` has been marked as deprecated.


Impact
======

Setting this property triggers a deprecation warning.


Affected Installations
======================

This property was of very little use ever since, it is unlikely an instance runs an extension consuming it.
The extension scanner will find usages.


Migration
=========

Use method :php:`TypoScriptFrontendController->addCacheTags()` to influence page cache tagging.

.. index:: Frontend, PHP-API, FullyScanned
