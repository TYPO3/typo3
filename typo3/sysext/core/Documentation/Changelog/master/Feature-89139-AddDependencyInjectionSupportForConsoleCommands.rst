.. include:: ../../Includes.txt

=======================================================================
Feature: #89139 - Add dependency injection support for console commands
=======================================================================

See :issue:`89139`

Description
===========

Support for dependency injection in console commands has been added.

Command dependencies can now be injected via constructor or other injection techniques.
Therefore a new dependency injection tag `console.command` has been added.
Commands tagged with `console.command` are lazy loaded. That means they will only be
instantiated when they are actually executed, when the `help` subcommand is executed,
or when available schedulable commands are iterated.

The legacy command definition format :php:`Confguration/Commands.php` has been deprecated.


Impact
======

It is recommended to configure dependency injection tags for all commands, as the legacy command
definition format :php:`Confguration/Commands.php` has been deprecated.

Commands that have been configured via `console.command` tag  override legacy commands from
:php:`Confguration/Commands.php` without throwing a deprecation error for those commands.
Backwards compatibility with older TYPO3 version can be achieved by specifying both variants,
legacy configuration in :php:`Confguration/Commands.php` and new configuration via
`console.command` tag.


Usage
=====

Add the `console.command` tag to command classes.
Use the tag attribute `command` to specify the command name.
The optional tag attribute `schedulable` may be set to false
to exclude the command from the TYPO3 scheduler.

.. code-block:: yaml

    services:
      _defaults:
        autowire: true
        autoconfigure: true
        public: false

      MyVendor\MyExt\Commands\FooCommand
        tags:
          - name: 'console.command'
            command: 'my:command'
            schedulable: false

Command aliases are to be configured as separate tags.
The optonal tag attribute `alias` should be set to true for alias commands.

.. code-block:: yaml

      MyVendor\MyExt\Commands\BarCommand
        tags:
          - name: 'console.command'
            command: 'my:bar'
          - name: 'console.command'
            command: 'my:old-bar-command'
            alias: true
            schedulable: false


.. index:: CLI, PHP-API, ext:core
