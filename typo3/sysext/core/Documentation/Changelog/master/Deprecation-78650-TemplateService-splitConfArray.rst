.. include:: ../../Includes.txt

=====================================================
Deprecation: #78650 - TemplateService->splitConfArray
=====================================================

See :issue:`78650`

Description
===========

The method `TemplateService->splitConfArray` which used for building the "optionSplit" functionality
has been marked as deprecated.

The method is now moved to a new class called `TypoScriptService`, effectively removing the
dependency on `$TSFE->tmpl` within a ContentObject.


Impact
======

Calling `TemplateService->splitConfArray` will throw a deprecation warning.


Affected Installations
======================

Any installation using an extension that calls this method.


Migration
=========

Use the new method `TypoScriptService->explodeConfigurationForOptionSplit` instead.

.. index:: PHP-API, TypoScript
