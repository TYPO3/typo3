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

namespace TYPO3\CMS\Extensionmanager\Tests\Unit\Service;

use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\CMS\Core\EventDispatcher\NoopEventDispatcher;
use TYPO3\CMS\Extensionmanager\Domain\Model\DownloadQueue;
use TYPO3\CMS\Extensionmanager\Domain\Model\Extension;
use TYPO3\CMS\Extensionmanager\Remote\ExtensionDownloaderRemoteInterface;
use TYPO3\CMS\Extensionmanager\Remote\RemoteRegistry;
use TYPO3\CMS\Extensionmanager\Service\ExtensionManagementService;
use TYPO3\CMS\Extensionmanager\Utility\DependencyUtility;
use TYPO3\CMS\Extensionmanager\Utility\FileHandlingUtility;
use TYPO3\CMS\Extensionmanager\Utility\InstallUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class ExtensionManagementServiceTest extends UnitTestCase
{
    protected bool $resetSingletonInstances = true;

    protected ExtensionManagementService $managementService;
    protected DependencyUtility&MockObject $dependencyUtilityMock;
    protected InstallUtility&MockObject $installUtilityMock;
    protected DownloadQueue $downloadQueue;
    protected ExtensionDownloaderRemoteInterface&MockObject $remoteMock;
    protected FileHandlingUtility&MockObject $fileHandlingUtilityMock;

    public function setUp(): void
    {
        parent::setUp();
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['extensionmanager']['offlineMode'] = false;
        $this->remoteMock = $this->createMock(ExtensionDownloaderRemoteInterface::class);
        $remoteRegistryMock = $this->createMock(RemoteRegistry::class);
        $remoteRegistryMock->method('hasRemote')->with(self::anything())->willReturn(true);
        $remoteRegistryMock->method('getRemote')->with(self::anything())->willReturn($this->remoteMock);
        $this->fileHandlingUtilityMock = $this->createMock(FileHandlingUtility::class);

        $this->downloadQueue = new DownloadQueue();
        $this->managementService = new ExtensionManagementService(
            $remoteRegistryMock,
            $this->fileHandlingUtilityMock,
            $this->downloadQueue,
            new NoopEventDispatcher()
        );

        $this->dependencyUtilityMock = $this->createMock(DependencyUtility::class);
        $this->installUtilityMock = $this->createMock(InstallUtility::class);

        $this->managementService->injectDependencyUtility($this->dependencyUtilityMock);
        $this->managementService->injectInstallUtility($this->installUtilityMock);
    }

    /**
     * @test
     */
    public function installDownloadsExtensionIfNecessary(): void
    {
        $extension = new Extension();
        $extension->setExtensionKey('foobar');
        $extension->setVersion('1.0.0');
        // an extension with a uid means it needs to be downloaded
        $extension->_setProperty('uid', 123);
        $extension->_setProperty('remote', 'ter');

        $this->remoteMock->expects(self::once())->method('downloadExtension')->with(
            $extension->getExtensionKey(),
            $extension->getVersion(),
            $this->fileHandlingUtilityMock,
            $extension->getMd5hash(),
            'Local'
        );
        $this->managementService->installExtension($extension);
    }

    /**
     * @test
     */
    public function installExtensionReturnsFalseIfDependenciesCannotBeResolved(): void
    {
        $extension = new Extension();
        $this->dependencyUtilityMock->expects(self::atLeastOnce())->method('setSkipDependencyCheck')->with(false);
        $this->dependencyUtilityMock->expects(self::atLeastOnce())->method('checkDependencies')->with($extension);
        $this->dependencyUtilityMock->method('hasDependencyErrors')->willReturn(true);

        $result = $this->managementService->installExtension($extension);
        self::assertFalse($result);
    }

    /**
     * @test
     */
    public function installExtensionWillReturnInstalledExtensions(): void
    {
        $extension = new Extension();
        $extension->setExtensionKey('foo');

        $result = $this->managementService->installExtension($extension);
        self::assertSame(['installed' => ['foo' => 'foo']], $result);
    }

    /**
     * @test
     */
    public function installExtensionWillReturnDownloadedExtensions(): void
    {
        $extension = new Extension();
        $extension->setExtensionKey('foo');
        $extension->_setProperty('remote', 'ter');
        $this->downloadQueue->addExtensionToQueue($extension);
        $this->installUtilityMock->method('enrichExtensionWithDetails')->with('foo')->willReturn([
            'key' => 'foo',
            'remote' => 'ter',
        ]);
        $this->installUtilityMock->expects(self::atLeastOnce())->method('reloadAvailableExtensions');
        $this->installUtilityMock->expects(self::atLeastOnce())->method('install')->with('foo');

        $result = $this->managementService->installExtension($extension);
        self::assertSame(['downloaded' => ['foo' => $extension], 'installed' => ['foo' => 'foo']], $result);
    }

    /**
     * @test
     */
    public function installExtensionWillReturnUpdatedExtensions(): void
    {
        $extension = new Extension();
        $extension->setExtensionKey('foo');
        $extension->_setProperty('remote', 'ter');
        $this->downloadQueue->addExtensionToQueue($extension, 'update');
        $this->installUtilityMock->method('enrichExtensionWithDetails')->with('foo')->willReturn([
            'key' => 'foo',
            'remote' => 'ter',
        ]);
        $this->installUtilityMock->expects(self::atLeastOnce())->method('reloadAvailableExtensions');

        // an extension update will uninstall the extension and install it again
        $this->installUtilityMock->expects(self::atLeastOnce())->method('uninstall')->with('foo');
        $this->installUtilityMock->expects(self::atLeastOnce())->method('install')->with('foo');

        $result = $this->managementService->installExtension($extension);

        self::assertSame(['updated' => ['foo' => $extension], 'installed' => ['foo' => 'foo']], $result);
    }

    /**
     * @test
     */
    public function markExtensionForDownloadAddsExtensionToDownloadQueueAndChecksDependencies(): void
    {
        $extension = new Extension();
        $extension->setExtensionKey('foo');
        $this->dependencyUtilityMock->method('hasDependencyErrors')->willReturn(false);
        $this->dependencyUtilityMock->expects(self::once())->method('checkDependencies')->with($extension);

        $this->managementService->markExtensionForDownload($extension);

        self::assertSame(['download' => ['foo' => $extension]], $this->downloadQueue->getExtensionQueue());
    }

    /**
     * @test
     */
    public function markExtensionForUpdateAddsExtensionToUpdateQueueAndChecksDependencies(): void
    {
        $extension = new Extension();
        $extension->setExtensionKey('foo');
        $this->dependencyUtilityMock->method('hasDependencyErrors')->willReturn(false);
        $this->dependencyUtilityMock->expects(self::once())->method('checkDependencies')->with($extension);

        $this->managementService->markExtensionForUpdate($extension);

        self::assertSame(['update' => ['foo' => $extension]], $this->downloadQueue->getExtensionQueue());
    }
}
