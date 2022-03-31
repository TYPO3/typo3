
.. include:: /Includes.rst.txt

===================================================
Breaking: #73152 - Symfony console helpers replaced
===================================================

See :issue:`73152`

Description
===========

By upgrading to Symfony Console 3.x the `DialogHelper`, `ProgressHelper` and
`TableHelper` have been replaced. The internal getter methods for these classes
have been replaced in Extbase `ConsoleOutput`.


Impact
======

Calling the following methods with result in a fatal error:

- `ConsoleOutput::getDialogHelper()`
- `ConsoleOutput::getProgressHelper()`
- `ConsoleOutput::getTableHelper()`

The 2nd argument of the following methods is ignored now:

- `ConsoleOutput::progressAdvance()`
- `ConsoleOutput::progressSet()`


Affected Installations
======================

Extensions which have directly called these methods in favor of the Extbase
`ConsoleOutput` helper methods.


Migration
=========

Use the following methods instead:

- `ConsoleOutput::getQuestionHelper()`
- `ConsoleOutput::getProgressBar()`
- `ConsoleOutput::getTable()`

Remove the 2nd argument when calling these methods:

- `ConsoleOutput::progressAdvance()`
- `ConsoleOutput::progressSet()`

.. index:: PHP-API, Backend, CLI
