.. include:: /Includes.rst.txt

===========================================
Feature: #93174 - Lazy console command list
===========================================

See :issue:`93174`

Description
===========

The TYPO3 command line utility :bash:`typo3/sysext/core/bin/typo3` has been adapted to
avoid instantiating all available console commands during the execution of the
default :bash:`typo3 list` command.

This enables commands to inject dependencies that require a fully booted system,
or a database connection, without causing the console command list to break or
slow down.

Options
-------

New tag properties for the :yaml:`console.command` dependency injection tag
have been added. The properties control the appearance of console commands in the
list output.

:`description`:  The description of the command (default: `''`).
:`hidden`:       Command will be hidden from `list` if `true` (default: `false`).

Example of a command registration that includes a description
-------------------------------------------------------------

The command list requires the description to be set next to the command
name in :file:`Services.yaml` in order for descriptions to be shown:

.. code-block:: yaml

    # Configuration/Services.yaml
    services:
      My\Namespace\Command\ExampleCommand:
        tags:
          - name: 'console.command'
            command: 'my:example'
            description: 'An example command that demonstrates some stuff'
            # not required, defaults to false
            hidden: false


Migration
=========

Extension authors should add the :yaml:`description` property to existing
:yaml:`console.command` dependency injection tags.
The call to :php:`$this->setDescription()` in :php:`Command::configure()` should
be removed, as the description, as defined in :file:`Services.yaml`, will be
injected into the command.


Impact
======

Extensions authors are now able to inject arbitrary dependencies in console
commands, without impacting the loading of the command list.

Integrators profit from a stable command list that is fast and always available,
even if a command is not instantiable or if it inadvertently contains too much
logic inside the command constructor.

.. index:: CLI, ext:core
