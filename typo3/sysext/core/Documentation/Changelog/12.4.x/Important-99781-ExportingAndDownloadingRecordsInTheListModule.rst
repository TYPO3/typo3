.. include:: /Includes.rst.txt

.. _important-99781-1707215955:

========================================================================
Important: #99781 - Exporting and downloading records in the list module
========================================================================

See :issue:`99781`

Description
===========

There are two different options for exporting records in the
:guilabel:`Web->List` module.

One is using the export functionality, which is provided by EXT:impexp and is
available via the "Export" docheader button in the single table view. It is
possible to manage the display of the button using the Page TSconfig
:typoscript:`mod.web_list.noExportRecordsLinks` option. However, the export
functionality is by default disabled for non-admin users, making the button
not showing up unless the functionality is explicitly enabled for the user
with the user TSconfig :typoscript:`options.impexp.enableExportForNonAdminUser`
option.

The "Download" functionality is available via the "Download" button in each
tables header row. It is available in both, the list and also the single table
view and can be managed using the Page TSconfig
:typoscript:`mod.web_list.displayRecordDownload` option, which is enabled by
default. Next to the general option is it also possible to set this option on
a per-table basis using the
:typoscript:`mod.web_list.table.<tablename>.displayRecordDownload` option.
In case this option is set, it takes precedence over the general option.

.. code-block:: typoscript

    # Page TSconfig
    mod.web_list {
        # Disable "Export" button in docheader
        noExportRecordsLinks = 1

        # Generally disable "Download" button
        displayRecordDownload = 0

        # Enable "Download" button for table "tt_content"
        table.tt_content.displayRecordDownload = 1
    }

.. index:: Backend, PHP-API, TSConfig, ext:backend
