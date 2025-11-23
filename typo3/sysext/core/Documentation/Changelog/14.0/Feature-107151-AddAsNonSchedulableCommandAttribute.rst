..  include:: /Includes.rst.txt

..  _feature-107151-1763044849:

=========================================================================
Feature: #107151 - Add AsNonSchedulableCommand attribute for CLI commands
=========================================================================

See :issue:`107151`

Description
===========

With :issue:`101567` the usage of Symfony's `#[AsCommand]` attribute
has been introduced, which allows configuring a Symfony CLI command
with a corresponding name, description and further options.

It however lacked TYPO3's custom implementation of the `schedulable`
option, which allows flagging a CLI command to be not allowed to be
scheduled via the :guilabel:`Administration > Scheduler` backend module.

.. note::
   The top-level backend modules were renamed in TYPO3 v14.
   The module now called :guilabel:`Administration` was formerly named
   :guilabel:`System`, and the module now called :guilabel:`System` was formerly
   named :guilabel:`Admin Tools`.

   For details, see:
   `Feature: #107628 – Improved backend module naming and structure
   <https://docs.typo3.org/permalink/changelog:feature-107628-1729026000>`_.

This previously required tagging such a command with the
:yaml:`schedulable: false` tag attribute in the :file:`Services.yaml` or
:file:`Services.php` definition.

For this, the PHP attribute
:php-short:`\TYPO3\CMS\Core\Attribute\AsNonSchedulableCommand` has been
introduced. Any Symfony Command can use this empty attribute. The automatic
Scheduler registry will ignore any command with this tag.

By default, a Symfony Command remains schedulable using the regular Symfony
attribute. To prevent redundancy, the new attribute
:php:`#[AsNonSchedulableCommand]` should be used only on top of that.

Another advantage is that an IDE like PhpStorm is capable of showing all
usages of that attribute inside a project.

Impact
======

Developers can now fully embrace using the Symfony :php:`#[AsCommand]`
attribute and still be able to declare a non-schedulable execution within
the scope of the same class, without any service registration.

This is achieved by using the :php:`#[AsNonSchedulableCommand]` in addition
to the :php:`#[AsCommand]` attribute.

Example
=======

..  code-block:: php

    use Symfony\Component\Console\Attribute\AsCommand;
    use Symfony\Component\Console\Command\Command;
    use TYPO3\CMS\Core\Attribute\AsNonSchedulableCommand;

    #[AsCommand('myextension:import', 'Import data from external source')]
    #[AsNonSchedulableCommand]
    final class ImportCommand extends Command
    {
        // ...
    }

..  index:: Backend, PHP-API, ext:core
