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

namespace TYPO3\CMS\Backend\Compatibility;

use TYPO3\CMS\Backend\Backend\Event\SystemInformationToolbarCollectorEvent;
use TYPO3\CMS\Backend\Backend\ToolbarItems\SystemInformationToolbarItem;
use TYPO3\CMS\Backend\Controller\EditDocumentController;
use TYPO3\CMS\Backend\Controller\Event\AfterFormEnginePageInitializedEvent;
use TYPO3\CMS\Backend\Controller\Event\BeforeFormEnginePageInitializedEvent;
use TYPO3\CMS\Backend\LoginProvider\Event\ModifyPageLayoutOnLoginProviderSelectionEvent;
use TYPO3\CMS\Backend\LoginProvider\UsernamePasswordLoginProvider;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Configuration\Event\ModifyLoadedPageTsConfigEvent;
use TYPO3\CMS\Extbase\SignalSlot\Dispatcher as SignalSlotDispatcher;

/**
 * This class provides a replacement for all existing signals in EXT:backend of TYPO3 Core, which now act as a
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

    public function onSystemInformationToolbarEvent(SystemInformationToolbarCollectorEvent $event): void
    {
        $this->signalSlotDispatcher->dispatch(
            SystemInformationToolbarItem::class,
            'getSystemInformation',
            [$event->getToolbarItem()]
        );
        $this->signalSlotDispatcher->dispatch(
            SystemInformationToolbarItem::class,
            'loadMessages',
            [$event->getToolbarItem()]
        );
    }

    public function onLoginProviderGetPageRenderer(ModifyPageLayoutOnLoginProviderSelectionEvent $event): void
    {
        $this->signalSlotDispatcher->dispatch(
            UsernamePasswordLoginProvider::class,
            'getPageRenderer',
            [$event->getPageRenderer()]
        );
    }

    public function onPreInitEditDocumentController(BeforeFormEnginePageInitializedEvent $event): void
    {
        $this->signalSlotDispatcher->dispatch(
            EditDocumentController::class,
            'preInitAfter',
            [$event->getController(), 'request' => $event->getRequest()]
        );
    }

    public function onInitEditDocumentController(AfterFormEnginePageInitializedEvent $event): void
    {
        $this->signalSlotDispatcher->dispatch(
            EditDocumentController::class,
            'initAfter',
            [$event->getController(), 'request' => $event->getRequest()]
        );
    }

    public function emitGetPagesTSconfigPreIncludeSignalBackendUtility(ModifyLoadedPageTsConfigEvent $event): void
    {
        $rootLine = $event->getRootLine();
        $page = end($rootLine);
        $signalArguments = $this->signalSlotDispatcher->dispatch(
            BackendUtility::class,
            'getPagesTSconfigPreInclude',
            [$event->getTsConfig(), (int)$page['uid'], $rootLine, false]
        );
        $event->setTsConfig($signalArguments[0]);
    }
}
