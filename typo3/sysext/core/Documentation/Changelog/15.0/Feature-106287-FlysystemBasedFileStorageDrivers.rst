..  include:: /Includes.rst.txt

..  _feature-106287-1792093589:

==================================================
Feature: #106287 - Flysystem-based storage drivers
==================================================

See :issue:`106287`

Description
===========

TYPO3 now ships `league/flysystem <https://flysystem.thephpleague.com/>`__
and lets any Flysystem adapter serve as a File Abstraction Layer (FAL)
storage, for example Amazon S3, Azure Blob Storage, Google Cloud Storage,
SFTP or WebDAV.

File storage drivers are registered with the new attribute
:php:`#[\TYPO3\CMS\Core\Attribute\AsFileStorageDriver]`. Its identifier is
used as driver of a storage in :file:`config/system/file-storages.yaml`.
The attribute is put on one of the following classes:

*   a Flysystem adapter factory, a service implementing
    :php:`\TYPO3\CMS\Core\Resource\Driver\FilesystemAdapterFactoryInterface`,
    which creates the adapter from the storage configuration. Such storages
    are handled by :php:`\TYPO3\CMS\Core\Resource\Driver\FlysystemDriver`.
*   a driver factory, a service implementing
    :php:`\TYPO3\CMS\Core\Resource\Driver\FileStorageDriverFactoryInterface`,
    which creates the driver from the storage configuration.
*   a driver class implementing
    :php:`\TYPO3\CMS\Core\Resource\Driver\DriverInterface`, which is created
    with the storage configuration as its only constructor argument, as
    before.

A Flysystem adapter factory is the recommended way to connect a storage
backend from TYPO3 v15 on. The driver layer of TYPO3 is meant to become a
thin layer between FAL and Flysystem: extend
:php:`\TYPO3\CMS\Core\Resource\Driver\AbstractFlysystemDriver` only where a
backend can do something Flysystem cannot express, and avoid implementing
:php:`\TYPO3\CMS\Core\Resource\Driver\DriverInterface` from scratch.

Factories are regular services, so they get their dependencies injected:

..  code-block:: php
    :caption: EXT:my_extension/Classes/Resource/S3AdapterFactory.php

    use Aws\S3\S3Client;
    use League\Flysystem\AwsS3V3\AwsS3V3Adapter;
    use League\Flysystem\FilesystemAdapter;
    use TYPO3\CMS\Core\Attribute\AsFileStorageDriver;
    use TYPO3\CMS\Core\Resource\Driver\FilesystemAdapterFactoryInterface;
    use TYPO3\CMS\Core\Resource\Exception\InvalidConfigurationException;

    #[AsFileStorageDriver(identifier: 'S3')]
    final readonly class S3AdapterFactory implements FilesystemAdapterFactoryInterface
    {
        public function __construct(
            private S3Client $client,
        ) {}

        public function createAdapter(array $configuration): FilesystemAdapter
        {
            if (empty($configuration['bucket'])) {
                throw new InvalidConfigurationException('Configure a bucket.', 1792100000);
            }
            return new AwsS3V3Adapter($this->client, $configuration['bucket'], $configuration['prefix'] ?? '');
        }
    }

The configuration of the storage is passed to the factory:

..  code-block:: yaml
    :caption: config/system/file-storages.yaml

    storages:
      2:
        name: Media bucket
        driver: S3
        configuration:
          bucket: my-media
          prefix: typo3
          baseUri: 'https://media.example.com/typo3/'

A driver that has to change the behaviour of the Flysystem driver extends
:php:`\TYPO3\CMS\Core\Resource\Driver\AbstractFlysystemDriver`, implements
:php:`createFilesystemAdapter()` and is registered by the attribute on the
driver class, or by a driver factory if it needs services.

The local driver of TYPO3 is registered with the attribute as :yaml:`Local`.
Drivers registered in
:php:`$GLOBALS['TYPO3_CONF_VARS']['SYS']['fal']['registeredDrivers']` keep
working. A driver registered with the attribute takes precedence over one
registered there with the same identifier, the latter is ignored and a
warning is logged.

Everything TYPO3 needs on top of Flysystem is handled by the base driver:

*   identifiers are canonicalized before they reach the adapter, including
    the lowercasing for storages configured with :yaml:`caseSensitive: false`,
    and cannot leave the storage
*   file and folder names are sanitized like in the local driver
*   folder listings support the file and folder filters, sorting, pagination
    and recursion of the backend file list, counting does not keep the listing
*   folders are renamed, moved and copied with their content
*   images and other files are processed from a local copy, read-only copies
    are removed when the file changes or when the driver is destructed
*   metadata from folder listings is reused for the file information, so
    remote adapters are not asked for each property of each file
*   files are streamed and dumped without loading them into memory

A storage is public if the configuration option :yaml:`baseUri` is set.
Public URLs that adapters such as the AWS S3 adapter generate themselves are
not used, as they generate URLs for private buckets as well. Otherwise the
files of the storage are delivered through the file dump eID.

Folders are counted by iterating their listing, as Flysystem has no API for
counting. A driver whose backend counts cheaper extends
:php:`\TYPO3\CMS\Core\Resource\Driver\AbstractFlysystemDriver` and
overrides :php:`countDirectoryItems()`.

Checksums of files, for example the SHA-1 hash TYPO3 stores in the file
index, are taken from the adapter if it provides them for the requested
algorithm. Otherwise, the file is read to calculate the checksum, which
means downloading it from a remote storage when it is indexed or updated.

MIME types follow the same rules as in local storages: a MIME type
configured for the file extension in
:php:`$GLOBALS['TYPO3_CONF_VARS']['SYS']['FileInfo']['fileExtensionToMimeType']`
takes precedence over the one of the adapter, and the registered MIME type
guessers refine the result, for example for XLIFF files reported as
:mimetype:`text/xml`. Adapters for local files should detect MIME types with
:php:`\League\MimeTypeDetection\FinfoMimeTypeDetector`, as the default
detector of Flysystem replaces results like :mimetype:`text/plain` with a
lookup of the file extension.

Impact
======

Extension authors can connect any storage backend that has a Flysystem
adapter to FAL with a few lines of code, instead of implementing the more
than 40 methods of :php:`\TYPO3\CMS\Core\Resource\Driver\DriverInterface`.
Factories registered with :php:`#[AsFileStorageDriver]` are services and
can use dependency injection, for example for a preconfigured API client,
which was not possible for drivers so far.
The local driver of TYPO3 is based on Flysystem as well, so every
installation uses it for its local storages, see
:ref:`breaking-106287-1792093600`.

Flysystem knows neither the access nor the creation time of a file, the
modification time is used for both. Read and write permissions are derived
from the storage capabilities, as Flysystem only knows the visibility of a
file.

..  index:: FAL, PHP-API, ext:core
