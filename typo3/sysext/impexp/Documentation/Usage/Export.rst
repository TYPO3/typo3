.. include:: /Includes.rst.txt

.. _export:

======
Export
======

The export functionality is only available for admin users and Backend
users for which the Page TSconfig option
:ref:`options.impexp.enableExportForNonAdminUser <t3tsconfig:useroptions-impexp-enableExportForNonAdminUser>`
has been enabled.

.. attention::

    The export functionality has the following security drawbacks:

    *  Export for editors is not limited on field level
    *  The "Save to filename" functionality saves to a shared folder, which
       other editors with different access rights may have access to.

    Thus, permissions should be handed out restrictively.

.. rst-class:: bignums

   1. Go to the export module

      In the page tree, right-click the page from which you want to start the
      export (1) and select :guilabel:`More options ... > Export` (2).

      .. include:: /Images/AutomaticScreenshots/ContextMenuExport.rst.txt

   2. Configure the export

      On the first tab of the export module you can fine-tune the export (1).

      If you want to export all data of the selected page including its
      subpages, select the "Infinite" option in the :guilabel:`Levels` selection
      box. Under :guilabel:`Include tables` you can limit the types of records
      to be exported.

      Under :guilabel:`Include relations to tables` you specify which relations
      of the records should be included in the export file. The related records
      will be also included - even if they are outside the pages selected for
      export.

      Under :guilabel:`Use static relations for tables` you select which
      relations of the records should be included in the export file - without
      including the related record. This is useful if the related record already
      exists in the target TYPO3 instance.

      If the same table is selected in :guilabel:`Include relations to tables`
      and :guilabel:`Use static relations for tables`, the relation is treated
      as static.

      The :guilabel:`Exclude disabled elements` checkbox means, when checked,
      that these records are excluded from export which are disabled according
      to their TCA configuration, e.g. by the "disabled", "starttime" or
      "endtime" fields. It is marked by default.

      Apply your changes via the :guilabel:`Update` button and repeat this step
      until the preview matches your expectations.

      .. include:: /Images/AutomaticScreenshots/ConfigureExport.rst.txt

   3. Check the included records

      All pages selected for export are listed in the upper part of the dialog
      (1).

      Below the dialog is a detailed list of all data to be exported (2). Here
      it is possible to exclude individual records. For some data types it is
      possible to make them editable manually.

      If the relation to records is lost, this is marked with an orange
      exclamation mark. Reasons for lost relations include records stored
      outside the page tree to be exported and excluded tables.

      Apply your changes by pressing the :guilabel:`Update` button and repeat
      this step until the preview is as you want it (3).

      Then switch to the :guilabel:`Advanced Options` tab (4).

      .. include:: /Images/AutomaticScreenshots/CheckExport.rst.txt

   4. Optionally select advanced export options

      In the third tab of the export module you can specify further export
      options (1).

      Checking :guilabel:`Save files in extra folder ..` means that files
      linked in records will be saved in a separate folder instead of being
      included directly in the export file. This is mandatory for use in
      :ref:`distributions<t3coreapi:distribution>` or when there are a large number of
      files that would otherwise bloat the export file and exhaust memory.
      The folder name pattern is "{filename}.files".

      Apply your changes by hitting the :guilabel:`Update` button (2) and switch
      to tab :guilabel:`File & Preset` (3) to start the export process.

      .. include:: /Images/AutomaticScreenshots/SelectAdvancedExportOptions.rst.txt

   5. Perform the export

      In the second tab of the export module you can specify the metadata of the
      export (1) before starting the export process.

      You can download the export file (2.a) or save it on your server (2.b).

      Currently it is necessary to choose saving on the server if the export is
      configured to save related files in a separate folder
      (see "Optionally select advanced export options" step).

      .. include:: /Images/AutomaticScreenshots/DownloadExport.rst.txt
