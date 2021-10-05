.. include:: /Includes.rst.txt

.. _command_line:

============
Command line
============

The import/export tool can alternatively also be used via the command line.
The advantage of using the CLI is that there is no PHP time limit, therefore
larger page trees can be exported and imported.

The exports and imports can be fine-tuned through the complete set of options
also available in the :ref:`import<import>` or :ref:`export module<export>` of
the TYPO3 backend.

.. note::
   If your TYPO3 installation is based on Composer, you can run the command
   with the shortcut :bash:`vendor/bin/typo3` instead of :bash:`typo3/sysext/core/bin/typo3`.

Export
======

Export the entire TYPO3 page tree (or selected parts of it) to a data file of
format XML or T3D:

.. code-block:: bash

   typo3/sysext/core/bin/typo3 impexp:export [options] [--] [<filename>]

with these options available:

.. code-block:: bash

   Arguments:
      filename                             The filename to export to (without file extension)

   Options:
      --type[=TYPE]                        The file type (xml, t3d, t3d_compressed). [default: "xml"]
      --pid[=PID]                          The root page of the exported page tree. [default: -1]
      --levels[=LEVELS]                    The depth of the exported page tree. "-2": "Records on this page", "-1": "Expanded tree", "0": "This page", "1": "1 level down", .. "999": "Infinite levels". [default: 0]
      --table[=TABLE]                      Include all records of this table. Examples: "_ALL", "tt_content", "sys_file_reference", etc. (multiple values allowed)
      --record[=RECORD]                    Include this specific record. Pattern is "{table}:{record}". Examples: "tt_content:12", etc. (multiple values allowed)
      --list[=LIST]                        Include the records of this table and this page. Pattern is "{table}:{pid}". Examples: "sys_language:0", etc. (multiple values allowed)
      --include-related[=INCLUDE-RELATED]  Include record relations to this table, including the related record. Examples: "_ALL", "sys_category", etc. (multiple values allowed)
      --include-static[=INCLUDE-STATIC]    Include record relations to this table, excluding the related record. Examples: "_ALL", "sys_language", etc. (multiple values allowed)
      --exclude[=EXCLUDE]                  Exclude this specific record. Pattern is "{table}:{record}". Examples: "fe_users:3", etc. (multiple values allowed)
      --exclude-disabled-records           Exclude records which are handled as disabled by their TCA configuration, e.g. by fields "disabled", "starttime" or "endtime".
      --exclude-html-css                   Exclude referenced HTML and CSS files.
      --title[=TITLE]                      The meta title of the export.
      --description[=DESCRIPTION]          The meta description of the export.
      --notes[=NOTES]                      The meta notes of the export.
      --dependency[=DEPENDENCY]            This TYPO3 extension is required for the exported records. Examples: "news", "powermail", etc. (multiple values allowed)
      --save-files-outside-export-file     Save files into separate folder instead of including them into the common export file. Folder name pattern is "{filename}.files".

Import
======

Import an export dump file in XML or T3D format into a TYPO3 instance:

.. code-block:: bash

   typo3/sysext/core/bin/typo3 impexp:import [options] [--] <file> [<pid>]

with these options available:

.. code-block:: bash

   Arguments:
      file                         The file path to import from (.t3d or .xml).
      pid                          The page to import to. [default: 0]

   Options:
      --update-records             If set, existing records with the same UID will be updated instead of inserted.
      --ignore-pid                 If set, page IDs of updated records are not corrected (only works in conjunction with --update-records).
      --force-uid                  If set, UIDs from file will be forced.
      --import-mode[=IMPORT-MODE]  Set the import mode of this specific record.
                                   Pattern is "{table}:{record}={mode}".
                                   Available modes for new records are "force_uid" and "exclude" and for existing records "as_new", "ignore_pid", "respect_pid" and "exclude".
                                   Examples are "pages:987=force_uid", "tt_content:1=as_new", etc. (multiple values allowed)
      --enable-log                 If set, all database actions are logged.
