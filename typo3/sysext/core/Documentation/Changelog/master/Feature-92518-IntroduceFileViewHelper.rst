.. include:: ../../Includes.txt

==========================================
Feature: #92518 - Introduce FileViewHelper
==========================================

See :issue:`92518`

Description
===========

With :doc:`#92518 <../master/Feature-92518-DownloadAndFilenameOptionsAddedToFileDumpController.rst>`,
the :php:`FileDumpController` has been extended with new options to force
the download of a file, as well as the option to define a custom filename.

To ease the use of the file dump functionality, especially the newly introduced
options, a new view helper :php:`TYPO3\CMS\Fluid\ViewHelpers\Link\FileViewHelper`
is added, which allows extension authors to easily create links to both public
and non-public files.

The usage is as following:

.. code-block:: html

   <f:link.file file="{file}" download="true" filename="alternative-name.jpg">Download file</f:link.file>

The above example will create a link to the file `123`, forcing a direct
download, while using the alternative filename.

In case the file is publicly accessible, a direct link will be used. Otherwise
the file dump functionality comes into play.

.. code-block:: html

   <!-- Public file -->
   <a href="https://example.com/fileadmin/path/to/file.jpg" download="alternative-name.jpg">Download file</a>

   <!-- Non-public file -->
   <a href="https://example.com/index.php?eID=dumpFile&t=f&f=123&dl=1&fn=alternative-name.jpg&token=79bce812">Download file</a>

.. note::

   The `file` argument accepts a :php:`FileInterface`. So either a :php:`File`,
   a :php:`FileReference` or a :php:`ProcessedFile` can be provided.

.. note::

   The `filename` argument accepts an alternative filename. In case the
   provided filename contains a file extension, this must be the same
   as from `file`. If file extensions is omitted, the original file
   extension is automatically appended to the given filename.

Impact
======

The new view helper allows creating links to files - even non-public ones -
in a straightforward way within fluid templates.

.. index:: FAL, Fluid, ext:fluid
