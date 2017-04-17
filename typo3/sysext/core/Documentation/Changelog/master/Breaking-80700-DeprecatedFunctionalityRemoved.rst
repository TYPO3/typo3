.. include:: ../../Includes.txt

===================================================
Breaking: #80700 - Deprecated functionality removed
===================================================

See :issue:`80700`

Description
===========

The following PHP classes that have been previously deprecated for v8 have been removed:

* TYPO3\CMS\Backend\Console\Application
* TYPO3\CMS\Backend\Console\CliRequestHandler
* TYPO3\CMS\Core\Controller\CommandLineController
* TYPO3\CMS\Lowlevel\CleanerCommand

The following configuration options are not evaluated anymore:
* $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['GLOBAL']['cliKeys']

The following entrypoints have been removed
* typo3/cli_dispatch.phpsh


Impact
======

Instantiating or requiring the PHP classes, will result in PHP fatal errors.

Calling the entrypoints via CLI will result in a file not found error.

.. index:: PHP-API