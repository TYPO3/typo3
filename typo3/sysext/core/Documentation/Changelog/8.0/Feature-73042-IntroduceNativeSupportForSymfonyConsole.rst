
.. include:: /Includes.rst.txt

==============================================================
Feature: #73042 - Introduce native support for Symfony Console
==============================================================

See :issue:`73042`

Description
===========

TYPO3 supports the Symfony Console component out-of-the-box now by providing a new Command Line script
located in `typo3/sysext/core/bin/typo3`. On TYPO3 instances installed via Composer, the binary is
linked into the `bin-dir`, e.g. `bin/typo3`.

The new binary still supports the existing command-line arguments when no proper Symfony Console command
was found as a fallback.

Registering a command to be available via the `typo3` command line tool works by putting a
`Configuration/Commands.php` file into any installed extension. This lists the Symfony/Console/Command classes
to be executed by `typo3` is an associative array. The key is the name of the command to be called as
the first argument to `typo3`.

A required parameter when registering a command is the `class` property which should inherit from Symfony's
base Command class.

A `Configuration/Commands.php` could look like this:

.. code-block:: php

    return [
        'backend:lock' => [
            'class' => \TYPO3\CMS\Backend\Command\LockBackendCommand::class
        ],
        'referenceindex:update' => [
            'class' => \TYPO3\CMS\Backend\Command\ReferenceIndexUpdateCommand::class
        ]
    ];


An example call could look like:

.. code-block:: sh

	bin/typo3 backend:lock http://www.mydomain.com/maintenance.html

For a non-Composer installation:

.. code-block:: sh

	typo3/sysext/core/bin/typo3 backend:lock http://www.mydomain.com/maintenance.html


Impact
======

Using Symfony Commands and calling `typo3` instead of using `typo3/cli_dispatch.phpsh` is
now the preferred way for writing command line code.

.. index:: PHP-API, Backend, CLI
