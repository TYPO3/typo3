============================================================================
Deprecation: #63603 - ExtendedFileUtility::$dontCheckForUnique is deprecated
============================================================================

Description
===========

The ExtendedFileUtility ``$dontCheckForUnique`` flag is deprecated and replaced by ``$fileUtility->setExistingFileConflictMode()`` with the possible options ``cancel``, ``replace`` and ``changeName``.


Impact
======

Extensions still using ``ExtendedFileUtility::$dontCheckForUnique`` will throw a deprecation warning.


Affected Installations
======================

All installations with extensions that use ``ExtendedFileUtility::$dontCheckForUnique``.


Migration
=========

Change the ``$fileUtility->dontCheckForUnique = TRUE`` to ``$fileUtility->setExistingFileConflictMode('replace')``.