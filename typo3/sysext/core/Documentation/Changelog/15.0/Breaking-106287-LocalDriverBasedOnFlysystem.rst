..  include:: /Includes.rst.txt

..  _breaking-106287-1792093600:

===================================================
Breaking: #106287 - Local driver based on Flysystem
===================================================

See :issue:`106287`

Description
===========

The local file storage driver
:php:`\TYPO3\CMS\Core\Resource\Driver\LocalDriver` now extends
:php:`\TYPO3\CMS\Core\Resource\Driver\AbstractFlysystemDriver` and accesses
files through the local adapter of Flysystem, see
:ref:`feature-106287-1792093589`.

Its public API is unchanged. The local specifics are kept: listings follow
symbolic links, new files and folders get the permissions and group
configured in :php:`$GLOBALS['TYPO3_CONF_VARS']['SYS']['fileCreateMask']`,
:php:`['folderCreateMask']` and :php:`['createGroup']`, folders are moved and
renamed at once, and files are processed and streamed in place.

The following protected methods of :php:`LocalDriver` have been removed or
changed:

*   :php:`retrieveFileAndFoldersInPath()` has been removed, listings are
    created by :php:`listDirectoryEntries()`, which returns Flysystem
    :php:`FileAttributes` and :php:`DirectoryAttributes` objects
*   :php:`sortDirectoryEntries()` and :php:`getDirectoryItemList()` are
    inherited from :php:`AbstractFlysystemDriver` and work on these objects
*   :php:`sanitizeFileName()`, :php:`isWithin()` and
    :php:`applyFilterMethodsToDirectoryItem()` are inherited from
    :php:`AbstractHierarchicalFilesystemDriver`

Errors of file operations are reported with the exceptions of Flysystem,
which implement :php:`\League\Flysystem\FilesystemException` and extend
:php:`\RuntimeException` like the exceptions thrown before. Deleting a file
that does not exist anymore no longer fails. Reading the contents of an
existing file that cannot be read throws an exception instead of returning
an empty string. Creating a file that already
exists throws an
:php:`\TYPO3\CMS\Core\Resource\Exception\ExistingTargetFileNameException`
instead of keeping the existing file, the backend reports this as for other
file operations.

The local driver is registered with
:php:`#[\TYPO3\CMS\Core\Attribute\AsFileStorageDriver]`, see
:ref:`feature-106287-1792093589`.
:php:`$GLOBALS['TYPO3_CONF_VARS']['SYS']['fal']['registeredDrivers']` is
empty by default and no longer contains the :php:`Local` entry.

A local driver whose configuration is invalid, for example because its base
path does not exist, throws an
:php:`\TYPO3\CMS\Core\Resource\Exception\InvalidConfigurationException` for
operations on files, instead of resolving them against the root of the file
system.


Impact
======

Classes extending :php:`LocalDriver` that call or override the removed or
changed protected methods break. Code that reads the local driver from
:php:`$GLOBALS['TYPO3_CONF_VARS']['SYS']['fal']['registeredDrivers']` does
not find it anymore.

An extension that replaces the local driver by registering another class as
:php:`$GLOBALS['TYPO3_CONF_VARS']['SYS']['fal']['registeredDrivers']['Local']`
is ignored: storages with the driver :yaml:`Local` use
:php:`LocalDriver` again, and only a warning is logged.


Affected installations
======================

Installations with extensions that extend :php:`LocalDriver` and rely on its
protected methods for directory listings, that read the registered drivers
from :php:`$GLOBALS['TYPO3_CONF_VARS']`, or that register their own class
for the driver identifier :php:`Local` there. The extension scanner does not
detect this.


Migration
=========

Override :php:`listDirectoryEntries()` instead of
:php:`retrieveFileAndFoldersInPath()` to change which entries are listed,
and :php:`sortDirectoryEntries()` with the Flysystem attribute objects to
change the sorting.

A replacement of the local driver gets its own identifier with
:php:`#[\TYPO3\CMS\Core\Attribute\AsFileStorageDriver]`, and the storages
that should use it name this identifier as :yaml:`driver` in
:file:`config/system/file-storages.yaml`.

Drivers for other backends than the local file system are best provided by
a service implementing
:php:`\TYPO3\CMS\Core\Resource\Driver\FilesystemAdapterFactoryInterface`,
which only creates the matching Flysystem adapter, see
:ref:`feature-106287-1792093589`.

Use :php:`\TYPO3\CMS\Core\Resource\Driver\DriverRegistry->driverExists()`
and :php:`->createDriver()` instead of reading
:php:`$GLOBALS['TYPO3_CONF_VARS']['SYS']['fal']['registeredDrivers']`.

..  index:: FAL, PHP-API, NotScanned, ext:core
