..  include:: /Includes.rst.txt

..  _important-107735:

==================================================================
Important: #107735 - Internal methods removed from ResourceFactory
==================================================================

See :issue:`107735`

Description
===========

The following internal methods have been removed from
:php:`\TYPO3\CMS\Core\Resource\ResourceFactory`:

- :php:`getDefaultStorage()`
- :php:`getStorageObject()`
- :php:`createFolderObject()`
- :php:`getFileObjectByStorageAndIdentifier()`

These methods were marked as :php:`@internal` and are replaced by using
:php:`\TYPO3\CMS\Core\Resource\StorageRepository` directly for better
separation of concerns. However, some of these methods might have been used
in custom extensions despite being marked as internal.

Migration
=========

Instead of using the removed methods from :php:`ResourceFactory`, use the
appropriate methods from :php:`StorageRepository` or direct access to
:php:`ResourceStorage`:

..  code-block:: php

    // Before
    $defaultStorage = $resourceFactory->getDefaultStorage();
    $storage = $resourceFactory->getStorageObject($uid);
    $folder = $resourceFactory->createFolderObject($storage, $identifier, $name);
    $file = $resourceFactory->getFileObjectByStorageAndIdentifier($storage, $fileIdentifier);

    // After
    $defaultStorage = $storageRepository->getDefaultStorage();
    $storage = $storageRepository->getStorageObject($uid);
    $folder = $storage->getFolder($identifier);
    $file = $storage->getFileByIdentifier($fileIdentifier);

..  index:: FAL, PHP-API, FullyScanned, ext:core
