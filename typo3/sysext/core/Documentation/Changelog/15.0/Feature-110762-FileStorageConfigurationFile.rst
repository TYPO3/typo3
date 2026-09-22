..  include:: /Includes.rst.txt

..  _feature-110762-1789862502:

==================================================
Feature: #110762 - File storage configuration file
==================================================

See :issue:`110762`

Description
===========

File storages are now defined in :file:`config/system/file-storages.yaml`,
see :ref:`breaking-110762-1789862500` for the format and the migration. This
comes with new API for extensions.

Reading and writing the configuration
-------------------------------------

:php:`\TYPO3\CMS\Core\Resource\Configuration\FileStorageConfiguration`
returns the normalized definitions with all defaults applied via
:php:`getAll()` and :php:`get(int $identifier)`.
:php:`\TYPO3\CMS\Core\Resource\Configuration\FileStorageConfigurationWriter`
changes the file: :php:`write(int $identifier, array $entry)` adds or replaces
one storage, :php:`remove(int $identifier)` deletes one,
:php:`apply(array $entries, array $removals)` changes several storages in one
step, for example to move the default flag, and
:php:`writeAll(array $storages)` writes a complete set into an empty file.
The writer works on the file as written on disk, so :yaml:`%env()%`
placeholders of untouched entries survive. It validates the resulting set,
imported files included, writes the file atomically and restores the
previous file if the result read back is not the expected one. The writer is
meant for command line tools and set up code, not for regular web requests.

After a write, the PSR-14 event
:php:`\TYPO3\CMS\Core\Resource\Event\FileStorageConfigurationChangedEvent`
is dispatched. TYPO3 itself uses it to flush the storage repository.

:php:`StorageRepository->addLocalStorage(string $name, string $basePath,
bool $default = false)` is the shortcut for extensions that add a local
directory as a storage programmatically. It never hands out an identifier
that is still referenced by files, processed files, file mounts or folder
collections.

Impact
======

Storages can be versioned, reviewed and deployed with the project and differ
per environment through placeholders.

..  index:: FAL, PHP-API, YAML, ext:core
