==============================================================
Feature: #73042 - Introduce native support for Symfony Console
==============================================================

Description
===========

TYPO3 supports the Symfony Console component out-of-the-box now by providing a new Command Line script
located in typo3/sysext/core/bin/t3console. On TYPO3 instances installed via Composer, the binary can be
linked into bin/t3console.

The new binary still supports the existing command-line arguments when no proper Symfony Console command
was found as a fallback.

Registering a command to be available via the ``t3console`` command line tool works by putting a
``Configuration/Commands.php`` file into any installed extension. This lists the Symfony/Console/Command classes
to be executed by t3console in an associative array. The key of the is the name of the command to be called as
the first argument after ``t3console``.

A required parameter when registering a command is the "class" property. Optionally the "user" parameter can be
set so a Backend user is logged in when calling the command.

The extensions' ``Configuration/Commands.php`` could look like this:

.. code-block:: php

    return [
        'backend:lock' => [
            'class' => \TYPO3\CMS\Backend\Command\LockBackendCommand::class
        ],
        'referenceindex:update' => [
            'class' => \TYPO3\CMS\Backend\Command\ReferenceIndexUpdateCommand::class,
            'user' => '_cli_lowlevel'
        ]
    ];


An example call could look like:

.. code-block:: sh

	typo3/sysext/core/bin/t3console backend:lock http://www.mydomain.com/maintenance.html


Impact
======

Using Symfony Commands and calling ``t3console`` instead of using ``typo3/cli_dispatch.phpsh`` is
now the preferred way for writing Command Line code.
