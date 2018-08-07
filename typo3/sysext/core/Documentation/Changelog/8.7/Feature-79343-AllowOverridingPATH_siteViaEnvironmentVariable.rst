.. include:: ../../Includes.txt

=====================================================================
Feature: #79343 - Allow overriding PATH_site via environment variable
=====================================================================

See :issue:`79343`

Description
===========

It is now possible to define the :php:`PATH_site` constant, which acts as a basis for any entry point
running a TYPO3 system, via the environment variable :php:`TYPO3_PATH_ROOT`.

This variable is automatically calculated and set for any TYPO3 installation set up via composer,
making it possible to run the TYPO3 command line interface from any location of the system.


Impact
======

When using the command line entry-point :file:`typo3/sysext/core/bin/typo3` with composer, it can also
be called from a projects' default :file:`bin/` directory.

.. index:: CLI, PHP-API
