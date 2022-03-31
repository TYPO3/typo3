.. include:: /Includes.rst.txt

=================================================================
Feature: #90548 - Download multiple files and folders in filelist
=================================================================

See :issue:`90548`

Description
===========

From time to time, editors might need to download files and folders,
which are stored in the TYPO3 installation. Therefore, the filelist
module has been improved to provide a couple of possibilities for
downloading the stored files and folders.

The action bar on the top of the listing now features the "Download"
option. It is shown, as soon as a file or folder is selected. It can
therefore be used to download a specific selection of files and folders.

The "Download" option has furthermore been added to the context menu as
well as the secondary menu. Those options can be used to download
a single file or folder.

Administrators can furthermore specify, which file extensions are allowed
for their users to be downloaded. Therefore, following user TSconfig is
available, expecting a comma-separated list of file extensions:

.. code-block:: typoscript

   # Either an allow list
   options.file_list.fileDownload.allowedFileExtensions = png,svg,pdf

   # or a deny list
   options.file_list.fileDownload.disallowedFileExtensions = yaml,exe,html

It's also possible to completely disable the file download for users:

.. code-block:: typoscript

    options.file_list.fileDownload.enabled = 0

.. note::

    When downloading folders, all readable subfolders and their files
    are included in the generated ZIP file as well.

Impact
======

It's now possible to download files and folders in the filelist module.

.. index:: Backend, ext:filelist
