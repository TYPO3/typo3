.. include:: /Includes.rst.txt

==========================================
Feature: #92518 - Introduce FileViewHelper
==========================================

See :issue:`92518`

Description
===========

With :doc:`#92518 <../11.3/Feature-92518-DownloadAndFilenameOptionsAddedToFileDumpController>`,
the :php:`\TYPO3\CMS\Core\Controller\FileDumpController` has been extended with
new options to force the download of a file, as well as the option to define a
custom filename.

To ease the use of the file dump functionality, especially the newly introduced
options, a new ViewHelper :php:`TYPO3\CMS\Fluid\ViewHelpers\Link\FileViewHelper`
is added, which allows extension authors to easily create links to both public
and non-public files.

The usage is as following:

.. code-block:: html

   <f:link.file file="{file}" download="true" filename="alternative-name.jpg">
      Download file
   </f:link.file>

The above example will create a link to the given file, forcing a direct
download, while using the alternative filename.

In case the file is publicly accessible, a direct link will be used. Otherwise
the file dump functionality comes into play.

.. code-block:: html

   <!-- Public file -->
   <a href="https://example.com/fileadmin/path/to/file.jpg"
      download="alternative-name.jpg"
   >
      Download file
   </a>

   <!-- Non-public file -->
   <a href="https://example.com/index.php?eID=dumpFile&t=f&f=123&dl=1&fn=alternative-name.jpg&token=79bce812">
      Download file
   </a>

.. note::

   The :php:`file` argument accepts a
   :php:`\TYPO3\CMS\Core\Resource\FileInterface`. So either a
   :php:`\TYPO3\CMS\Core\Resource\File`,
   a :php:`\TYPO3\CMS\Core\Resource\FileReference` or a
   :php:`\TYPO3\CMS\Core\Resource\ProcessedFile` can be provided.

.. note::

   The :html:`filename` argument accepts an alternative filename. In case the
   provided filename contains a file extension, this must be the same
   as from the :html:`file` object. If file extensions is omitted, the original
   file extension is automatically appended to the given filename.

Impact
======

The new ViewHelper allows creating links to files - even non-public ones -
in a straightforward way within Fluid templates.

.. index:: FAL, Fluid, ext:fluid
