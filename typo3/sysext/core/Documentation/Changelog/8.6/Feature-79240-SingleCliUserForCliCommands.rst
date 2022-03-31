.. include:: /Includes.rst.txt

==================================================
Feature: #79240 - Single cli user for cli commands
==================================================

See :issue:`79240`

Description
===========

Accessing TYPO3 functionality from the command line has been simplified. Single commands no longer require single
users in the database, instead all cli command use the username `_cli_`.
This user is created on demand by the framework if it does not exist at the first command line call.
The `_cli_` user has admin rights and no longer needs specific access rights assigned to perform specific
tasks like manipulating database content using the :php:`DataHandler`.


Impact
======

Creating and managing command line tasks has been simplified, all existing `_cli_*` users can be deleted.

.. index:: CLI, PHP-API
