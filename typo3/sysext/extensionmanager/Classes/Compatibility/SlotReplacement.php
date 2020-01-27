<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Extensionmanager\Compatibility;

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

use TYPO3\CMS\Core\Package\Event\AfterPackageActivationEvent;
use TYPO3\CMS\Core\Package\Event\AfterPackageDeactivationEvent;
use TYPO3\CMS\Core\Package\Event\BeforePackageActivationEvent;
use TYPO3\CMS\Extbase\SignalSlot\Dispatcher as SignalSlotDispatcher;
use TYPO3\CMS\Extensionmanager\Event\AfterExtensionDatabaseContentHasBeenImportedEvent;
use TYPO3\CMS\Extensionmanager\Event\AfterExtensionFilesHaveBeenImportedEvent;
use TYPO3\CMS\Extensionmanager\Event\AfterExtensionStaticDatabaseContentHasBeenImportedEvent;
use TYPO3\CMS\Extensionmanager\Event\AvailableActionsForExtensionEvent;
use TYPO3\CMS\Extensionmanager\Service\ExtensionManagementService;
use TYPO3\CMS\Extensionmanager\Utility\InstallUtility;
use TYPO3\CMS\Extensionmanager\ViewHelpers\ProcessAvailableActionsViewHelper;

/**
 * This class provides a replacement for all existing signals in EXT:extensionmanager of TYPO3 Core, which now act as a
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

    public function afterExtensionInstallSlot(AfterPackageActivationEvent $event): void
    {
        $this->signalSlotDispatcher->dispatch(
            InstallUtility::class,
            'afterExtensionInstall',
            [
                $event->getPackageKey(),
                $event->getEmitter()
            ]
        );
    }

    public function afterExtensionUninstallSlot(AfterPackageDeactivationEvent $event): void
    {
        $this->signalSlotDispatcher->dispatch(
            InstallUtility::class,
            'afterExtensionUninstall',
            [
                $event->getPackageKey(),
                $event->getEmitter()
            ]
        );
    }

    public function emitAfterExtensionT3DImportSignal(AfterExtensionDatabaseContentHasBeenImportedEvent $event): void
    {
        $this->signalSlotDispatcher->dispatch(
            InstallUtility::class,
            'afterExtensionT3DImport',
            [
                $event->getImportFileName(),
                $event->getImportResult(),
                $event->getEmitter()
            ]
        );
    }

    public function emitAfterExtensionStaticSqlImportSignal(AfterExtensionStaticDatabaseContentHasBeenImportedEvent $event): void
    {
        $this->signalSlotDispatcher->dispatch(
            InstallUtility::class,
            'afterExtensionStaticSqlImport',
            [
                $event->getSqlFileName(),
                $event->getEmitter()
            ]
        );
    }

    public function emitAfterExtensionFileImportSignal(AfterExtensionFilesHaveBeenImportedEvent $event): void
    {
        $this->signalSlotDispatcher->dispatch(
            InstallUtility::class,
            'afterExtensionFileImport',
            [
                $event->getDestinationAbsolutePath(),
                $event->getEmitter()
            ]
        );
    }

    public function emitWillInstallExtensionsSignal(BeforePackageActivationEvent $event): void
    {
        $this->signalSlotDispatcher->dispatch(
            ExtensionManagementService::class,
            'willInstallExtensions',
            [
                $event->getPackageKeys()
            ]
        );
    }

    public function emitProcessActionsSignal(AvailableActionsForExtensionEvent $event): void
    {
        $actions = $event->getActions();
        $this->signalSlotDispatcher->dispatch(
            ProcessAvailableActionsViewHelper::class,
            'processActions',
            [
                $event->getPackageData(),
                &$actions,
            ]
        );
        $event->setActions($actions);
    }
}
