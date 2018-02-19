.. include:: ../../Includes.txt

===========================================================================
Breaking: #82506 - Remove BackendUserRepository injection in NoteController
===========================================================================

See :issue:`82506`

Description
===========

To improve the performance of showing sys_note records the injection of the :php:`BackendUserRepository` has been
removed in :php:`\TYPO3\CMS\SysNote\Controller\NoteController`.


Impact
======

The method :php:`\TYPO3\CMS\SysNote\Domain\Repository\SysNoteRepository::findByPidsAndAuthor` has been removed.


Affected Installations
======================

Any installation using third-party extension that use the method
:php:`\TYPO3\CMS\SysNote\Domain\Repository\SysNoteRepository::findByPidsAndAuthor`.


Migration
=========

Use the method :php:`\TYPO3\CMS\SysNote\Domain\Repository\SysNoteRepository::findByPidsAndAuthorId`, and use the
user id as 2nd argument instead of a `BackendUser` object.

.. index:: Backend, PHP-API, FullyScanned, ext:sys_note
