.. include:: /Includes.rst.txt

.. _feature-104526-1722603089:

===============================================================================
Feature: #104526 - Provide validators for PSR-7 UploadedFile objects in Extbase
===============================================================================

See :issue:`104526`

Description
===========

4 new extbase validators have been added to allow common validation tasks of a
PSR-7 :php:`UploadedFile` object or an :php:`ObjectStorage` containing PSR-7
:php:`UploadedFile` objects.

Note, that the new validators can only be applied to the TYPO3 implementation
of the PSR-7 :php:`UploadedFileInterface` because they validate the uploaded
files before it has been moved.

Custom implementations of the :php:`UploadedFileInterface` must continue to
implement their own validators.

FileNameValidator
-----------------

This validator ensures, that files with PHP executable file extensions can not
be uploaded. The validator has no options.

FileSizeValidator
-----------------

This validator can be used to validate an uploaded file against a given minimum
and maximum file size.

Validator options:

* :php:`minimum` - The minimum size as string (e.g. 100K)
* :php:`maximum` - The maximum size as string (e.g. 100K)

MimeTypeValidator
-----------------

This validator can be used to validate an uploaded file against a given set
of accepted MIME types. The validator additionally verifies, that the given
file extension of the uploaded file matches allowed file extensions for the
detected mime type.

Validator options:

* :php:`allowedMimeTypes` - An array of allowed MIME types
* :php:`ignoreFileExtensionCheck` - If set to "true", it is checked, the file
  extension check is disabled

ImageDimensionsValidator
------------------------

This validator can be used to validate an uploaded image for given image
dimensions. The validator must only be used, when it is ensured, that the
uploaded file is an image (e.g. by validating the MIME type).

Validator options:

* :php:`width` - Fixed width of the image as integer
* :php:`height` - Fixed height of the image as integer
* :php:`minWidth` - Minimum width of the image as integer. Default is `0`
* :php:`maxWidth` - Maximum width of the image as integer. Default is `PHP_INT_MAX`
* :php:`minHeight` - Minimum height of the image as integer. Default is `0`
* :php:`maxHeight` - Maximum height of the image as integer. Default is `PHP_INT_MAX`


Impact
======

TYPO3 extension autors can now use the new validators to validate a given
:php:`UploadedFile` object.

.. index:: Backend, ext:extbase
