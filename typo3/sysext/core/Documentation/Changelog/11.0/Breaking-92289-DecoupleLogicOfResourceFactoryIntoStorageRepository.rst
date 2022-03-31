.. include:: /Includes.rst.txt

===========================================================================
Breaking: #92289 - Decouple logic of ResourceFactory into StorageRepository
===========================================================================

See :issue:`92289`

Description
===========

The :php:`ResourceFactory` class was initially created for the File Abstraction Layer (FAL)
as a Factory class, which created PHP objects.

However, in the recent years, it became apparent that it is more useful to
separate the concerns of the creation and retrieving of existing information.

For this reason, the :php:`StorageRepository` class is now handling the creation of
:php:`ResourceStorage` objects. This layer accesses the Database and the needed Driver
objects and configuration.

The :php:`StorageRepository` class does not extend from :php:`AbstractRepository` anymore,
and is available standalone.

Most of the logic in the :php:`ResourceFactory` concerning Storages has been moved to
:php:`StorageRepository`, which has a lot of options available now.

The following methods within :php:`ResourceFactory` have been marked
as internal, and are kept for backwards-compatibility without deprecation:

* :php:`ResourceFactory->getDefaultStorage()`
* :php:`ResourceFactory->getStorageObject()`
* :php:`ResourceFactory->convertFlexFormDataToConfigurationArray()`
* :php:`ResourceFactory->createStorageObject()`
* :php:`ResourceFactory->createFolderObject()`
* :php:`ResourceFactory->getFileObjectByStorageAndIdentifier()`
* :php:`ResourceFactory->getStorageObjectFromCombinedIdentifier()`

The following method has been removed

* :php:`ResourceFactory->getDriverObject()`


Impact
======

Calling the removed method will throw a fatal error.

Checking :php:`StorageRepository` for an instance of :php:`AbstractRepository`
will have different results.


Affected Installations
======================

TYPO3 installations with specific third-party extensions working with the FAL
API directly might use the existing functionality.


Migration
=========

Migrate to the :php:`StorageRepository` API in the third-party extension code.

.. index:: FAL, PHP-API, FullyScanned, ext:core
