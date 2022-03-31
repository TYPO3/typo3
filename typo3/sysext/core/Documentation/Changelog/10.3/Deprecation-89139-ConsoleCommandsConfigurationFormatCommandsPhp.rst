.. include:: /Includes.rst.txt

========================================================================
Deprecation: #89139 - Console Commands configuration format Commands.php
========================================================================

See :issue:`89139`

Description
===========

The console command configuration file format :php:`Configuration/Commands.php`
has been marked as deprecated in favor of the symfony service tag
:yaml:`console.command`. The tag allows to configure dependency injection and
command registration in one single location.


Impact
======

Providing a command configuration in :php:`Configuration/Commands.php` will
trigger a PHP :php:`E_USER_DEPRECATED` error when the respective commands have not already
been defined via symfony service tags.

Extensions that provide both, the deprecated configuration file and service
tags, will not trigger a PHP :php:`E_USER_DEPRECATED` error in order to allow extensions to
support multiple TYPO3 major versions.


Affected Installations
======================

TYPO3 installations with custom extensions that configure symfony console commands
via :php:`Configuration/Commands.php` and have not been migrated to add symfony
service tags.


Migration
=========

Add the :yaml:`console.command` tag to command classes. Use the tag attribute :yaml:`command`
to specify the command name. The optional tag attribute :yaml:`schedulable` may be set
to false to exclude the command from the TYPO3 scheduler.

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

.. index:: CLI, PHP-API, PartiallyScanned, ext:core
