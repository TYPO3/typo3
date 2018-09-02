.. include:: ../../Includes.txt

=========================================================
Feature: #85991 - Exclude Symfony Commands from Scheduler
=========================================================

See :issue:`85991`

Description
===========

TYPO3's Scheduler system extension added a feature to call a Symfony / command repeatedly, however
it is very helpful if an extension author can decide to explicitly define a command to be
"non-schedulable" like the installation of an extension or the listing of syslog information which
act as helpers on the command line.

This feature is the equivalent to Extbase's `@cli` annotation for command controllers, and thus
finishes the Scheduler integration for Symfony commands in TYPO3.


Impact
======

A registered Symfony command can now have a new option :php:`schedulable` which can be set to
:php:`false` for commands that should only be executed specifically by TYPO3's CLI interface.

The default value is :php:`true` which means that every Symfony command can be used in the Scheduler.

An example file within :file:`EXT:myextension/Configuration/Commands.php` could look like this:

.. code-block:: php

   return [
       'admins:delete' => [
           'class' => \ACME\MyExtension\Command\DeleteAllAdministratorsCommand::class,
           'schedulable' => false,
       ]
   ];

The command could still be executed via :shell:`.../typo3 admins:delete` but not be set up as
Scheduler task in the TYPO3 backend.

.. index:: CLI, ext:scheduler
