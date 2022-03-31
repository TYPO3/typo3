.. include:: /Includes.rst.txt

====================================================================
Feature: #89577 - New PSR-14 based events for File Abstraction Layer
====================================================================

See :issue:`89577`

Description
===========

The following new PSR-14 based Events have been introduced:

- :php:`\TYPO3\CMS\Core\Classes\Resource\Event\AfterFileAddedEvent`
- :php:`\TYPO3\CMS\Core\Classes\Resource\Event\AfterFileAddedToIndexEvent`
- :php:`\TYPO3\CMS\Core\Classes\Resource\Event\AfterFileContentsSetEvent`
- :php:`\TYPO3\CMS\Core\Classes\Resource\Event\AfterFileCopiedEvent`
- :php:`\TYPO3\CMS\Core\Classes\Resource\Event\AfterFileCreatedEvent`
- :php:`\TYPO3\CMS\Core\Classes\Resource\Event\AfterFileDeletedEvent`
- :php:`\TYPO3\CMS\Core\Classes\Resource\Event\AfterFileMarkedAsMissingEvent`
- :php:`\TYPO3\CMS\Core\Classes\Resource\Event\AfterFileMetaDataCreatedEvent`
- :php:`\TYPO3\CMS\Core\Classes\Resource\Event\AfterFileMetaDataDeletedEvent`
- :php:`\TYPO3\CMS\Core\Classes\Resource\Event\AfterFileMetaDataUpdatedEvent`
- :php:`\TYPO3\CMS\Core\Classes\Resource\Event\AfterFileMovedEvent`
- :php:`\TYPO3\CMS\Core\Classes\Resource\Event\AfterFileProcessingEvent`
- :php:`\TYPO3\CMS\Core\Classes\Resource\Event\AfterFileRemovedFromIndexEvent`
- :php:`\TYPO3\CMS\Core\Classes\Resource\Event\AfterFileRenamedEvent`
- :php:`\TYPO3\CMS\Core\Classes\Resource\Event\AfterFileReplacedEvent`
- :php:`\TYPO3\CMS\Core\Classes\Resource\Event\AfterFileUpdatedInIndexEvent`
- :php:`\TYPO3\CMS\Core\Classes\Resource\Event\AfterFolderAddedEvent`
- :php:`\TYPO3\CMS\Core\Classes\Resource\Event\AfterFolderCopiedEvent`
- :php:`\TYPO3\CMS\Core\Classes\Resource\Event\AfterFolderDeletedEvent`
- :php:`\TYPO3\CMS\Core\Classes\Resource\Event\AfterFolderMovedEvent`
- :php:`\TYPO3\CMS\Core\Classes\Resource\Event\AfterFolderRenamedEvent`
- :php:`\TYPO3\CMS\Core\Classes\Resource\Event\AfterResourceStorageInitializationEvent`
- :php:`\TYPO3\CMS\Core\Classes\Resource\Event\BeforeFileAddedEvent`
- :php:`\TYPO3\CMS\Core\Classes\Resource\Event\BeforeFileContentsSetEvent`
- :php:`\TYPO3\CMS\Core\Classes\Resource\Event\BeforeFileCopiedEvent`
- :php:`\TYPO3\CMS\Core\Classes\Resource\Event\BeforeFileCreatedEvent`
- :php:`\TYPO3\CMS\Core\Classes\Resource\Event\BeforeFileDeletedEvent`
- :php:`\TYPO3\CMS\Core\Classes\Resource\Event\BeforeFileMovedEvent`
- :php:`\TYPO3\CMS\Core\Classes\Resource\Event\BeforeFileProcessingEvent`
- :php:`\TYPO3\CMS\Core\Classes\Resource\Event\BeforeFileRenamedEvent`
- :php:`\TYPO3\CMS\Core\Classes\Resource\Event\BeforeFileReplacedEvent`
- :php:`\TYPO3\CMS\Core\Classes\Resource\Event\BeforeFolderAddedEvent`
- :php:`\TYPO3\CMS\Core\Classes\Resource\Event\BeforeFolderCopiedEvent`
- :php:`\TYPO3\CMS\Core\Classes\Resource\Event\BeforeFolderDeletedEvent`
- :php:`\TYPO3\CMS\Core\Classes\Resource\Event\BeforeFolderMovedEvent`
- :php:`\TYPO3\CMS\Core\Classes\Resource\Event\BeforeFolderRenamedEvent`
- :php:`\TYPO3\CMS\Core\Classes\Resource\Event\BeforeResourceStorageInitializationEvent`
- :php:`\TYPO3\CMS\Core\Classes\Resource\Event\EnrichFileMetaDataEvent`
- :php:`\TYPO3\CMS\Core\Classes\Resource\Event\GeneratePublicUrlForResourceEvent`
- :php:`\TYPO3\CMS\Core\Classes\Resource\Event\SanitizeFileNameEvent`

They replace the existing Extbase Signal Slots in the File Abstraction Layer.

Impact
======

All existing signals and their registered slots will work exactly the same as before, however
it is highly encouraged to migrate to the new PSR-14 based events.

In addition, all Core hooks using these events have been migrated to new PSR-14 events,
all new Events have a description when to use them and what the benefits are.

The Event `AfterFileCopiedEvent` in addition also contains the newly created File
object.

Have a look at the new PHP classes to understand the Events and to learn more about PSR-14.

.. index:: FAL, PHP-API, ext:core
