.. include:: ../../Includes.txt

====================================================================
Breaking: #79120 - Remove legacy CLI-related constants and variables
====================================================================

See :issue:`79120`

Description
===========

The deprecated PHP constants :php:`TYPO3_cliKey` and :php:`TYPO3_cliInclude`, and the global variables :php:`$GLOBALS['temp_cliScriptPath']` and 
:php:`$GLOBALS['temp_cliKey']` which had been filled when running a CLI command have been removed.


Impact
======

Calling one of the PHP constants above will result in a PHP error. Accessing the global variables will result in a PHP warning.


Affected Installations
======================

Any installation with third-party CLI commands which use these constants or global variables.

.. index:: CLI, PHP-API
