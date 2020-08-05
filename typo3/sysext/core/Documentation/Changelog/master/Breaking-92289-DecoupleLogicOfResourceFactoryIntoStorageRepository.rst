.. include:: ../../Includes.txt

===========================================================================
Breaking: #92289 - Decouple logic of ResourceFactory into StorageRepository
===========================================================================

See :issue:`92289`

Description
===========

The ResourceFactory was initially created for the File Abstraction Layer (FAL)
as a Factory class, which created PHP objects.

However, in the recent years, it became more apparent that it is more useful to
separate the concerns of the creation and retrieving of existing information.

For this reason, the StorageRepository is now handling the creation of
ResourceStorage objects. This layer accesses the Database and the needed Driver
objects and configuration.

The StorageRepository class does not extend from AbstractRepository anymore,
and is available standalone.

Most of the logic in the ResourceFactory concerning Storages has been moved to
StorageRepository, which has a lot of options available now.

The following methods within ResourceFactory have been marked
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

Checking StorageRepository for an instance of AbstractRepository
will have different results.


Affected Installations
======================

TYPO3 installations with specific third-party extensions working with the FAL
API directly might use the existing functionality.


Migration
=========

Migrate to the StorageRepository API in the third-party extension code.

.. index:: FAL, PHP-API, FullyScanned, ext:core
