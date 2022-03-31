.. include:: /Includes.rst.txt

========================================================================================
Deprecation: #95317 - Legacy syntax for IRRE localize synchronize command in DataHandler
========================================================================================

See :issue:`95317`

Description
===========

The :php:`\TYPO3\CMS\Core\DataHandling\DataHandler`
command :php:`inlineLocalizeSynchronize` now
triggers a PHP :php:`E_USER_DEPRECATED` error if the incoming command payload is sent
as comma-separated list rather than an array.

The array allows to synchronize/localize multiple values at once,
which is preferred since TYPO3 v7.6, and used in TYPO3 properly
since then.


Impact
======

Calling DataHandler :php:`process_cmdmap` with an incoming
command for :php:`inlineLocalizeSynchronize` with a payload
of comma-separated values will trigger a PHP :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

TYPO3 installations with custom code related to DataHandler
and modifying the :php:`inlineLocalizeSynchronize` command,
which is highly unlikely. This only affects special
handling of inline configuration fields.


Migration
=========

See :doc:`changelog <../7.6/Important-71126-AllowToDefineMultipleInlineLocalizeSynchronizeCommands>`
for further information on how to migrate your incoming
DataHandler command.

.. index:: PHP-API, NotScanned, ext:core
