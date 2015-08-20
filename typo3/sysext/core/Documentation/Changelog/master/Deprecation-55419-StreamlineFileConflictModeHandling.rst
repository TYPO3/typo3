============================================================
Deprecation: #55419 - Streamline file conflict mode handling
============================================================


Description
===========

Conflicts in file names and folder names when uploading new files or creating new folders are now handled
uniformly with constants within the core. Therefore a new enumeration was introduced to provide the available
values: ``\TYPO3\CMS\Core\Resource\DuplicationBehavior``.

Provided constants are:
 * DuplicationBehavior::RENAME
 * DuplicationBehavior::REPLACE
 * DuplicationBehavior::CANCEL

Before this change there were two sets of strings used to define the behavior upon conflicts.
 * Set1: ``cancel``, ``replace`` and ``changeName``
 * Set2: ``cancel``, ``overrideExistingFile`` and ``renameNewFile``

As they are redundant they are now represented by a new set of constants:

 * ``cancel``, ``replace`` and ``rename``

All usages of strings of the former sets have been replaced with their counterparts from the new set. In the enumeration
the former values are mapped to the new values and have been marked for deprecation.


Impact
======

Using ``changeName``, ``overrideExistingFile`` or ``renameNewFile`` for file conflict handling will result in a deprecation log entry.


Affected Installations
======================

All third party code that calls one of the listed methods with ``$conflictMode`` either set to ``changeName``, ``overrideExistingFile`` or ``renameNewFile``.


Migration
=========

Use the provided enumeration ``\TYPO3\CMS\Core\Resource\DuplicationBehavior`` instead.


Example
=======

.. code-block:: php

	$resourceStorage->copyFile($file, $targetFolder, 'target-file-name', DuplicationBehavior::RENAME);
