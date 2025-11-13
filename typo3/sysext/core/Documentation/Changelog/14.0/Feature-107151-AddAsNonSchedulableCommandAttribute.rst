.. include:: /Includes.rst.txt

.. _feature-107151-1763044849:

====================================================================================================
Feature: #107151 - Add AsNonSchedulableCommand attribute to enhance AsCommand CLI Command attributes
====================================================================================================

See :issue:`107151`

Description
===========

With :issue:`101567` the usage of Symfony's `#[AsCommand]` attribute
has been introduced, which allows to configure a CLI Symfony Command
name and description (and some other options).

It lacked TYPO3's custom implementation for the `schedulable`
option, which allows to flag a CLI command to not be able to be
scheduled in EXT:scheduler.

This required to list such a `schedulable: false` command in the
`Services.yaml` / `Services.php` definition.

For this, the PHP attribute
:php:`\TYPO3\CMS\Core\Attribute\AsNonSchedulableCommand` has
been created. Any Symfony Command can implement use empty attribute
so that the automatic Scheduler registry will ignore commands
with this tagged class.

By default, a Symfony Command remains schedulable using the regular
Symfony attribute, to prevent redundancy and utilize the new attribute
`#[AsNonSchedulableCommand]` only on top of that.

Another upside of this is that an IDE like PhpStorm is capable
of showing all usages of that attribute inside a project.

Impact
======

Developers can now fully embrace using the Symfony `#[AsCommand]`
attribute and still be able to declare a non-schedulable execution
within the scope of the same class, without any service registration.

This is achieved by using the `#[AsNonSchedulableCommand]` in addition
to the `#[AsCommand]` attribute.


.. index:: Backend, PHP-API, ext:core
