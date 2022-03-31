.. include:: /Includes.rst.txt

===================================================================================
Deprecation: #95062 - $skipSorting argument of RelationHandler->writeForeignField()
===================================================================================

See :issue:`95062`

Description
===========

To further clean up :php:`TYPO3\CMS\Core\DataHandling\DataHandler`, the unused
internal property :php:`callFromImpExp` has been removed. Its single usage has
been the 4th argument of :php:`TYPO3\CMS\Core\Database\RelationHandler->writeForeignField()`.
Handing over this argument to :php:`RelationHandler->writeForeignField()` has been
marked as deprecated.


Impact
======

Calling :php:`TYPO3\CMS\Core\Database\RelationHandler->writeForeignField()` with
4th argument triggers a PHP :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

It is unlikely instances contain extensions using the above argument, since
it carried a core internal information tailored for EXT:impexp specific needs.
The extension scanner will find usages as weak match.


Migration
=========

No migration available. Consuming extensions should drop that argument.
Calling RelationHandler->writeForeignField() with non-default true as fourth
argument skipped some relation-sorting related code, which should be avoided.

.. index:: Database, FullyScanned, ext:core
