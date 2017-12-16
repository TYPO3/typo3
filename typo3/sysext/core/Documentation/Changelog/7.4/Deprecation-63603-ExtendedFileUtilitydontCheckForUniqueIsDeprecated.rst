
.. include:: ../../Includes.txt

============================================================================
Deprecation: #63603 - ExtendedFileUtility::$dontCheckForUnique is deprecated
============================================================================

See :issue:`63603`

Description
===========

The ExtendedFileUtility `$dontCheckForUnique` flag has been marked as deprecated and replaced by
`$fileUtility->setExistingFileConflictMode()` with the possible options of the `\TYPO3\CMS\Core\Resource\DuplicationBehavior` enumeration.


Impact
======

Extensions still using `ExtendedFileUtility::$dontCheckForUnique` will throw a deprecation warning.


Affected Installations
======================

All installations with extensions that use `ExtendedFileUtility::$dontCheckForUnique`.


Migration
=========

Change the `$fileUtility->dontCheckForUnique = TRUE` to `$fileUtility->setExistingFileConflictMode(DuplicationBehavior::REPLACE)`.
