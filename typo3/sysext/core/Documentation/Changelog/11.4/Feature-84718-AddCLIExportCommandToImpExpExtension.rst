.. include:: /Includes.rst.txt

======================================================
Feature: #84718 - Add CLI export command to EXT:impexp
======================================================

See :issue:`84718`

Description
===========

The new CLI command

-  :bash:`impexp:export`

was added as the missing twin of the existing CLI command :bash:`impexp:import`.

The export command can be executed via

.. code-block:: bash

   typo3/sysext/core/bin/typo3 impexp:export [options] [--] [<filename>]

and exports the entire TYPO3 page tree - or parts of it - to a data file of
format XML or T3D, which can be used for import into any TYPO3 instance or
as initial page tree of a :ref:`distribution <t3coreapi:distribution>`.

The export can be fine-tuned through the complete set of options already
available in the export view of the TYPO3 backend:

.. code-block:: bash

   Arguments:
      filename                           The filename to export to (without file extension)

   Options:
      --type[=TYPE]                      The file type (xml, t3d, t3d_compressed). [default: "xml"]
      --pid[=PID]                        The root page of the exported page tree. [default: -1]
      --levels[=LEVELS]                  The depth of the exported page tree. "-2": "Records on this page", "-1": "Expanded tree", "0": "This page", "1": "1 level down", .. "999": "Infinite levels". [default: 0]
      --table[=TABLE]                    Include all records of this table. Examples: "_ALL", "tt_content", "sys_file_reference", etc. (multiple values allowed)
      --record[=RECORD]                  Include this specific record. Pattern is "{table}:{record}". Examples: "tt_content:12", etc. (multiple values allowed)
      --list[=LIST]                      Include the records of this table and this page. Pattern is "{table}:{pid}". Examples: "sys_language:0", etc. (multiple values allowed)
      --includeRelated[=INCLUDERELATED]  Include record relations to this table, including the related record. Examples: "_ALL", "sys_category", etc. (multiple values allowed)
      --includeStatic[=INCLUDESTATIC]    Include record relations to this table, excluding the related record. Examples: "_ALL", "sys_language", etc. (multiple values allowed)
      --exclude[=EXCLUDE]                Exclude this specific record. Pattern is "{table}:{record}". Examples: "fe_users:3", etc. (multiple values allowed)
      --excludeDisabledRecords           Exclude records which are handled as disabled by their TCA configuration, e.g. by fields "disabled", "starttime" or "endtime".
      --excludeHtmlCss                   Exclude referenced HTML and CSS files.
      --title[=TITLE]                    The meta title of the export.
      --description[=DESCRIPTION]        The meta description of the export.
      --notes[=NOTES]                    The meta notes of the export.
      --dependency[=DEPENDENCY]          This TYPO3 extension is required for the exported records. Examples: "news", "powermail", etc. (multiple values allowed)
      --saveFilesOutsideExportFile       Save files into separate folder instead of including them into the common export file. Folder name pattern is "{filename}.files".

Impact
======

Exporting a TYPO3 page tree without time limit is now possible via CLI.

Repeated exports with the same configuration become easily documentable and
applicable - for example during distribution development.

.. index:: CLI, ext:impexp
