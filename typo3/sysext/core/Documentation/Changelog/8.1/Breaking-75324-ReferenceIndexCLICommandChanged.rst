
.. include:: ../../Includes.txt

=====================================================
Breaking: #75324 - ReferenceIndex CLI command changed
=====================================================

See :issue:`75324`

Description
===========

The Reference Index Updater Command Line command has been changed to use a Symfony Command.

The command to update the reference index on non-composer-mode installations is now called on the command line via
`typo3/sysext/core/bin/typo3 referenceindex:update`.

To just check the reference index, the option `-c` (alternatively the property "check" can be used) is used like this `typo3/sysext/core/bin/typo3 referenceindex:update -c`

For installations set up via composer, the typo3 CLI binary is available in the "bin/" directory directly inside the
project root.

The command can be used like this:

.. code-block:: sh

	# update the reference index
	bin/typo3 referenceindex:update
	# check the reference index
	bin/typo3 referenceindex:update -c
	bin/typo3 referenceindex:update --check

The additional option --silent does not output anything when running the CLI command.


Impact
======

Calling the command via the old syntax `typo3/cli_dispatch.phpsh lowlevel refindex` will not work anymore.


Affected Installations
======================

Any existing installation upgrading to TYPO3 v8 with a (e.g. cron) CLI script, running the reference index update via
the :file:`typo3/cli_dispatch.phpsh`.


Migration
=========

Change the CLI scripts inside your installation to the new binary path.

.. index:: PHP-API, CLI, ext:lowlevel
