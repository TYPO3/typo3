..  include:: /Includes.rst.txt

..  _breaking-110999-1788639260:

================================================================
Breaking: #110999 - LocalDriver throws FileDoesNotExistException
================================================================

See :issue:`110999`

Description
===========

The
:php:`\TYPO3\CMS\Core\Resource\Driver\LocalDriver::getFileInfoByIdentifier()`
method now throws the specific
:php:`\TYPO3\CMS\Core\Resource\Exception\FileDoesNotExistException` when the
requested file does not exist. Previously, the global
:php:`\InvalidArgumentException` was thrown.

Impact
======

Extensions that catch :php:`\InvalidArgumentException` when calling
:php:`LocalDriver::getFileInfoByIdentifier()` will no longer catch the
exception. Code that catches
:php:`\TYPO3\CMS\Core\Resource\Exception\ResourceDoesNotExistException`
continues to work because :php:`FileDoesNotExistException` extends it.

Affected installations
======================

Installations are affected when custom extension code directly or indirectly
calls :php:`LocalDriver::getFileInfoByIdentifier()` and catches
:php:`\InvalidArgumentException` for missing files.

Migration
=========

Catch :php:`FileDoesNotExistException` instead:

..  code-block:: php

    use TYPO3\CMS\Core\Resource\Exception\FileDoesNotExistException;

    try {
        $fileInfo = $driver->getFileInfoByIdentifier($identifier);
    } catch (FileDoesNotExistException) {
        // Handle the missing file.
    }

Code that handles missing files and folders alike can catch the parent
:php:`ResourceDoesNotExistException` instead.

..  index:: FAL, PHP-API, NotScanned, ext:core
