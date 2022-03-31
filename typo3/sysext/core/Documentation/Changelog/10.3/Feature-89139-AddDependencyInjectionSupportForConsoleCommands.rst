.. include:: /Includes.rst.txt

=======================================================================
Feature: #89139 - Add dependency injection support for console commands
=======================================================================

See :issue:`89139`

Description
===========

Support for dependency injection in console commands has been added.

Command dependencies can now be injected via constructor or other injection techniques.
Therefore, a new dependency injection tag :yaml:`console.command` has been added.
Commands tagged with :yaml:`console.command` are lazy loaded. That means they will only be
instantiated when they are actually executed, when the `help` subcommand is executed,
or when available schedulable commands are iterated.

The legacy command definition format :file:`Configuration/Commands.php` has been marked as deprecated.


Impact
======

It is recommended to configure dependency injection tags for all commands, as the legacy command
definition format :file:`Configuration/Commands.php` will be removed in TYPO3 v11.

Commands that have been configured via :yaml:`console.command` tag override legacy commands from
:file:`Configuration/Commands.php` without triggering a PHP :php:`E_USER_DEPRECATED` error for those commands.
Backwards compatibility with older TYPO3 version can be achieved by specifying both variants,
legacy configuration in :file:`Configuration/Commands.php` and new configuration via
:yaml:`console.command` tag.


Usage
=====

Add the :yaml:`console.command` tag to command classes.
Use the tag attribute :yaml:`command` to specify the command name.
The optional tag attribute :yaml:`schedulable` may be set to false
to exclude the command from the TYPO3 scheduler.

:file:`your_extension/Configuration/Services.yaml`

.. code-block:: yaml

    services:
      _defaults:
        autowire: true
        autoconfigure: true
        public: false

      MyVendor\MyExt\Command\FooCommand:
        tags:
          - name: 'console.command'
            command: 'my:command'
            schedulable: false

Command aliases are to be configured as separate tags.
The optional tag attribute :yaml:`alias` should be set to true for alias commands.

.. code-block:: yaml

      MyVendor\MyExt\Command\BarCommand:
        tags:
          - name: 'console.command'
            command: 'my:bar'
          - name: 'console.command'
            command: 'my:old-bar-command'
            alias: true
            schedulable: false


.. index:: CLI, PHP-API, ext:core
