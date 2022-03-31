
.. include:: /Includes.rst.txt

=======================================================
Deprecation: #68804 - CLI-related constants and methods
=======================================================

See :issue:`68804`

Description
===========

Logic regarding regular CLI-based scripts with the CLIkey option has been moved
into the CliRequestHandler.

Therefore, the following method has been marked as deprecated:

.. code-block:: php

	BackendUserAuthentication->checkCLIuser()

Additionally, the following constants and global parameters have been marked for deprecation in CLI context.

.. code-block:: php

	const TYPO3_cliKey
	const TYPO3_cliInclude
	$GLOBALS['MCONF']['name']
	$GLOBALS['temp_cliScriptPath']
	$GLOBALS['temp_cliKey']

The method, constants and variables will be removed in TYPO3 CMS 8.


Impact
======

Calling `BackendUserAuthentication->checkCLIuser()` directly will now trigger a deprecation log entry.


Affected Installations
======================

Installations with custom entry points in a CLI environment that make use of the method, constants or variables above.


Migration
=========

Use the native `$_SERVER['argv']` or the given `Input` object directly in your code to detect the
current CLI-relevant data.


.. index:: PHP-API, CLI
