
.. include:: /Includes.rst.txt

===================================================
Feature: #68837 - Closures for Command Line Scripts
===================================================

See :issue:`68837`

Description
===========

For registering new command line scripts through the CLI API ("cliKey"), it is
now possible to use PHP closures instead of reference to PHP scripts.

Example usage inside ext_localconf.php:

.. code-block:: php

	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['GLOBAL']['cliKeys']['myclikey'] = array(
		function() {
			$controller = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\Acme\MyExtension\CommandLineTool::class);
			$controller->main();
		},
		'_CLI_lowlevel'
	);


.. index:: PHP-API, CLI
