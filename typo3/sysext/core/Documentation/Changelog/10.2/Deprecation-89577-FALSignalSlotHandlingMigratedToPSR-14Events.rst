.. include:: /Includes.rst.txt

=======================================================================
Deprecation: #89577 - FAL SignalSlot handling migrated to PSR-14 events
=======================================================================

See :issue:`89577`

Description
===========

Within the File Abstraction Layer, all "Signals" of Extbase's SignalSlot dispatcher have been migrated to PSR-14 events.

For this reason, all FAL-related Signals have been migrated to PSR-14 event listeners which are prioritized as the
first listener to be executed when an Event is fired.

The following interface has been deprecated and will be removed in TYPO3 v11:

- :php:`\TYPO3\CMS\Core\Resource\ResourceFactoryInterface`

The following constants have been deprecated and will be removed in TYPO3 v11:

- :php:`\TYPO3\CMS\Core\Resource\ResourceFactoryInterface::SIGNAL_PreProcessStorage`
- :php:`\TYPO3\CMS\Core\Resource\ResourceFactoryInterface::SIGNAL_PostProcessStorage`
- :php:`\TYPO3\CMS\Core\Resource\ResourceStorageInterface::SIGNAL_PostFileAdd`
- :php:`\TYPO3\CMS\Core\Resource\ResourceStorageInterface::SIGNAL_PostFileCopy`
- :php:`\TYPO3\CMS\Core\Resource\ResourceStorageInterface::SIGNAL_PostFileCreate`
- :php:`\TYPO3\CMS\Core\Resource\ResourceStorageInterface::SIGNAL_PostFileDelete`
- :php:`\TYPO3\CMS\Core\Resource\ResourceStorageInterface::SIGNAL_PostFileMove`
- :php:`\TYPO3\CMS\Core\Resource\ResourceStorageInterface::SIGNAL_PostFileRename`
- :php:`\TYPO3\CMS\Core\Resource\ResourceStorageInterface::SIGNAL_PostFileReplace`
- :php:`\TYPO3\CMS\Core\Resource\ResourceStorageInterface::SIGNAL_PostFileSetContents`
- :php:`\TYPO3\CMS\Core\Resource\ResourceStorageInterface::SIGNAL_PostFolderAdd`
- :php:`\TYPO3\CMS\Core\Resource\ResourceStorageInterface::SIGNAL_PostFolderCopy`
- :php:`\TYPO3\CMS\Core\Resource\ResourceStorageInterface::SIGNAL_PostFolderDelete`
- :php:`\TYPO3\CMS\Core\Resource\ResourceStorageInterface::SIGNAL_PostFolderMove`
- :php:`\TYPO3\CMS\Core\Resource\ResourceStorageInterface::SIGNAL_PostFolderRename`
- :php:`\TYPO3\CMS\Core\Resource\ResourceStorageInterface::SIGNAL_PreFileAdd`
- :php:`\TYPO3\CMS\Core\Resource\ResourceStorageInterface::SIGNAL_PreFileCopy`
- :php:`\TYPO3\CMS\Core\Resource\ResourceStorageInterface::SIGNAL_PreFileCreate`
- :php:`\TYPO3\CMS\Core\Resource\ResourceStorageInterface::SIGNAL_PreFileDelete`
- :php:`\TYPO3\CMS\Core\Resource\ResourceStorageInterface::SIGNAL_PreFileMove`
- :php:`\TYPO3\CMS\Core\Resource\ResourceStorageInterface::SIGNAL_PreFileRename`
- :php:`\TYPO3\CMS\Core\Resource\ResourceStorageInterface::SIGNAL_PreFileReplace`
- :php:`\TYPO3\CMS\Core\Resource\ResourceStorageInterface::SIGNAL_PreFileSetContents`
- :php:`\TYPO3\CMS\Core\Resource\ResourceStorageInterface::SIGNAL_PreFolderAdd`
- :php:`\TYPO3\CMS\Core\Resource\ResourceStorageInterface::SIGNAL_PreFolderCopy`
- :php:`\TYPO3\CMS\Core\Resource\ResourceStorageInterface::SIGNAL_PreFolderDelete`
- :php:`\TYPO3\CMS\Core\Resource\ResourceStorageInterface::SIGNAL_PreFolderMove`
- :php:`\TYPO3\CMS\Core\Resource\ResourceStorageInterface::SIGNAL_PreFolderRename`
- :php:`\TYPO3\CMS\Core\Resource\ResourceStorageInterface::SIGNAL_PreGeneratePublicUrl`
- :php:`\TYPO3\CMS\Core\Resource\ResourceStorageInterface::SIGNAL_SanitizeFileName`
- :php:`\TYPO3\CMS\Core\Resource\Service\FileProcessingService::SIGNAL_PreFileProcess`
- :php:`\TYPO3\CMS\Core\Resource\Service\FileProcessingService::SIGNAL_PostFileProcess`

Impact
======

Calling the Signals still works as before, without any deprecation message triggered in order to still be fully working.
However, they will likely be removed and stop working in TYPO3 v11.0.

All interfaces and constants which only existed for Signal-Slot related handling have been marked as deprecated.
The ExtensionScanner will detect any usages of the PHP symbols.


Affected Installations
======================

TYPO3 installations with extensions that hook into FAL-related functionality, e.g. "secure downloads" extension.


Migration
=========

It is highly recommended to use the PSR-14 events and create custom event listeners and not depend on Signals to be
executed in FAL anymore.

See all core examples, read the documentation about PSR-14 events and investigate especially the :php:`SlotReplacement`
PHP class on what can listened and modified.

.. index:: FAL, PHP-API, PartiallyScanned, ext:core
