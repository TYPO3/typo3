:navigaton-title: Import

..  include:: /Includes.rst.txt
..  _import:

===========================================
Importing `.t3d` files in the TYPO3 backend
===========================================

The import functionality is only available for admin users and Backend
users for which the Page TSconfig option
:ref:`options.impexp.enableImportForNonAdminUser
<t3tsref:useroptions-impexp-enableImportForNonAdminUser>` has been enabled.

..  contents:: Table of contents

..  note::

    Make sure that any required extensions are installed and the database
    schema is up-to-date before starting the import. Otherwise, the data
    related to non-existing tables will not be imported.

..  _import-open-module:

Open the import module
======================

In the page tree, right-click the page you want to import to (1) and
select :guilabel:`More options ...  > Import` (2).

..  include:: /Images/AutomaticScreenshots/ContextMenuImport.rst.txt

..  _import-upload-file:

Upload the export file
======================

On the second tab of the import module you can upload the export file
to your target TYPO3 instance.

Select the file to upload (1) and click the :guilabel:`Upload files`
button (2).

Then switch to the :guilabel:`Import` tab (3).

..  include:: /Images/AutomaticScreenshots/UploadImport.rst.txt

..  _import-configure-settings:

Configure the import settings
=============================

On the first tab of the import module you can configure the import.

First select the uploaded export file (1). Then adjust the general
settings (2). Finally, press the :guilabel:`Preview` button (3).

- Checking :guilabel:`Update records` means that existing records with
  the same UID will be updated instead of newly inserted.

- Checking :guilabel:`Do not show differences in records` prevents
  calculation of differences between existing and imported records.
  Note: The compare function is currently broken and therefore disabled
  in the screenshot.

..  include:: /Images/AutomaticScreenshots/ConfigureImport.rst.txt

..  _import-review-data:

Review the data to be imported
==============================

A tree with the records to be imported is displayed below the
configuration form (1). If you change any of the options (2), you can
reload this preview with the :guilabel:`Preview` button (3).

..  include:: /Images/AutomaticScreenshots/CheckAndPerformImport.rst.txt

..  _import-execute:

Execute the import
==================

Click the :guilabel:`Import` button to execute the import process.
