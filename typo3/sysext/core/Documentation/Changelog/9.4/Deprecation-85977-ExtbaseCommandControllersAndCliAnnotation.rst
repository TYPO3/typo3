.. include:: ../../Includes.txt

========================================================================================
Deprecation: #85977 - Extbase CLI functionality, Command Controllers and @cli Annotation
========================================================================================

See :issue:`85977`

Description
===========

TYPO3 supports Symfony Console commands natively since TYPO3 v8. Since TYPO3 v9.0 it is possible to also register
Symfony Console commands within scheduler, just like Extbase CommandControllers can be handled.

The main advantage of Symfony Console commands over Extbase Command Controllers is that they run very early in a
CLI context, not needing a database connection or other restrictions. On top comes better alias handling, CLI argument
and option handling.

As a trade-off, Extbase's ObjectManager and Configuration Handling and ORM is not available by default.

Since TYPO3 v9.4, it is possible to also register a Symfony Console command as "schedulable", to control the visibility
of a certain Symfony Command in Scheduler, making the PHPDoc annotation :php:`@cli` obsolete.

Impact
======

Using a CommandController via CLI will trigger a PHP :php:`E_USER_DEPRECATED` error. All other PHP classes for Extbase's CLI
functionality have been marked as deprecated, but will not trigger a PHP :php:`E_USER_DEPRECATED` error.

Using :php:`@cli` will also trigger a PHP :php:`E_USER_DEPRECATED` error. After the annotation has been removed from your commands, they will appear in the list of
executable commands in the scheduler module.


Affected Installations
======================

All installations that make use of command controllers or methods tagged with :php:`@cli`.


Migration
=========

Migrate custom commands within CommandControllers as symfony commands as TYPO3 Core does. Use specific argument
definitions on what parameters will be available.

See documentation https://symfony.com/doc/current/console.html and
https://docs.typo3.org/typo3cms/CoreApiReference/ApiOverview/BackendModules/CliScripts/Index.html for detailed
descriptions on how to write Console Commands and how to integrate them into TYPO3.

Think twice whether you need all of Extbase's power of Dependency Injection (ObjectManager / ObjectContainer) and Domain
Model / Repositories and ORM, or if native database queries will suit your task better.

If anything related to DataHandler and Backend permission handling is necessary, you should run
:php:`Bootstrap::initializeBackendAuthentication();`.

.. index:: FullyScanned, ext:scheduler
