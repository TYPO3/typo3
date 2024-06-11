.. include:: /Includes.rst.txt

.. _feature-104058-1718204204:

=========================================================
Feature: #103956 - Introduce install:password:set command
=========================================================

See :issue:`104058`

Description
===========

The Install Tool Password can now also be set via the TYPO3 command line interface
instead of only via direct file access, allowing for more automation and easy
hash generation without extra steps.

Usage
=====

Interactively create and write an install-tool password hash:

..  code-block:: bash

    bin/typo3 install:password:set

Only return the generated password hash without writing to settings:

..  code-block:: bash

    bin/typo3 install:password:set --dry-run

No interaction mode (generates random password):

..  code-block:: bash

    bin/typo3 install:password:set --no-interaction

Options can be combined as needed:

..  code-block:: bash

    bin/typo3 install:password:set --dry-run --no-interaction

The last variation could be used for example in CI/CD automation to
generate a suitable password, process the output (generated password
needs to be persisted on its own) and store it in vaults or environment
variables for further usage.

Impact
======

It is now possible to set the (hashed) Install Tool password using the
TYPO3 command line interface.

.. index:: Backend, CLI, LocalConfiguration, ext:install
