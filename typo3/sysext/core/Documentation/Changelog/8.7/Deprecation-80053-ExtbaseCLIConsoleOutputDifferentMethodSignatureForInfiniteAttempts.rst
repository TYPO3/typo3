.. include:: /Includes.rst.txt

=================================================================================================
Deprecation: #80053 - Extbase CLI Console Output different method signature for infinite attempts
=================================================================================================

See :issue:`80053`

Description
===========

When using Extbase's CLI functionality to ask for a question via :php:`ConsoleOutput->select()` or
:php:`ConsoleOutput->askAndValidate()` the option to define infinite attempts has changed from "false"
to "null".


Impact
======

Calling any of the methods with :php:`$attempts = false` will trigger a deprecation log entry.


Affected Installations
======================

Any TYPO3 extension shipping custom CLI commands for Extbase using the methods above with the option
to have infinite attempts.


Migration
=========

Set the method argument from "false" to "null" in the Extbase Command of your extension.

.. index:: CLI, ext:extbase, PHP-API
