.. include:: ../../Includes.txt

=====================================================
Deprecation: #78650 - TemplateService->splitConfArray
=====================================================

See :issue:`78650`

Description
===========

The method :php:`TemplateService->splitConfArray` which has been used for building the "optionSplit"
functionality has been marked as deprecated.

The method is now moved to a new class called :php:`TypoScriptService`, effectively removing the
dependency on :php:`$TSFE->tmpl` within a ContentObject.


Impact
======

Calling :php:`TemplateService->splitConfArray` will throw a deprecation warning.


Affected Installations
======================

Any installation using an extension that calls this method.


Migration
=========

Use the new method :php:`TypoScriptService->explodeConfigurationForOptionSplit` instead.

.. index:: PHP-API, TypoScript, Frontend
