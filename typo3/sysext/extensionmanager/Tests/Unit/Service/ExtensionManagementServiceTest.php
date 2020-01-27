<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Extensionmanager\Tests\Unit\Service;

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

use Prophecy\Argument;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Extensionmanager\Domain\Model\DownloadQueue;
use TYPO3\CMS\Extensionmanager\Domain\Model\Extension;
use TYPO3\CMS\Extensionmanager\Service\ExtensionManagementService;
use TYPO3\CMS\Extensionmanager\Utility\DependencyUtility;
use TYPO3\CMS\Extensionmanager\Utility\DownloadUtility;
use TYPO3\CMS\Extensionmanager\Utility\ExtensionModelUtility;
use TYPO3\CMS\Extensionmanager\Utility\InstallUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Testcase
 */
class ExtensionManagementServiceTest extends UnitTestCase
{
    protected $managementService;
    protected $downloadUtilityProphecy;
    protected $dependencyUtilityProphecy;
    protected $installUtilityProphecy;
    protected $downloadQueue;
    protected $extensionModelUtilityProphecy;

    public function setUp(): void
    {
        parent::setUp();
        $this->resetSingletonInstances = true;
        $this->managementService = new ExtensionManagementService();
        $this->managementService->injectEventDispatcher($this->prophesize(EventDispatcherInterface::class)->reveal());

        $this->downloadUtilityProphecy = $this->prophesize(DownloadUtility::class);
        $this->dependencyUtilityProphecy = $this->prophesize(DependencyUtility::class);
        $this->installUtilityProphecy = $this->prophesize(InstallUtility::class);
        $this->downloadQueue = new DownloadQueue();
        $this->extensionModelUtilityProphecy = $this->prophesize(ExtensionModelUtility::class);

        $this->managementService->injectDependencyUtility($this->dependencyUtilityProphecy->reveal());
        $this->managementService->injectDownloadUtility($this->downloadUtilityProphecy->reveal());
        $this->managementService->injectInstallUtility($this->installUtilityProphecy->reveal());
        $this->managementService->injectDownloadQueue($this->downloadQueue);
        $this->managementService->injectExtensionModelUtility($this->extensionModelUtilityProphecy->reveal());
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

        $this->managementService->installExtension($extension);
        $this->downloadUtilityProphecy->download($extension)->shouldHaveBeenCalled();
    }

    /**
     * @test
     */
    public function installExtensionReturnsFalseIfDependenciesCannotBeResolved(): void
    {
        $extension = new Extension();
        $this->dependencyUtilityProphecy->setLocalExtensionStorage(Argument::any())->willReturn();
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
        $downloadQueue->addExtensionToQueue($extension);
        $this->managementService->injectDownloadQueue($downloadQueue);
        $this->installUtilityProphecy->enrichExtensionWithDetails('foo')->willReturn([]);
        $this->installUtilityProphecy->reloadAvailableExtensions()->willReturn();
        $this->installUtilityProphecy->install('foo')->willReturn();
        $this->extensionModelUtilityProphecy->mapExtensionArrayToModel([])->willReturn($extension);

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
        $downloadQueue->addExtensionToQueue($extension, 'update');
        $this->managementService->injectDownloadQueue($downloadQueue);
        $this->installUtilityProphecy->enrichExtensionWithDetails('foo')->willReturn([]);
        $this->installUtilityProphecy->reloadAvailableExtensions()->willReturn();
        $this->extensionModelUtilityProphecy->mapExtensionArrayToModel([])->willReturn($extension);

        // an extension update will uninstall the extension and install it again
        $this->installUtilityProphecy->uninstall('foo')->shouldBeCalled();
        $this->installUtilityProphecy->install('foo')->shouldBeCalled();

        $result = $this->managementService->installExtension($extension);

        self::assertSame(['updated' => ['foo' => $extension], 'installed' => ['foo' => 'foo']], $result);
    }

    /**
     * @test
     */
    public function markExtensionForCopyAddsExtensionToCopyQueue(): void
    {
        $this->managementService->markExtensionForCopy('ext', 'some/folder/');

        self::assertSame(['ext' => 'some/folder/'], $this->downloadQueue->resetExtensionCopyStorage());
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
