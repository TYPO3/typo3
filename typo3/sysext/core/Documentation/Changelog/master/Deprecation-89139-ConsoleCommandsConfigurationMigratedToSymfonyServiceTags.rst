.. include:: ../../Includes.txt

=====================================================================================
Deprecation: #89139 - Console Commands configuration migrated to Symfony service tags
=====================================================================================

See :issue:`89139`

Description
===========

The console command configuration file format :php:`Configuration/Commands.php`
has been deprecated in favor of the dependency injection service tag
`console.command`. The tag allows to configure dependency injection and
command registration in one single location.


Impact
======

Providing a command configuration in :php:`Configuration/Commands.php` will
trigger a deprecation warning when the respective commands have not already
been defined via dependency injection service tags.

Extensions that provide both, the deprecated configuration file and service
tags, will not trigger a deprecation message in order to allow extensions to
support multiple TYPO3 major versions.


Affected Installations
======================

TYPO3 installations with custom extensions that configure symfony console commands
via :php:`Configuration/Commands.php` and have not been migrated to add symfony
service tags.


Migration
=========

Add the `console.command` tag to command classes. Use the tag attribute `command`
to specify the command name. The optional tag attribute `schedulable` may be set
to false to exclude the command from the TYPO3 scheduler.

.. code-block:: yaml

    services:
      _defaults:
        autowire: true
        autoconfigure: true
        public: false

      MyVendor\MyExt\Commands\FooCommand
        tags:
          - name: 'console.command',
            command: 'my:command'
            schedulable: false

Command aliases are to be configured as separate tags.
The optonal tag attribute `alias` should be set to true for alias commands.

.. code-block:: yaml

      MyVendor\MyExt\Commands\BarCommand
        tags:
          - name: 'console.command'
            command: 'my:bar' }
          - name: 'console.command'
            command: 'my:old-bar-command'
            alias: true
            schedulable: false

.. index:: CLI, PHP-API, PartiallyScanned, ext:core
