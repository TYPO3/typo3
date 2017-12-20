
.. include:: ../../Includes.txt

==============================================================
Deprecation: #77432 - Extbase: Prepared Statement Query Option
==============================================================

See :issue:`77432`

Description
===========

The option to use prepared statements within the Extbase persistence layer has been removed. The method
`getUsePreparedStatement()` has been removed from the `QuerySettingsInterface`, as the database
abstraction layer will take care of prepared statements automatically.

The implementation of the following properties within `Typo3QuerySettings` has been marked as
deprecated:

* `getUsePreparedStatement()`
* `usePreparedStatement()`

The protected property `usePreparedStatement` has been marked as deprecated as well.


Impact
======

Calling one of the methods above within the `QuerySettings` object within the extbase persistence
will trigger a deprecation notice warning.


Affected Installations
======================

Any TYPO3 instance with an extbase extension using custom query settings using the
`usePreparedStatement()` option.


Migration
=========

Remove any calls to the methods within the extensions' code, as the TYPO3 abstraction layer will
handle them automatically.

.. index:: PHP-API, ext:extbase, Database