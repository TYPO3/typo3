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

use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Extensionmanager\Domain\Model\DownloadQueue;
use TYPO3\CMS\Extensionmanager\Domain\Model\Extension;
use TYPO3\CMS\Extensionmanager\Remote\ExtensionDownloaderRemoteInterface;
use TYPO3\CMS\Extensionmanager\Remote\RemoteRegistry;
use TYPO3\CMS\Extensionmanager\Service\ExtensionManagementService;
use TYPO3\CMS\Extensionmanager\Utility\DependencyUtility;
use TYPO3\CMS\Extensionmanager\Utility\FileHandlingUtility;
use TYPO3\CMS\Extensionmanager\Utility\InstallUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class ExtensionManagementServiceTest extends UnitTestCase
{
    use ProphecyTrait;

    protected $managementService;
    protected $dependencyUtilityProphecy;
    protected $installUtilityProphecy;
    protected $downloadQueue;
    protected $remoteRegistry;
    protected $remote;
    protected $fileHandlingUtility;

    public function setUp(): void
    {
        parent::setUp();
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['extensionmanager']['offlineMode'] = false;
        $this->resetSingletonInstances = true;
        $this->remoteRegistry = $this->prophesize(RemoteRegistry::class);
        $this->remote = $this->prophesize(ExtensionDownloaderRemoteInterface::class);
        $this->remoteRegistry->hasRemote(Argument::cetera())->willReturn(true);
        $this->remoteRegistry->getRemote(Argument::cetera())->willReturn($this->remote->reveal());
        $this->fileHandlingUtility = $this->prophesize(FileHandlingUtility::class);

        $this->managementService = new ExtensionManagementService(
            $this->remoteRegistry->reveal(),
            $this->fileHandlingUtility->reveal()
        );
        $this->managementService->injectEventDispatcher($this->prophesize(EventDispatcherInterface::class)->reveal());

        $this->dependencyUtilityProphecy = $this->prophesize(DependencyUtility::class);
        $this->installUtilityProphecy = $this->prophesize(InstallUtility::class);
        $this->downloadQueue = new DownloadQueue();

        $this->managementService->injectDependencyUtility($this->dependencyUtilityProphecy->reveal());
        $this->managementService->injectInstallUtility($this->installUtilityProphecy->reveal());
        $this->managementService->injectDownloadQueue($this->downloadQueue);
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

        $this->managementService->installExtension($extension);
        $this->remote->downloadExtension(
            $extension->getExtensionKey(),
            $extension->getVersion(),
            $this->fileHandlingUtility,
            $extension->getMd5hash(),
            'Local'
        )->shouldHaveBeenCalled();
    }

    /**
     * @test
     */
    public function installExtensionReturnsFalseIfDependenciesCannotBeResolved(): void
    {
        $extension = new Extension();
        $this->dependencyUtilityProphecy->setSkipDependencyCheck(false)->willReturn();
        $this->dependencyUtilityProphecy->checkDependencies($extension)->willReturn();

        $this->dependencyUtilityProphecy->hasDependencyErrors()->willReturn(true);

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
        $downloadQueue = new DownloadQueue();
        $extension = new Extension();
        $extension->setExtensionKey('foo');
        $extension->_setProperty('remote', 'ter');
        $downloadQueue->addExtensionToQueue($extension);
        $this->managementService->injectDownloadQueue($downloadQueue);
        $this->installUtilityProphecy->enrichExtensionWithDetails('foo')->willReturn([
            'key' => 'foo',
            'remote' => 'ter',
        ]);
        $this->installUtilityProphecy->reloadAvailableExtensions()->willReturn();
        $this->installUtilityProphecy->install('foo')->willReturn();

        $result = $this->managementService->installExtension($extension);
        self::assertSame(['downloaded' => ['foo' => $extension], 'installed' => ['foo' => 'foo']], $result);
    }

    /**
     * @test
     */
    public function installExtensionWillReturnUpdatedExtensions(): void
    {
        $downloadQueue = new DownloadQueue();
        $extension = new Extension();
        $extension->setExtensionKey('foo');
        $extension->_setProperty('remote', 'ter');
        $downloadQueue->addExtensionToQueue($extension, 'update');
        $this->managementService->injectDownloadQueue($downloadQueue);
        $this->installUtilityProphecy->enrichExtensionWithDetails('foo')->willReturn([
            'key' => 'foo',
            'remote' => 'ter',
        ]);
        $this->installUtilityProphecy->reloadAvailableExtensions()->willReturn();

        // an extension update will uninstall the extension and install it again
        $this->installUtilityProphecy->uninstall('foo')->shouldBeCalled();
        $this->installUtilityProphecy->install('foo')->shouldBeCalled();

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
        $this->dependencyUtilityProphecy->hasDependencyErrors()->willReturn(false);
        $this->dependencyUtilityProphecy->checkDependencies($extension)->shouldBeCalled();

        $this->managementService->markExtensionForDownload($extension);

        $this->dependencyUtilityProphecy->checkDependencies($extension)->shouldHaveBeenCalled();
        self::assertSame(['download' => ['foo' => $extension]], $this->downloadQueue->getExtensionQueue());
    }

    /**
     * @test
     */
    public function markExtensionForUpdateAddsExtensionToUpdateQueueAndChecksDependencies(): void
    {
        $extension = new Extension();
        $extension->setExtensionKey('foo');
        $this->dependencyUtilityProphecy->hasDependencyErrors()->willReturn(false);
        $this->dependencyUtilityProphecy->checkDependencies($extension)->shouldBeCalled();

        $this->managementService->markExtensionForUpdate($extension);

        $this->dependencyUtilityProphecy->checkDependencies($extension)->shouldHaveBeenCalled();
        self::assertSame(['update' => ['foo' => $extension]], $this->downloadQueue->getExtensionQueue());
    }
}
