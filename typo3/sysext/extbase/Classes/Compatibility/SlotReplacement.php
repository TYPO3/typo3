<?php

declare(strict_types=1);

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

namespace TYPO3\CMS\Extbase\Compatibility;

use TYPO3\CMS\Extbase\Event\Mvc\AfterRequestDispatchedEvent;
use TYPO3\CMS\Extbase\Event\Mvc\BeforeActionCallEvent;
use TYPO3\CMS\Extbase\Event\Persistence\AfterObjectThawedEvent;
use TYPO3\CMS\Extbase\Event\Persistence\EntityAddedToPersistenceEvent;
use TYPO3\CMS\Extbase\Event\Persistence\EntityFinalizedAfterPersistenceEvent;
use TYPO3\CMS\Extbase\Event\Persistence\EntityPersistedEvent;
use TYPO3\CMS\Extbase\Event\Persistence\EntityRemovedFromPersistenceEvent;
use TYPO3\CMS\Extbase\Event\Persistence\EntityUpdatedInPersistenceEvent;
use TYPO3\CMS\Extbase\Event\Persistence\ModifyQueryBeforeFetchingObjectDataEvent;
use TYPO3\CMS\Extbase\Event\Persistence\ModifyResultAfterFetchingObjectDataEvent;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Mvc\Dispatcher;
use TYPO3\CMS\Extbase\Persistence\Generic\Backend;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper;
use TYPO3\CMS\Extbase\SignalSlot\Dispatcher as SignalSlotDispatcher;

/**
 * This class provides a replacement for all existing signals in EXT:extbase of TYPO3 Core, which now act as a
 * simple wrapper for PSR-14 events with a simple ("first prioritized") listener implementation.
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

    public function __construct(SignalSlotDispatcher $signalSlotDispatcher)
    {
        $this->signalSlotDispatcher = $signalSlotDispatcher;
    }

    public function afterRequestDispatched(AfterRequestDispatchedEvent $event): void
    {
        $this->signalSlotDispatcher->dispatch(
            Dispatcher::class,
            'afterRequestDispatch',
            [$event->getRequest(), $event->getResponse()]
        );
    }

    public function beforeCallActionMethod(BeforeActionCallEvent $event): void
    {
        $this->signalSlotDispatcher->dispatch(
            ActionController::class,
            'beforeCallActionMethod',
            [
                $event->getControllerClassName(),
                $event->getActionMethodName(),
                $event->getPreparedArguments()
            ]
        );
    }

    public function afterDataMappedForObject(AfterObjectThawedEvent $event): void
    {
        $this->signalSlotDispatcher->dispatch(
            DataMapper::class,
            'afterMappingSingleRow',
            [
                $event->getObject()
            ]
        );
    }

    public function emitBeforeGettingObjectDataSignal(ModifyQueryBeforeFetchingObjectDataEvent $event): void
    {
        $signalArguments = $this->signalSlotDispatcher->dispatch(
            Backend::class,
            'beforeGettingObjectData',
            [
                $event->getQuery()
            ]
        );
        $event->setQuery($signalArguments[0]);
    }

    public function emitAfterGettingObjectDataSignal(ModifyResultAfterFetchingObjectDataEvent $event): void
    {
        $signalArguments = $this->signalSlotDispatcher->dispatch(
            Backend::class,
            'afterGettingObjectData',
            [$event->getQuery(), $event->getResult()]
        );
        $event->setResult($signalArguments[1]);
    }

    public function emitEndInsertObjectSignal(EntityFinalizedAfterPersistenceEvent $event): void
    {
        $this->signalSlotDispatcher->dispatch(
            Backend::class,
            'endInsertObject',
            [
                $event->getObject()
            ]
        );
    }

    public function emitAfterInsertObjectSignal(EntityAddedToPersistenceEvent $event): void
    {
        $this->signalSlotDispatcher->dispatch(
            Backend::class,
            'afterInsertObject',
            [
                $event->getObject()
            ]
        );
    }

    public function emitAfterUpdateObjectSignal(EntityUpdatedInPersistenceEvent $event): void
    {
        $this->signalSlotDispatcher->dispatch(
            Backend::class,
            'afterUpdateObject',
            [
                $event->getObject()
            ]
        );
    }

    public function emitAfterPersistObjectSignal(EntityPersistedEvent $event): void
    {
        $this->signalSlotDispatcher->dispatch(
            Backend::class,
            'afterPersistObject',
            [
                $event->getObject()
            ]
        );
    }

    public function emitAfterRemoveObjectSignal(EntityRemovedFromPersistenceEvent $event): void
    {
        $this->signalSlotDispatcher->dispatch(
            Backend::class,
            'afterRemoveObject',
            [
                $event->getObject()
            ]
        );
    }
}
