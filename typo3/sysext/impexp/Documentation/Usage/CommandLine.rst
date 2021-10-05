.. include:: /Includes.rst.txt

.. _command_line:

==================
Command line usage
==================

The Import/Export tool can be accessed from the command line via
:ref:`Symfony Console Commands (cli) <t3coreapi:symfony-console-commands-cli>`.

The advantage of using the CLI is that there is no PHP time limit, therefore larger page-trees can
be exported.

You can see the complete list of options by calling the help command:

.. code-block:: bash

   vendor/bin/typo3 help impexp:export

.. note::
   If your TYPO3 installation is not based on composer you can run the command
   with :bash:`typo3/sysext/core/bin/typo3 impexp:export` instead.

Exports and imports can be fine-tuned with the same options
that are available in the backend GUI.

Export
======

Exporting a TYPO3 page tree without PHP time limit is possible via
:ref:`Symfony Console Commands (cli) <symfony-console-commands-cli>`.

.. code-block:: bash
   :caption: Composer based installation

   vendor/bin/typo3 impexp:export [options] [--] [<filename>]


This exports the entire TYPO3 page tree (or selected parts of it) to a data file that's
either XML or T3D depending on which option is selected.

Import
======

Importing a TYPO3 page tree without a PHP time limit is possible via
:ref:`Symfony Console Commands (cli) <symfony-console-commands-cli>`.

.. code-block:: bash
   :caption: Composer based installation

   vendor/bin/typo3 impexp:import [options] [--] [<filename>]

.. code-block:: bash
   :caption: The following options available when importing

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
