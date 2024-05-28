.. include:: /Includes.rst.txt

.. _feature-104973-1726393875:

=====================================================================
Feature: #104973 - Activate the shipped LintYaml executable for TYPO3
=====================================================================

See :issue:`104973`

Description
===========

The `typo3` executable received a new command `lint:yaml` to ease and encourage linting of yaml files before deploying to production and therefor avoid failures.

Usage as follows:

.. code-block:: bash

    bin/typo3 lint:yaml
    bin/typo3 lint:yaml --help

The `help` argument will list possible usage elements.


Impact
======

Integrate easy made linting of yaml files from core, custom extensions or
any other source into your quality assurance workflow in the known format of the typo3 executable.

.. index:: CLI, YAML
