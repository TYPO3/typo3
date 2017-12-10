.. include:: ../../Includes.txt

============================================================================
Feature: #81330 - Trait to migrate public access to protected by deprecation
============================================================================

See :issue:`81330`
See Important-81330-DealingWithPropertiesThatAreMigratedToProtected.rst

Description
===========

A new PHP trait (:php:`PublicPropertyDeprecationTrait`) is added to support the smooth migration of public properties to
a protected or private state of a property. By using this trait, deprecation warnings are thrown until the next
major TYPO3 version.

Impact
======

Instead of creating a breaking change by setting a public property to protected, the migration can now by done by the
softer path of deprecation. This will encourage the encapsulation of core classes that still have public properties.
By reaching encapsulation, refactoring becomes a lot more easy. The core can be modernized more quickly with less
issues for developers.

.. index:: PHP-API
