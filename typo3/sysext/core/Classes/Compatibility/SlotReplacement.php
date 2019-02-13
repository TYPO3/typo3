<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Core\Compatibility;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Resource\Event\AfterFileAddedEvent;
use TYPO3\CMS\Core\Resource\Event\AfterFileAddedToIndexEvent;
use TYPO3\CMS\Core\Resource\Event\AfterFileContentsSetEvent;
use TYPO3\CMS\Core\Resource\Event\AfterFileCopiedEvent;
use TYPO3\CMS\Core\Resource\Event\AfterFileCreatedEvent;
use TYPO3\CMS\Core\Resource\Event\AfterFileDeletedEvent;
use TYPO3\CMS\Core\Resource\Event\AfterFileMarkedAsMissingEvent;
use TYPO3\CMS\Core\Resource\Event\AfterFileMetaDataCreatedEvent;
use TYPO3\CMS\Core\Resource\Event\AfterFileMetaDataDeletedEvent;
use TYPO3\CMS\Core\Resource\Event\AfterFileMetaDataUpdatedEvent;
use TYPO3\CMS\Core\Resource\Event\AfterFileMovedEvent;
use TYPO3\CMS\Core\Resource\Event\AfterFileProcessingEvent;
use TYPO3\CMS\Core\Resource\Event\AfterFileRemovedFromIndexEvent;
use TYPO3\CMS\Core\Resource\Event\AfterFileRenamedEvent;
use TYPO3\CMS\Core\Resource\Event\AfterFileReplacedEvent;
use TYPO3\CMS\Core\Resource\Event\AfterFileUpdatedInIndexEvent;
use TYPO3\CMS\Core\Resource\Event\AfterFolderAddedEvent;
use TYPO3\CMS\Core\Resource\Event\AfterFolderCopiedEvent;
use TYPO3\CMS\Core\Resource\Event\AfterFolderDeletedEvent;
use TYPO3\CMS\Core\Resource\Event\AfterFolderMovedEvent;
use TYPO3\CMS\Core\Resource\Event\AfterFolderRenamedEvent;
use TYPO3\CMS\Core\Resource\Event\AfterResourceStorageInitializationEvent;
use TYPO3\CMS\Core\Resource\Event\BeforeFileAddedEvent;
use TYPO3\CMS\Core\Resource\Event\BeforeFileContentsSetEvent;
use TYPO3\CMS\Core\Resource\Event\BeforeFileCopiedEvent;
use TYPO3\CMS\Core\Resource\Event\BeforeFileCreatedEvent;
use TYPO3\CMS\Core\Resource\Event\BeforeFileDeletedEvent;
use TYPO3\CMS\Core\Resource\Event\BeforeFileMovedEvent;
use TYPO3\CMS\Core\Resource\Event\BeforeFileProcessingEvent;
use TYPO3\CMS\Core\Resource\Event\BeforeFileRenamedEvent;
use TYPO3\CMS\Core\Resource\Event\BeforeFileReplacedEvent;
use TYPO3\CMS\Core\Resource\Event\BeforeFolderAddedEvent;
use TYPO3\CMS\Core\Resource\Event\BeforeFolderCopiedEvent;
use TYPO3\CMS\Core\Resource\Event\BeforeFolderDeletedEvent;
use TYPO3\CMS\Core\Resource\Event\BeforeFolderMovedEvent;
use TYPO3\CMS\Core\Resource\Event\BeforeFolderRenamedEvent;
use TYPO3\CMS\Core\Resource\Event\BeforeResourceStorageInitializationEvent;
use TYPO3\CMS\Core\Resource\Event\EnrichFileMetaDataEvent;
use TYPO3\CMS\Core\Resource\Event\GeneratePublicUrlForResourceEvent;
use TYPO3\CMS\Core\Resource\Event\SanitizeFileNameEvent;
use TYPO3\CMS\Core\Resource\Index\FileIndexRepository;
use TYPO3\CMS\Core\Resource\Index\MetaDataRepository;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Resource\Service\FileProcessingService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\SignalSlot\Dispatcher as SignalSlotDispatcher;

/**
 * This class provides a replacement for all existing signals in TYPO3 Core, which now act as a simple wrapper
 * for PSR-14 events with a simple ("first prioritized") listener implementation.
 *
 * @internal Please note that this class will likely be removed in TYPO3 v11, and Extension Authors should
 * switch to PSR-14 event listeners.
 */
class SlotReplacement
{
    /**
     * @var SignalSlotDispatcher
     */
    protected $signalSlotDispatcher;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    public function __construct(SignalSlotDispatcher $signalSlotDispatcher, EventDispatcherInterface $eventDispatcher)
    {
        $this->signalSlotDispatcher = $signalSlotDispatcher;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function onMetaDataRepositoryRecordPostRetrieval(EnrichFileMetaDataEvent $event): void
    {
        $data = $event->getRecord();
        $data = new \ArrayObject($data);
        $this->signalSlotDispatcher->dispatch(MetaDataRepository::class, 'recordPostRetrieval', [$data]);
        $event->setRecord($data->getArrayCopy());
    }

    public function onMetaDataRepositoryRecordUpdated(AfterFileMetaDataUpdatedEvent $event): void
    {
        $this->signalSlotDispatcher->dispatch(MetaDataRepository::class, 'recordUpdated', [$event->getRecord()]);
    }

    public function onMetaDataRepositoryRecordCreated(AfterFileMetaDataCreatedEvent $event): void
    {
        $this->signalSlotDispatcher->dispatch(MetaDataRepository::class, 'recordCreated', [$event->getRecord()]);
    }

    public function onMetaDataRepositoryRecordDeleted(AfterFileMetaDataDeletedEvent $event): void
    {
        $this->signalSlotDispatcher->dispatch(MetaDataRepository::class, 'recordDeleted', [$event->getFileUid()]);
    }

    public function onFileIndexRepositoryRecordUpdated(AfterFileUpdatedInIndexEvent $event): void
    {
        $this->signalSlotDispatcher->dispatch(FileIndexRepository::class, 'recordUpdated', [$event->getRelevantProperties()]);
    }

    public function onFileIndexRepositoryRecordCreated(AfterFileAddedToIndexEvent $event): void
    {
        $this->signalSlotDispatcher->dispatch(FileIndexRepository::class, 'recordCreated', [$event->getRecord()]);
    }

    public function onFileIndexRepositoryRecordMarkedAsMissing(AfterFileMarkedAsMissingEvent $event): void
    {
        $this->signalSlotDispatcher->dispatch(FileIndexRepository::class, 'recordMarkedAsMissing', [$event->getFileUid()]);
    }

    public function onFileIndexRepositoryRecordDeleted(AfterFileRemovedFromIndexEvent $event): void
    {
        $this->signalSlotDispatcher->dispatch(FileIndexRepository::class, 'recordDeleted', [$event->getFileUid()]);
    }

    public function onResourceFactoryPreProcessStorage(BeforeResourceStorageInitializationEvent $event): void
    {
        [, $uid, $recordData, $fileIdentifier] = $this->signalSlotDispatcher->dispatch(
            ResourceFactory::class,
            'preProcessStorage',
            [ResourceFactory::getInstance(), $event->getStorageUid(), $event->getRecord(), $event->getFileIdentifier()]
        );
        $event->setStorageUid($uid);
        $event->setRecord($recordData);
        $event->setFileIdentifier($fileIdentifier);
    }

    public function onResourceFactoryPostProcessStorage(AfterResourceStorageInitializationEvent $event): void
    {
        $this->signalSlotDispatcher->dispatch(
            ResourceFactory::class,
            'postProcessStorage',
            [ResourceFactory::getInstance(), $event->getStorage()]
        );
    }

    public function onFileProcessingServiceEmitPreFileProcessSignal(BeforeFileProcessingEvent $event): void
    {
        $service = GeneralUtility::makeInstance(
            FileProcessingService::class,
            $event->getFile()->getStorage(),
            $event->getDriver(),
            $this->eventDispatcher
        );
        $this->signalSlotDispatcher->dispatch(
            ResourceStorage::class,
            FileProcessingService::SIGNAL_PreFileProcess,
            [
                $service, $event->getDriver(), $event->getProcessedFile(), $event->getFile(), $event->getTaskType(), $event->getConfiguration()
            ]
        );
    }

    public function onFileProcessingServiceEmitPostFileProcessSignal(AfterFileProcessingEvent $event): void
    {
        $service = GeneralUtility::makeInstance(
            FileProcessingService::class,
            $event->getFile()->getStorage(),
            $event->getDriver(),
            $this->eventDispatcher
        );
        $this->signalSlotDispatcher->dispatch(
            ResourceStorage::class,
            FileProcessingService::SIGNAL_PostFileProcess,
            [$service, $event->getDriver(), $event->getProcessedFile(), $event->getFile(), $event->getTaskType(), $event->getConfiguration()]
        );
    }

    public function onResourceStorageEmitSanitizeFileNameSignal(SanitizeFileNameEvent $event): void
    {
        list($fileName) = $this->signalSlotDispatcher->dispatch(
            ResourceStorage::class,
            ResourceStorage::SIGNAL_SanitizeFileName,
            [
                $event->getFileName(),
                $event->getTargetFolder(),
                $event->getStorage(),
                $event->getDriver()
            ]
        );
        $event->setFileName($fileName);
    }

    public function onResourceStorageEmitPreFileAddSignal(BeforeFileAddedEvent $event): void
    {
        $targetFileName = $event->getFileName();
        $this->signalSlotDispatcher->dispatch(
            ResourceStorage::class,
            ResourceStorage::SIGNAL_PreFileAdd,
            [
                &$targetFileName,
                $event->getTargetFolder(),
                $event->getSourceFilePath(),
                $event->getStorage(),
                $event->getDriver()
            ]
        );
        $event->setFileName($targetFileName);
    }

    public function onResourceStorageEmitPostFileAddSignal(AfterFileAddedEvent $event): void
    {
        $this->signalSlotDispatcher->dispatch(
            ResourceStorage::class,
            ResourceStorage::SIGNAL_PostFileAdd,
            [
                $event->getFile(),
                $event->getFolder()
            ]
        );
    }

    public function onResourceStorageEmitPreFileCopySignal(BeforeFileCopiedEvent $event): void
    {
        $this->signalSlotDispatcher->dispatch(
            ResourceStorage::class,
            ResourceStorage::SIGNAL_PreFileCopy,
            [
                $event->getFile(),
                $event->getFolder()
            ]
        );
    }

    public function onResourceStorageEmitPostFileCopySignal(AfterFileCopiedEvent $event): void
    {
        $this->signalSlotDispatcher->dispatch(
            ResourceStorage::class,
            ResourceStorage::SIGNAL_PostFileCopy,
            [
                $event->getFile(),
                $event->getFolder()
            ]
        );
    }

    public function onResourceStorageEmitPreFileMoveSignal(BeforeFileMovedEvent $event): void
    {
        $this->signalSlotDispatcher->dispatch(
            ResourceStorage::class,
            ResourceStorage::SIGNAL_PreFileMove,
            [
                $event->getFile(),
                $event->getFolder(),
                $event->getTargetFileName()
            ]
        );
    }

    public function onResourceStorageEmitPostFileMoveSignal(AfterFileMovedEvent $event): void
    {
        $this->signalSlotDispatcher->dispatch(
            ResourceStorage::class,
            ResourceStorage::SIGNAL_PostFileMove,
            [
                $event->getFile(),
                $event->getFolder(),
                $event->getOriginalFolder()
            ]
        );
    }

    public function onResourceStorageEmitPreFileRenameSignal(BeforeFileRenamedEvent $event): void
    {
        $this->signalSlotDispatcher->dispatch(
            ResourceStorage::class,
            ResourceStorage::SIGNAL_PreFileRename,
            [
                $event->getFile(),
                $event->getTargetFileName()
            ]
        );
    }

    public function onResourceStorageEmitPostFileRenameSignal(AfterFileRenamedEvent $event): void
    {
        $this->signalSlotDispatcher->dispatch(
            ResourceStorage::class,
            ResourceStorage::SIGNAL_PostFileRename,
            [
                $event->getFile(),
                $event->getTargetFileName()
            ]
        );
    }

    public function onResourceStorageEmitPreFileReplaceSignal(BeforeFileReplacedEvent $event): void
    {
        $this->signalSlotDispatcher->dispatch(
            ResourceStorage::class,
            ResourceStorage::SIGNAL_PreFileReplace,
            [
                $event->getFile(),
                $event->getLocalFilePath()
            ]
        );
    }

    public function onResourceStorageEmitPostFileReplaceSignal(AfterFileReplacedEvent $event): void
    {
        $this->signalSlotDispatcher->dispatch(
            ResourceStorage::class,
            ResourceStorage::SIGNAL_PostFileReplace,
            [
                $event->getFile(),
                $event->getLocalFilePath()
            ]
        );
    }

    public function onResourceStorageEmitPreFileCreateSignal(BeforeFileCreatedEvent $event): void
    {
        $this->signalSlotDispatcher->dispatch(
            ResourceStorage::class,
            ResourceStorage::SIGNAL_PreFileCreate,
            [
                $event->getFileName(),
                $event->getFolder()
            ]
        );
    }

    public function onResourceStorageEmitPostFileCreateSignal(AfterFileCreatedEvent $event): void
    {
        $this->signalSlotDispatcher->dispatch(
            ResourceStorage::class,
            ResourceStorage::SIGNAL_PostFileCreate,
            [
                $event->getFileName(),
                $event->getFolder()
            ]
        );
    }

    public function onResourceStorageEmitPreFileDeleteSignal(BeforeFileDeletedEvent $event): void
    {
        $this->signalSlotDispatcher->dispatch(
            ResourceStorage::class,
            ResourceStorage::SIGNAL_PreFileDelete,
            [
                $event->getFile()
            ]
        );
    }

    public function onResourceStorageEmitPostFileDeleteSignal(AfterFileDeletedEvent $event): void
    {
        $this->signalSlotDispatcher->dispatch(
            ResourceStorage::class,
            ResourceStorage::SIGNAL_PostFileDelete,
            [
                $event->getFile()
            ]
        );
    }

    public function onResourceStorageEmitPreFileSetContentsSignal(BeforeFileContentsSetEvent $event): void
    {
        $this->signalSlotDispatcher->dispatch(
            ResourceStorage::class,
            ResourceStorage::SIGNAL_PreFileSetContents,
            [
                $event->getFile(),
                $event->getContent()
            ]
        );
    }

    public function onResourceStorageEmitPostFileSetContentsSignal(AfterFileContentsSetEvent $event): void
    {
        $this->signalSlotDispatcher->dispatch(
            ResourceStorage::class,
            ResourceStorage::SIGNAL_PostFileSetContents,
            [
                $event->getFile(),
                $event->getContent()
            ]
        );
    }

    public function onResourceStorageEmitPreFolderAddSignal(BeforeFolderAddedEvent $event): void
    {
        $this->signalSlotDispatcher->dispatch(
            ResourceStorage::class,
            ResourceStorage::SIGNAL_PreFolderAdd,
            [
                $event->getParentFolder(),
                $event->getFolderName()
            ]
        );
    }

    public function onResourceStorageEmitPostFolderAddSignal(AfterFolderAddedEvent $event): void
    {
        $this->signalSlotDispatcher->dispatch(
            ResourceStorage::class,
            ResourceStorage::SIGNAL_PostFolderAdd,
            [
                $event->getFolder()
            ]
        );
    }

    public function onResourceStorageEmitPreFolderCopySignal(BeforeFolderCopiedEvent $event): void
    {
        $this->signalSlotDispatcher->dispatch(
            ResourceStorage::class,
            ResourceStorage::SIGNAL_PreFolderCopy,
            [
                $event->getFolder(),
                $event->getTargetParentFolder(),
                $event->getTargetFolderName()
            ]
        );
    }

    public function onResourceStorageEmitPostFolderCopySignal(AfterFolderCopiedEvent $event): void
    {
        $this->signalSlotDispatcher->dispatch(
            ResourceStorage::class,
            ResourceStorage::SIGNAL_PostFolderCopy,
            [
                $event->getFolder(),
                $event->getTargetParentFolder(),
                $event->getTargetFolder()->getName()
            ]
        );
    }

    public function onResourceStorageEmitPreFolderMoveSignal(BeforeFolderMovedEvent $event): void
    {
        $this->signalSlotDispatcher->dispatch(
            ResourceStorage::class,
            ResourceStorage::SIGNAL_PreFolderMove,
            [
                $event->getFolder(),
                $event->getTargetParentFolder(),
                $event->getTargetFolderName()
            ]
        );
    }

    public function onResourceStorageEmitPostFolderMoveSignal(AfterFolderMovedEvent $event): void
    {
        $this->signalSlotDispatcher->dispatch(
            ResourceStorage::class,
            ResourceStorage::SIGNAL_PostFolderMove,
            [
                $event->getFolder(),
                $event->getTargetParentFolder(),
                $event->getTargetFolder()->getName(),
                $event->getFolder()->getParentFolder()
            ]
        );
    }

    public function onResourceStorageEmitPreFolderRenameSignal(BeforeFolderRenamedEvent $event): void
    {
        $this->signalSlotDispatcher->dispatch(
            ResourceStorage::class,
            ResourceStorage::SIGNAL_PreFolderRename,
            [
                $event->getFolder(),
                $event->getTargetName()
            ]
        );
    }

    public function onResourceStorageEmitPostFolderRenameSignal(AfterFolderRenamedEvent $event): void
    {
        $this->signalSlotDispatcher->dispatch(
            ResourceStorage::class,
            ResourceStorage::SIGNAL_PostFolderRename,
            [
                $event->getFolder(),
                $event->getFolder()->getName()
            ]
        );
    }

    public function onResourceStorageEmitPreFolderDeleteSignal(BeforeFolderDeletedEvent $event): void
    {
        $this->signalSlotDispatcher->dispatch(
            ResourceStorage::class,
            ResourceStorage::SIGNAL_PreFolderDelete,
            [
                $event->getFolder()
            ]
        );
    }

    public function onResourceStorageEmitPostFolderDeleteSignal(AfterFolderDeletedEvent $event): void
    {
        $this->signalSlotDispatcher->dispatch(
            ResourceStorage::class,
            ResourceStorage::SIGNAL_PostFolderDelete,
            [
                $event->getFolder()
            ]
        );
    }

    public function onResourceStorageEmitPreGeneratePublicUrlSignal(GeneratePublicUrlForResourceEvent $event): void
    {
        $publicUrl = $event->getPublicUrl();
        $urlData = ['publicUrl' => &$publicUrl];
        $this->signalSlotDispatcher->dispatch(
            ResourceStorage::class,
            ResourceStorage::SIGNAL_PreGeneratePublicUrl,
            [
                $event->getStorage(),
                $event->getDriver(),
                $event->getResource(),
                $event->isRelativeToCurrentScript(),
                $urlData
            ]
        );
        $event->setPublicUrl($urlData['publicUrl']);
    }
}
