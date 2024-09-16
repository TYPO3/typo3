.. include:: /Includes.rst.txt

.. _feature-104973-1726393875:

=====================================================================
Feature: #104973 - Activate the shipped LintYaml executable for TYPO3
=====================================================================

See :issue:`104973`

Description
===========

The :bash:`typo3` executable received a new command `lint:yaml` to ease and encourage
linting of YAML files before deploying to production and therefore avoid failures.

Usage as follows:

.. code-block:: bash

    bin/typo3 lint:yaml
    bin/typo3 lint:yaml --help

The `help` argument will list possible usage elements.


Impact
======

Integrate easy made linting of YAML files from Core, custom extensions or
any other source into your quality assurance workflow in the known format
of the :bash:`typo3` executable.

.. index:: CLI, YAML
