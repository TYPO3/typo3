..  include:: /Includes.rst.txt

..  _feature-59312-1761220800:

===================================================================
Feature: #59312 - Introduce cleanup command for missing FAL records
===================================================================

See :issue:`59312`

Description
===========

Files that are deleted outside of TYPO3, for example via SSH or FTP, or that
are gone after restoring a database dump without the matching file system,
keep their records in :sql:`sys_file` and :sql:`sys_file_metadata`. The
storage indexer flags these records as missing (:sql:`sys_file.missing = 1`)
and the reports module lists them under "Files flagged as missing", but so
far nothing in TYPO3 could remove them. Deleting a missing file through the
File Abstraction Layer API is rejected as well, so the only way out was to
restore the file and delete it again, or to clean the database by hand.

The new CLI command :bash:`cleanup:missingfiles` of :composer:`typo3/cms-lowlevel`
closes this gap. It takes over the name of the command that was removed in
TYPO3 v12 together with the :file:`uploads/` folder handling, as the File
Abstraction Layer is the only file handling left.

The command

*   optionally updates the reference index (:bash:`--update-refindex`), as
    :sql:`sys_refindex` decides whether a file is still referenced,
*   optionally updates the storage index of one or more file storages
    (:bash:`--update-storage=<uid>`, repeatable, or :bash:`--update-storage=all`),
    so files deleted outside of TYPO3 are flagged as missing first,
*   finds all :sql:`sys_file` records flagged as missing,
*   keeps the records that are still referenced by any record, including soft
    references such as :html:`t3://file?uid=` links, and lists them in verbose
    mode, so that the file can be restored or the references can be removed,
*   removes missing files without references the same way as a file deleted
    via TYPO3: the :sql:`sys_file` and :sql:`sys_file_metadata` records, their
    category relations, their processed files (records and files in the
    processing folder) and left-over deleted file references are removed, and
    the reference index entries are updated.

Running the command
-------------------

Show which records would be removed, after updating the reference index and
the index of storage 1:

..  code-block:: bash

    vendor/bin/typo3 cleanup:missingfiles --dry-run -v --update-refindex --update-storage=1

Remove the records:

..  code-block:: bash

    vendor/bin/typo3 cleanup:missingfiles --update-refindex --update-storage=all

Without :bash:`--update-refindex` and :bash:`--update-storage` the command asks
in interactive mode whether both indexes should be updated, and assumes them to
be up to date in non-interactive mode (:bash:`-n`).

Impact
======

Administrators can remove the database records of files that are flagged as
missing and are no longer referenced with the new CLI command
:bash:`cleanup:missingfiles`, instead of manipulating the database by hand.

..  index:: CLI, FAL, ext:lowlevel
