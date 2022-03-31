.. include:: /Includes.rst.txt

==================================================================
Deprecation: #92598 - Workspace-related methods "fixVersioningPid"
==================================================================

See :issue:`92598`

Description
===========

The two workspace-related methods

* :php:`TYPO3\CMS\Core\Domain\Repository\PageRepository->fixVersioningPid()`
* :php:`TYPO3\CMS\Backend\Utility\BackendUtility::fixVersioningPid()`

have been marked as deprecated, as they are not needed in TYPO3 v11 anymore.

Both methods served to replace the value of a record's "pid" of
a live version with the actual "pid" value of a versioned record.

Since TYPO3 v11 this is only different for versioned records which
have been moved, where the live record has e.g. a PID value of 13
but in a workspace the record was moved to PID 20. In order to
correctly resolve e.g. a page path or a rootline, these methods
helped to modify the "pid" value.

However, as TYPO3 v11 does not use Move Placeholders anymore,
and move pointers (records moved in a workspace) already contain
the newly moved location as "pid" value, the extra database
call is not needed.


Impact
======

Calling these methods in custom PHP code will trigger a PHP :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

TYPO3 installations with custom PHP code respecting versioned
records with these methods. This usually does not apply to
Extbase-related extensions or extensions that do not consider
moved records in Workspaces (yet).


Migration
=========

The API methods:

* :php:`TYPO3\CMS\Core\Domain\Repository\PageRepository->versionOL()`
* :php:`TYPO3\CMS\Backend\Utility\BackendUtility::workspaceOL()`
* :php:`TYPO3\CMS\Backend\Utility\BackendUtility::getRecordWSOL()`

now override the "pid" value of the moved records directly, and
keep the live "pid" value in "_ORIG_pid".

It is highly recommended to use these methods.

If it is needed to manually find the online PID for a versioned record, it is
recommended to just fetch the live record (stored in :sql:`t3ver_oid`) via
typical Doctrine-based database queries and load the PID value from there,
or use the overlay methods as described to get both values.


.. index:: PHP-API, FullyScanned, ext:workspaces
