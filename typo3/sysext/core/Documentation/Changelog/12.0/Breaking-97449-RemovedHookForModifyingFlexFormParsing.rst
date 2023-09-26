.. include:: /Includes.rst.txt

.. _breaking-97449:

===============================================================
Breaking: #97449 - Removed hook for modifying flex form parsing
===============================================================

See :issue:`97449`

Description
===========

The hook :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][FlexFormTools::class]['flexParsing']`,
supporting the four hook methods

- :php:`getDataStructureIdentifierPreProcess`
- :php:`getDataStructureIdentifierPostProcess`
- :php:`parseDataStructureByIdentifierPreProcess`
- :php:`parseDataStructureByIdentifierPostProcess`

has been removed in favor of four new dedicated :doc:`PSR-14 events <../12.0/Feature-97449-PSR-14EventsForModifyingFlexFormParsing>`.

Impact
======

Any hook implementation registered is not executed anymore in
TYPO3 v12.0+. The extension scanner will report possible usages.

Affected Installations
======================

TYPO3 installations with custom extensions using this hook.

Migration
=========

The hook is removed without deprecation in order to allow extensions
to work with TYPO3 v11 (using the hook) and v12+ (using the new event).

Use the PSR-14 events as an improved replacement.

.. index:: Backend, PHP-API, FullyScanned, ext:backend
