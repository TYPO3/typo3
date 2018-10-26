.. include:: /Includes.rst.txt

.. _feature-45039-1674297405:

===========================================================
Feature: #45039 - Command to clean up local processed files
===========================================================

See :issue:`45039`

Description
===========

It is now possible to set up a recurring scheduler task or execute a CLI command
to clean up local processed files and their respective database records.


Impact
======

The command will delete `sys_file_processedfile` records with references on
non-existing files. Also files in the configured temporary directory
(typically `_processed_`) will be deleted if there are no references on them.


Example
=======

Delete files and records with confirmation:

..  code-block:: bash

    ./bin/typo3 cleanup:localprocessedfiles

Delete files and records:

..  code-block:: bash

    ./bin/typo3 cleanup:localprocessedfiles -f

Only show which files and records would be deleted:

..  code-block:: bash

    ./bin/typo3 cleanup:localprocessedfiles --dry-run -v

Please note that the command currently only works for local drivers.

.. index:: CLI, PHP-API, ext:lowlevel
