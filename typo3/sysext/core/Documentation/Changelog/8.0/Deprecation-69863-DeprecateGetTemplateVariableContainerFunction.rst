
.. include:: ../../Includes.txt

=====================================================================
Deprecation: #69863 - Deprecate getTemplateVariableContainer function
=====================================================================

See :issue:`69863`

Description
===========

`RenderingContext->getTemplateVariableContainer` has been marked as deprecated in
favor of getVariableProvider due to a changed concept for variable provisioning in
standalone fluid. It now does more than just contain variables so a change in naming
is necessary.


Impact
======

Calling this method directly will trigger a deprecation log entry.


Affected Installations
======================

Any TYPO3 instance using `getTemplateVariableContainer` method directly within an
extension or third-party code.


Migration
=========

Use `getVariableProvider` instead of `getTemplateVariableContainer`.

.. index:: PHP-API, Fluid
