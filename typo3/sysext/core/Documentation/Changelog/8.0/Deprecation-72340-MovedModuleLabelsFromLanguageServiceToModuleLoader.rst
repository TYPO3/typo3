
.. include:: /Includes.rst.txt

=============================================================================
Deprecation: #72340 - Moved moduleLabels from LanguageService to ModuleLoader
=============================================================================

See :issue:`72340`

Description
===========

Labels for registered modules were previously stored within the LanguageService class. The logic has
been moved to the ModuleLoader class. The method `LanguageService->addModuleLabels()` and the
property `LanguageService->moduleLabels` have been marked as deprecated.


Impact
======

Calling `LanguageService->addModuleLabels()` will trigger a deprecation log entry. The property
`LanguageService->moduleLabels` will no longer contain the expected values anymore.


Affected Installations
======================

Any installation with extensions that directly access the labels for a given module.


Migration
=========

Use `ModuleLoader->addLabelsForModule()` and `ModuleLoader->getLabelsForModule` instead.

.. index:: PHP-API
