.. include:: /Includes.rst.txt

.. _breaking-97214:

==============================================================
Breaking: #97214 - Use UploadedFile objects instead of $_FILES
==============================================================

See :issue:`97214`

Description
===========

The TYPO3 request already contains a "disentangled" array of UploadedFile
objects. With this change, these UploadedFile objects are now used instead
of the superglobal :php:`$_FILES` in Extbase requests.

Additionally, the FAL ResourceStorage has been adjusted for handling
UploadedFile objects and the ExtensionManager upload handling has been
adjusted.

The next step would be to further adjust FAL to use only PSR provided
methods for handling uploaded files and implementing an API for file
uploads in Extbase.

Impact
======

The global :php:`$_FILES` object is not used in Extbase or the extension
manager anymore, instead the PSR request is used.

Affected Installations
======================

All installations extending the TYPO3 Core ResourceStorage object and
overwriting the :php:`addUploadedFile` method.

Migration
=========

Extension authors extending the TYPO3 Core resource storage and implementing
their own handling of :php:`addUploadedFile` need to allow objects of type
:php:`UploadedFile` in addition to the old array from global :php:`$_FILES`.

To do so, switch the type annotation to :php:`array|UploadedFile` and add code that
handles :php:`UploadedFile` objects and arrays.

Example
^^^^^^^

..  code-block:: php

    if ($uploadedFileData instanceof UploadedFile) {
        $localFilePath = $uploadedFileData->getTemporaryFileName();
        if ($targetFileName === null) {
            $targetFileName = $uploadedFileData->getClientFilename();
        }
        $size = $uploadedFileData->getSize();
    } else {
        $localFilePath = $uploadedFileData['tmp_name'];
        if ($targetFileName === null) {
            $targetFileName = $uploadedFileData['name'];
        }
        $size = $uploadedFileData['size'];
    }

.. index:: PHP-API, NotScanned, ext:extbase
