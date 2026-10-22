..  include:: /Includes.rst.txt

..  _deprecation-110762-1791443860:

==============================================================================
Deprecation: #110762 - StorageRepository->createLocalStorage() and legacy keys
==============================================================================

See :issue:`110762`

Description
===========

File storages are configured in :file:`config/system/file-storages.yaml`,
see :ref:`breaking-110762-1789862500`. Two remains of the former database
table are deprecated:

*   :php:`\TYPO3\CMS\Core\Resource\StorageRepository->createLocalStorage()`
    is replaced by :php:`addLocalStorage()`. The deprecated method keeps its
    signature and writes the file. A base path with the path type
    :php:`relative` is resolved against the public path as before, the
    description is ignored.
*   Listeners of
    :php:`\TYPO3\CMS\Core\Resource\Event\BeforeResourceStorageInitializationEvent`
    that pass the keys of the former :sql:`sys_file_storage` row to
    :php:`setRecord()`, such as :php:`is_public` or :php:`processingfolder`,
    or a FlexForm string as :php:`configuration`, are mapped to the
    normalized storage definition.

Impact
======

Both trigger a PHP :php:`E_USER_DEPRECATED` error. The extension scanner
reports calls of :php:`createLocalStorage()`.

Affected installations
======================

Installations with extensions that create local storages programmatically
or modify storage records in a listener of
:php-short:`\TYPO3\CMS\Core\Resource\Event\BeforeResourceStorageInitializationEvent`.

Migration
=========

Call :php:`addLocalStorage(string $name, string $basePath, bool $default =
false)`. Its base path is absolute or relative to the project path, so a
path formerly relative to the public path is prefixed accordingly, for
example :php:`'public/fileadmin/'` in a Composer based installation.

Listeners use the keys :php:`identifier`, :php:`default`, :php:`browsable`,
:php:`public`, :php:`writable`, :php:`online`, :php:`autoExtractMetadata`,
:php:`processingFolder` and an array as :php:`configuration`.

..  index:: FAL, PHP-API, PartiallyScanned, ext:core
