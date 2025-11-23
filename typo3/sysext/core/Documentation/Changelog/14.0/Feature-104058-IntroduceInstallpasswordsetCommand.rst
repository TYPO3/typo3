.. include:: /Includes.rst.txt

.. _feature-104058-1718204204:

=========================================================
Feature: #104058 - Introduce install:password:set command
=========================================================

See :issue:`104058`

Description
===========

The Install Tool password can now also be set via the TYPO3 command line interface
instead of only via direct file access. This allows for better automation and
easier hash generation without manual steps.

Usage
=====

Interactively create and write an Install Tool password hash:

..  code-block:: bash

    vendor/bin/typo3 install:password:set

Return the generated password hash without writing to the configuration:

..  code-block:: bash

    vendor/bin/typo3 install:password:set --dry-run

Run without interaction (generates a random password):

..  code-block:: bash

    vendor/bin/typo3 install:password:set --no-interaction

Options can be combined as needed:

..  code-block:: bash

    vendor/bin/typo3 install:password:set --dry-run --no-interaction

The last variation can, for example, be used in CI/CD automation to
generate a suitable password, process the output (the generated password
needs to be persisted separately), and store it in vaults or environment
variables for later use.

Impact
======

It is now possible to set the hashed Install Tool password using the
TYPO3 command line interface.

.. index:: Backend, CLI, LocalConfiguration, ext:install
