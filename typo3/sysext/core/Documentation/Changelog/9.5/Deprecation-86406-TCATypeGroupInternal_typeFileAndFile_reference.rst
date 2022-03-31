.. include:: /Includes.rst.txt

==========================================================================
Deprecation: #86406 - TCA type group internal_type file and file_reference
==========================================================================

See :issue:`86406`

Description
===========

The :php:`TCA` property values :php:`internal_type="file"` and :php:`internal_type="file_reference"`
for columns config :php:`type="group"` have been marked as deprecated.

A series of related methods have been marked as deprecated:

* :php:`TYPO3\CMS\Core\DataHandling\DataHandler->checkValue_group_select_file()`
* :php:`TYPO3\CMS\Core\DataHandling\DataHandler->copyRecord_procFilesRefs()`
* :php:`TYPO3\CMS\Core\DataHandling\DataHandler->extFileFields()`
* :php:`TYPO3\CMS\Core\DataHandling\DataHandler->extFileFunctions()`
* :php:`TYPO3\CMS\Core\Database\ReferenceIndex->getRelations_procFiles()`
* :php:`TYPO3\CMS\Core\Integrity\DatabaseIntegrityCheck->getFileFields()`

Some constants have bee marked as deprecated:

* :php:`TYPO3\CMS\Core\DataHandling\TableColumnSubType::FILE`
* :php:`TYPO3\CMS\Core\DataHandling\TableColumnSubType::FILE_REFERENCE`

The "internal_type" functionality has been superseded by the File Abstraction Layer (FAL) since TYPO3 6.0
and has several drawbacks within TYPO3 (e.g. multiple copies of files based on a file name, no flexibility
for moving data to a different storage, no metadata functionality, no cropping functionality).

Impact
======

The Backend module "Upgrade" > "Check TCA migrations" shows them as deprecated, and triggers a
:php:`E_USER_DEPRECATED` error.

Using the TCA property values mentioned above will trigger a PHP :php:`E_USER_DEPRECATED` error when the cache is cleared.


Affected Installations
======================

Installations still using the methods or constants, or TYPO3 installations with extensions registering custom
TCA fields with the mentioned TCA properties.

Migration
=========

It is rather unlikely instances use one of the above methods or constants. The extension scanner will find possible
usages, though.

It's more likely that extensions use :php:`type=group` with :php:`internal_type=file` or
:php:`internal_type=file_reference`. Those should switch to use FAL references based on
:php:`type=inline` instead.

The core code changed one last :php:`internal_type=file` usage in TYPO3 v9 and moved it to FAL. Several use-cases
within the last TYPO3 major versions show how to migrate a legacy file field to FAL (e.g. "fe_users.image"
or "tt_content.image" including automatic upgrade wizards for the database - an example of the last migration can be
found online_. These previous changes give some insight on how a file relation could be changed to FAL and comes
with an upgrade wizard that can be a helpful example if existing extension data needs to be migrated.

.. _online: https://review.typo3.org/#/c/54830/

.. index:: Backend, PHP-API, TCA, PartiallyScanned
