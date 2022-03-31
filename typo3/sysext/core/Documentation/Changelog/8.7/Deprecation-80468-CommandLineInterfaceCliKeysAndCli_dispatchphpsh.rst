.. include:: /Includes.rst.txt

============================================================================
Deprecation: #80468 - Command Line Interface: cliKeys and cli_dispatch.phpsh
============================================================================

See :issue:`80468`

Description
===========

The functionality to register any command line script via
:php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['GLOBAL']['cliKeys']['my_extension']` has been marked as deprecated.

The entry-point '`typo3/cli_dispatch.phpsh`` as well as the corresponding :php:`Application` class and
the :php:`CliRequestHandler` class have been marked as deprecated as well.

The functionality has been superseded by Symfony Console and the new entry-point within
``typo3/sysext/core/bin/typo3`` which is able to handle all functionality the same way including
all Extbase-related Command Controllers.


Impact
======

Calling the CLI entrypoint ``typo3/cli_dispatch.phpsh`` to call a CLI script will trigger a
deprecation warning.


Affected Installations
======================

Any installation using ``typo3/cli_dispatch.phpsh`` in any deployment or cronjob / scheduler
functionality.


Migration
=========

All functionality related to Extbase, EXT:lowlevel, or scheduler tasks can be called via
the new entrypoint ``typo3/sysext/core/bin/typo3`` with a similar call.

Update all cronjobs and automated and manual running scripts called via the command line to use
the new entrypoint.

If there any custom cliKeys registered, migrate them to a Symfony Command or an Extbase Command
Controller.

.. index:: CLI, LocalConfiguration
