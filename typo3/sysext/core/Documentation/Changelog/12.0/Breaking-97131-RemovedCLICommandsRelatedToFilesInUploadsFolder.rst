.. include:: /Includes.rst.txt

.. _breaking-97131:

===========================================================================
Breaking: #97131 - Removed CLI commands related to files in uploads/ folder
===========================================================================

See :issue:`97131`

Description
===========

Historically, TYPO3 managed its actual files in a folder called
:file:`uploads/` until the `File Abstraction Layer` has been introduced
in TYPO3 v6.0. The old compatibility layer using :file:`uploads/` as
file storage folder has been removed in TYPO3 v10.

TYPO3 still has had some CLI commands which have now been
removed as they do not serve any use anymore:

* cleanup:multiplereferencedfiles
* cleanup:lostfiles
* cleanup:missingfiles

Impact
======

Calling the CLI commands will result in an CLI exit code > 0,
as they have been removed.

Affected Installations
======================

TYPO3 installations still having CLI tools using the CLI commands,
which serve no purpose anymore.

Migration
=========

None.

.. index:: CLI, NotScanned, ext:lowlevel
