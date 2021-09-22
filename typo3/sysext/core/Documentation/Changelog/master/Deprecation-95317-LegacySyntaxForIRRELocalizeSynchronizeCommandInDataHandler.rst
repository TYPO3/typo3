.. include:: ../../Includes.txt

========================================================================================
Deprecation: #95317 - Legacy syntax for IRRE localize synchronize command in DataHandler
========================================================================================

See :issue:`95317`

Description
===========

The DataHandler command "inlineLocalizeSynchronize" now
triggers a warning if the incoming command payload is sent
as comma-separated list rather than an array.

The array allows to synchronize/localize multiple values at once,
which is preferred since TYPO3 v7.6, and used in TYPO3 properly
since then.


Impact
======

Calling DataHandler `process_cmdmap` with an incoming
command for "inlineLocalizeSynchronize" with a payload
of comma-separated values will trigger a PHP deprecation warning.


Affected Installations
======================

TYPO3 installations with custom code related to DataHandler
and modifying the "inlineLocalizeSynchronize" command,
which is highly unlikely. This only affects special
handling of "inline" configuration fields.


Migration
=========

See "Important-71126-AllowToDefineMultipleInlineLocalizeSynchronizeCommands.rst"
for further information on how to migrate your incoming
DataHandler command.

.. index:: PHP-API, NotScanned, ext:core