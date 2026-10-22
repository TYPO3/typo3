.. include:: /Includes.rst.txt

.. _breaking-92289:

===========================================================================
Breaking: #92289 - Decouple logic of ResourceFactory into StorageRepository
===========================================================================

See :issue:`92289`

Description
===========

The :php-short:`\TYPO3\CMS\Core\Resource\ResourceFactory` class was initially created for the File Abstraction Layer (FAL)
as a Factory class, which created PHP objects.

However, in the recent years, it became apparent that it is more useful to
separate the concerns of the creation and retrieving of existing information.

For this reason, the :php-short:`\TYPO3\CMS\Core\Resource\StorageRepository` class is now handling the creation of
:php-short:`\TYPO3\CMS\Core\Resource\ResourceStorage` objects. This layer accesses the Database and the needed Driver
objects and configuration.

The :php-short:`\TYPO3\CMS\Core\Resource\StorageRepository` class does not extend from :php-short:`\TYPO3\CMS\Core\Resource\AbstractRepository` anymore,
and is available standalone.

Most of the logic in the :php-short:`\TYPO3\CMS\Core\Resource\ResourceFactory` concerning Storages has been moved to
:php-short:`\TYPO3\CMS\Core\Resource\StorageRepository`, which has a lot of options available now.

The following methods within :php-short:`\TYPO3\CMS\Core\Resource\ResourceFactory` have been marked
as internal, and are kept for backwards-compatibility without deprecation:

* :php-short:`\TYPO3\CMS\Core\Resource\ResourceFactory->getDefaultStorage()`
* :php-short:`\TYPO3\CMS\Core\Resource\ResourceFactory->getStorageObject()`
* :php-short:`\TYPO3\CMS\Core\Resource\ResourceFactory->convertFlexFormDataToConfigurationArray()`
* :php-short:`\TYPO3\CMS\Core\Resource\ResourceFactory->createStorageObject()`
* :php-short:`\TYPO3\CMS\Core\Resource\ResourceFactory->createFolderObject()`
* :php-short:`\TYPO3\CMS\Core\Resource\ResourceFactory->getFileObjectByStorageAndIdentifier()`
* :php-short:`\TYPO3\CMS\Core\Resource\ResourceFactory->getStorageObjectFromCombinedIdentifier()`

The following method has been removed

* :php-short:`\TYPO3\CMS\Core\Resource\ResourceFactory->getDriverObject()`


Impact
======

Calling the removed method will throw a fatal error.

Checking :php-short:`\TYPO3\CMS\Core\Resource\StorageRepository` for an instance of :php-short:`\TYPO3\CMS\Core\Resource\AbstractRepository`
will have different results.


Affected Installations
======================

TYPO3 installations with specific third-party extensions working with the FAL
API directly might use the existing functionality.


Migration
=========

Migrate to the :php-short:`\TYPO3\CMS\Core\Resource\StorageRepository` API in the third-party extension code.

.. index:: FAL, PHP-API, FullyScanned, ext:core
