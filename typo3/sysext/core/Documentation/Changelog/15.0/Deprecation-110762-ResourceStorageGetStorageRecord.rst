..  include:: /Includes.rst.txt

..  _deprecation-110762-1789862501:

==========================================================
Deprecation: #110762 - ResourceStorage->getStorageRecord()
==========================================================

See :issue:`110762`

Description
===========

File storages are configured in :file:`config/system/file-storages.yaml`
instead of the database table :sql:`sys_file_storage`, see
:ref:`breaking-110762-1789862500`. The method
:php:`\TYPO3\CMS\Core\Resource\ResourceStorage->getStorageRecord()` returned
the database row of the storage. It has been deprecated and now returns an
array in the shape of the former row, mapped from the configuration, so that
existing code keeps working for one major version.

Impact
======

Calling the method triggers a PHP :php:`E_USER_DEPRECATED` error. The
extension scanner reports usages.

Affected installations
======================

Installations with extensions calling :php:`getStorageRecord()`, for example
to read :php:`is_public`, :php:`processingfolder` or the driver
configuration of a storage.

Migration
=========

Use the getters of :php-short:`\TYPO3\CMS\Core\Resource\ResourceStorage` instead: :php:`getUid()`,
:php:`getName()`, :php:`getDriverType()`,
:php:`isDefault()`, :php:`isBrowsable()`, :php:`isPublic()`,
:php:`isWritable()`, :php:`isOnline()`, :php:`autoExtractMetadataEnabled()`,
:php:`getProcessingFolder()` and :php:`getConfiguration()` for the driver
options. The normalized definition of a storage is also available from
:php:`\TYPO3\CMS\Core\Resource\Configuration\FileStorageConfiguration->get()`.

..  index:: FAL, PHP-API, FullyScanned, ext:core
