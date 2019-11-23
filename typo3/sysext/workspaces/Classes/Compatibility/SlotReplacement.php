<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Workspaces\Compatibility;

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

use TYPO3\CMS\Extbase\SignalSlot\Dispatcher as SignalSlotDispatcher;
use TYPO3\CMS\Workspaces\Event\AfterCompiledCacheableDataForWorkspaceEvent;
use TYPO3\CMS\Workspaces\Event\AfterDataGeneratedForWorkspaceEvent;
use TYPO3\CMS\Workspaces\Event\GetVersionedDataEvent;
use TYPO3\CMS\Workspaces\Event\SortVersionedDataEvent;
use TYPO3\CMS\Workspaces\Service\GridDataService;

/**
 * This class provides a replacement for all existing signals in EXT:workspaces of TYPO3 Core, which now act as a
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

    public function onGenerateDataArrayBeforeCaching(AfterCompiledCacheableDataForWorkspaceEvent $event): void
    {
        [$obj, $dataArray, $versions] = $this->signalSlotDispatcher->dispatch(
            GridDataService::class,
            GridDataService::SIGNAL_GenerateDataArray_BeforeCaching,
            [$event->getGridService(), $event->getData(), $event->getVersions()]
        );
        $event->setData($dataArray);
        $event->setVersions($versions);
    }

    public function onGenerateDataArrayPostProcessing(AfterDataGeneratedForWorkspaceEvent $event): void
    {
        [$obj, $dataArray] = $this->signalSlotDispatcher->dispatch(
            GridDataService::class,
            GridDataService::SIGNAL_GenerateDataArray_PostProcesss,
            [$event->getGridService(), $event->getData(), $event->getVersions()]
        );
        $event->setData($dataArray);
    }

    public function onGetDataPostProcessing(GetVersionedDataEvent $event): void
    {
        [$obj, $dataArray, $start, $limit, $dataArrayPart] = $this->signalSlotDispatcher->dispatch(
            GridDataService::class,
            GridDataService::SIGNAL_GetDataArray_PostProcesss,
            [$event->getGridService(), $event->getData(), $event->getStart(), $event->getLimit(), $event->getDataArrayPart()]
        );
        $event->setData($dataArray);
        $event->setDataArrayPart($dataArrayPart);
    }

    public function onSortDataPostProcessing(SortVersionedDataEvent $event): void
    {
        [$obj, $dataArray, $sortingColumn, $sortingDirection] = $this->signalSlotDispatcher->dispatch(
            GridDataService::class,
            GridDataService::SIGNAL_SortDataArray_PostProcesss,
            [$event->getGridService(), $event->getData(), $event->getSortColumn(), $event->getSortDirection()]
        );
        $event->setData($dataArray);
        $event->setSortColumn($sortingColumn);
        $event->setSortDirection($sortingDirection);
    }
}
