.. include:: ../../Includes.txt

===============================================
Deprecation: #85977 - Deprecate @cli annotation
===============================================

See :issue:`85977`

Description
===========

Back then, the PHPDoc annotation `@cli` was added to indicate Extbase CommandController commands to be usable on CLI only instead of also be usable as a scheduler task.

The scheduler implementation will be refactored in TYPO3 10 and the execution of commands from the backend will vanish. Therefore it will not be necessary any more to define if commands can only be used on the command line or not. In the future, all commands will only be executable from the command line.


Impact
======

Using @cli will log a deprecation warning.
Once removed from your commands, they will appear in the list of executable executable commands in the scheduler module.


Affected Installations
======================

All installations that make use of command controllers that are tagged with @cli.


Migration
=========

There is none.

.. index:: FullyScanned, ext:scheduler
