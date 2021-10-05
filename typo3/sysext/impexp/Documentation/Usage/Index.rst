.. include:: /Includes.rst.txt

.. _usage:

=====
Usage
=====

The Import/Export tool can be accessed via the :ref:`command line<command_line>`
by CLI (Symfony Console Commands) or :ref:`manually<manual_export>` from the
TYPO3 backend.

Users with admin rights can use both the import and the export functionality.
Editors with no admin rights can only use the export functionality (unless
it is disabled). Editors can only export content they have access to.

The import functionality can be used for :ref:`content updates<content-update>`
instead of importing the entire page tree and its content.

The export functionality can be used to export example content for use
:ref:`distributions<distributions>`.

It is also possible to save and load export data :ref:`presets<presets>` for reoccurring
export jobs.

.. toctree::
    :titlesonly:

    ManualExport
    ManualImport
    CommandLine
    Update
    Distributions
    Presets
