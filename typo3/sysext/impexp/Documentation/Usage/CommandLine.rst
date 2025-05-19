:navigation-title: Command line

..  include:: /Includes.rst.txt
..  _command_line:

======================================================
Using the Import/Export tool from the command line
======================================================

The import/export tool can alternatively also be used via the command line.
The advantage of using the CLI is that there is no PHP time limit, therefore
larger page trees can be exported and imported.

The exports and imports can be fine-tuned through the complete set of options
also available in the :ref:`import <import>` or :ref:`export module <export>`
of the TYPO3 backend.

.. note::

    If your TYPO3 installation is based on Composer, you can run the command
    with the shortcut :bash:`vendor/bin/typo3` instead of
    :bash:`typo3/sysext/core/bin/typo3`.

..  attention::

    Exporting and importing content may expose sensitive data or bypass
    permission boundaries. Review the :ref:`security considerations regarding
    exports <security>` before using this functionality.

..  _command_line-export:

Exporting content from the command line
=======================================

Export the entire TYPO3 page tree (or selected parts of it) to a data file of
format XML or T3D:

..  tabs::

    ..  tab:: Composer mode

        ..  code-block:: bash

            vendor/bin/typo3 impexp:export [options] [--] [<filename>]

    ..  tab:: Classic mode

        ..  code-block:: bash

            typo3/sysext/core/bin/typo3 impexp:export [options] [--] [<filename>]

With these options available:

..  code-block:: bash

    Arguments:
        filename                             The filename to export to (without file extension)

    Options:
        --type[=TYPE]                        The file type (xml, t3d, t3d_compressed). [default: "xml"]
        --pid[=PID]                          The root page of the exported page tree. [default: -1]
        --levels[=LEVELS]                    The depth of the exported page tree.
                                             "-2": "Records on this page", "0": "This page",
                                             "1": "1 level down", .. "999": "Infinite levels". [default: 0]
        --table[=TABLE]                      Include all records of this table.
                                             Examples: "_ALL", "tt_content", "sys_file_reference", etc.
                                             (multiple values allowed)
        --record[=RECORD]                    Include this specific record. Pattern is "{table}:{record}".
                                             Examples: "tt_content:12", etc. (multiple values allowed)
        --list[=LIST]                        Include records of this table and page. Pattern is "{table}:{pid}".
                                             Examples: "be_users:0", etc. (multiple values allowed)
        --include-related[=INCLUDE-RELATED]  Include record relations to this table, including the related record.
                                             Examples: "_ALL", "sys_category", etc. (multiple values allowed)
        --include-static[=INCLUDE-STATIC]    Include record relations to this table, excluding the related record.
                                             Examples: "_ALL", "be_users", etc. (multiple values allowed)
        --exclude[=EXCLUDE]                  Exclude this specific record. Pattern is "{table}:{record}".
                                             Examples: "fe_users:3", etc. (multiple values allowed)
        --exclude-disabled-records           Exclude records considered disabled by their TCA configuration,
                                             e.g. "disabled", "starttime", or "endtime" fields.
        --exclude-html-css                   Exclude referenced HTML and CSS files.
        --title[=TITLE]                      The meta title of the export.
        --description[=DESCRIPTION]          The meta description of the export.
        --notes[=NOTES]                      The meta notes of the export.
        --dependency[=DEPENDENCY]            Declare required TYPO3 extensions for the export.
                                             Examples: "news", "powermail", etc. (multiple values allowed)
        --save-files-outside-export-file     Save files in a separate folder named "{filename}.files"
                                             instead of embedding them in the export file.

..  _command_line-import:

Importing content from the command line
=======================================

Import an export dump file in XML or T3D format into a TYPO3 instance:

..  tabs::

    ..  tab:: Composer mode

        ..  code-block:: bash

            vendor/bin/typo3 impexp:import [options] [--] <file> [<pid>]

    ..  tab:: Classic mode

        ..  code-block:: bash

            typo3/sysext/core/bin/typo3 impexp:import [options] [--] <file> [<pid>]

With these options available:

..  code-block:: bash

    Arguments:
        file                         The file path to import from (.t3d or .xml).
        pid                          The page to import to. [default: 0]

    Options:
        --update-records             Update existing records with the same UID instead of inserting new ones.
        --ignore-pid                 Prevent page ID correction for updated records
                                     (requires --update-records).
        --force-uid                  Force UIDs from the file.
        --import-mode[=IMPORT-MODE]  Set the import mode for specific records.
                                     Pattern: "{table}:{record}={mode}".
                                     Modes:
                                     - For new records: "force_uid", "exclude"
                                     - For existing records: "as_new", "ignore_pid",
                                       "respect_pid", "exclude"
                                     Examples: "pages:987=force_uid", "tt_content:1=as_new",
                                               etc. (multiple values allowed)
        --enable-log                 Log all database actions.
