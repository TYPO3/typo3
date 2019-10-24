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
    /**
     * @test
     */
    public function resolveDependenciesCallsDownloadDependenciesIfDownloadKeyExistsInQueue()
    {
        $managementMock = $this->getAccessibleMock(
            ExtensionManagementService::class,
            ['downloadDependencies', 'uninstallDependenciesToBeUpdated', 'setInExtensionRepository', 'downloadMainExtension', 'isAutomaticInstallationEnabled']
        );
        $managementMock->expects(self::any())->method('downloadMainExtension')->willReturn([]);
        $managementMock->expects(self::any())->method('isAutomaticInstallationEnabled')->willReturn([false]);
        $extensionModelMock = $this->getAccessibleMock(Extension::class);
        $extensionModelMock->_set('extensionKey', 'foobar');
        $extensionModelMock->_set('version', '1.0.0');
        $dependencyUtilityMock = $this->getAccessibleMock(DependencyUtility::class, ['checkDependencies']);
        $dependencyUtilityMock->expects(self::atLeastOnce())->method('checkDependencies');
        $managementMock->_set('dependencyUtility', $dependencyUtilityMock);
        $downloadQueueMock = $this->getAccessibleMock(DownloadQueue::class, ['isCopyQueueEmpty', 'isQueueEmpty', 'resetExtensionQueue', 'addExtensionToInstallQueue']);
        $downloadQueueMock->expects(self::any())->method('isCopyQueueEmpty')->willReturn(true);
        $downloadQueueMock->expects(self::at(1))->method('isQueueEmpty')->with('download')->willReturn(false);
        $downloadQueueMock->expects(self::at(4))->method('isQueueEmpty')->with('download')->willReturn(true);
        $downloadQueueMock->expects(self::at(5))->method('isQueueEmpty')->with('update')->willReturn(true);
        $downloadQueueMock->expects(self::atLeastOnce())->method('resetExtensionQueue')->willReturn([
            'download' => [
                'foo' => $extensionModelMock
            ]
        ]);
        $managementMock->_set('downloadQueue', $downloadQueueMock);
        $managementMock->expects(self::once())->method('downloadDependencies')->with(['foo' => $extensionModelMock])->willReturn([]);
        $managementMock->_call('installExtension', $extensionModelMock);
    }

    /**
     * @test
     */
    public function resolveDependenciesCallsUpdateAndDownloadDependenciesIfUpdateKeyExistsInQueue()
    {
        $managementMock = $this->getAccessibleMock(
            ExtensionManagementService::class,
            ['downloadDependencies', 'uninstallDependenciesToBeUpdated', 'setInExtensionRepository', 'downloadMainExtension', 'isAutomaticInstallationEnabled']
        );
        $managementMock->expects(self::any())->method('downloadMainExtension')->willReturn([]);
        $managementMock->expects(self::any())->method('isAutomaticInstallationEnabled')->willReturn(true);
        $extensionModelMock = $this->getAccessibleMock(Extension::class);
        $extensionModelMock->_set('extensionKey', 'foobar');
        $extensionModelMock->_set('version', '1.0.0');
        $dependencyUtilityMock = $this->getAccessibleMock(DependencyUtility::class, ['checkDependencies']);
        $dependencyUtilityMock->expects(self::atLeastOnce())->method('checkDependencies');
        $managementMock->_set('dependencyUtility', $dependencyUtilityMock);
        $downloadQueueMock = $this->getAccessibleMock(DownloadQueue::class, ['isCopyQueueEmpty', 'isQueueEmpty', 'resetExtensionQueue', 'addExtensionToInstallQueue']);
        $downloadQueueMock->expects(self::any())->method('isCopyQueueEmpty')->willReturn(true);
        $downloadQueueMock->expects(self::at(1))->method('isQueueEmpty')->with('download')->willReturn(false);
        $downloadQueueMock->expects(self::at(4))->method('isQueueEmpty')->with('download')->willReturn(true);
        $downloadQueueMock->expects(self::at(5))->method('isQueueEmpty')->with('update')->willReturn(true);
        $downloadQueueMock->expects(self::atLeastOnce())->method('resetExtensionQueue')->willReturn([
            'update' => [
                'foo' => $extensionModelMock
            ]
        ]);
        $managementMock->_set('downloadQueue', $downloadQueueMock);
        $managementMock->expects(self::once())->method('downloadDependencies')->with(['foo' => $extensionModelMock])->willReturn([]);
        $managementMock->expects(self::once())->method('uninstallDependenciesToBeUpdated')->with(['foo' => $extensionModelMock])->willReturn([]);
        $managementMock->_call('installExtension', $extensionModelMock);
    }

    /**
     * @test
     */
    public function downloadDependenciesCallsDownloadUtilityDownloadMethod()
    {
        $managementMock = $this->getAccessibleMock(ExtensionManagementService::class, [
            'dummy'
        ]);

        $extensionModelMock = $this->getAccessibleMock(Extension::class, ['getExtensionKey']);
        $extensionModelMock->_set('extensionKey', 'foobar');
        $extensionModelMock->_set('version', '1.0.0');

        $dependencyUtilityMock = $this->getAccessibleMock(DependencyUtility::class);
        $dependencyUtilityMock->expects(self::atLeastOnce())->method('checkDependencies');
        $installUtilityMock = $this->getMockBuilder(InstallUtility::class)->getMock();
        $installUtilityMock->expects(self::any())->method('enrichExtensionWithDetails')->willReturn([]);
        $extensionModelUtilityMock = $this->getMockBuilder(ExtensionModelUtility::class)->getMock();
        $extensionModelUtilityMock->expects(self::any())->method('mapExtensionArrayToModel')->willReturn($extensionModelMock);
        $managementMock->_set('dependencyUtility', $dependencyUtilityMock);
        $managementMock->_set('installUtility', $installUtilityMock);
        $managementMock->_set('extensionModelUtility', $extensionModelUtilityMock);

        $downloadQueue = [
            $extensionModelMock
        ];
        $downloadQueueMock = $this->getAccessibleMock(DownloadQueue::class, ['removeExtensionFromQueue', 'addExtensionToInstallQueue']);
        $downloadUtilityMock = $this->getAccessibleMock(DownloadUtility::class, ['download']);
        $downloadUtilityMock->expects(self::once())->method('download')->with($extensionModelMock);
        $managementMock->_set('downloadUtility', $downloadUtilityMock);
        $managementMock->_set('downloadQueue', $downloadQueueMock);
        $managementMock->_call('downloadDependencies', $downloadQueue);
    }

    /**
     * @test
     */
    public function downloadDependenciesCallsRemoveExtensionFromQueue()
    {
        $managementMock = $this->getAccessibleMock(ExtensionManagementService::class, [
            'dummy'
        ]);

        /** @var Extension $extensionModelMock */
        $extensionModelMock = $this->getMockBuilder(Extension::class)
            ->setMethods(['getExtensionKey'])
            ->getMock();
        $extensionModelMock->setExtensionKey('foobar');
        $extensionModelMock->setVersion('1.0.0');
        $downloadQueue = [
            $extensionModelMock
        ];

        $dependencyUtilityMock = $this->getAccessibleMock(DependencyUtility::class);
        $dependencyUtilityMock->expects(self::atLeastOnce())->method('checkDependencies');
        $installUtilityMock = $this->getMockBuilder(InstallUtility::class)->getMock();
        $installUtilityMock->expects(self::any())->method('enrichExtensionWithDetails')->willReturn([]);
        $extensionModelUtilityMock = $this->getMockBuilder(ExtensionModelUtility::class)->getMock();
        $extensionModelUtilityMock->expects(self::any())->method('mapExtensionArrayToModel')->willReturn($extensionModelMock);
        $managementMock->_set('dependencyUtility', $dependencyUtilityMock);
        $managementMock->_set('installUtility', $installUtilityMock);
        $managementMock->_set('extensionModelUtility', $extensionModelUtilityMock);

        $downloadQueueMock = $this->getAccessibleMock(DownloadQueue::class, ['removeExtensionFromQueue', 'addExtensionToInstallQueue']);
        $downloadUtilityMock = $this->getAccessibleMock(DownloadUtility::class, ['download']);
        $downloadQueueMock->expects(self::once())->method('removeExtensionFromQueue')->with($extensionModelMock);
        $managementMock->_set('downloadUtility', $downloadUtilityMock);
        $managementMock->_set('downloadQueue', $downloadQueueMock);
        $managementMock->_call('downloadDependencies', $downloadQueue);
    }

    /**
     * @test
     */
    public function downloadDependenciesReturnsResolvedDependencies()
    {
        $managementMock = $this->getAccessibleMock(ExtensionManagementService::class, [
            'dummy'
        ]);

        $extensionModelMock = $this->getAccessibleMock(Extension::class, ['getExtensionKey']);
        $extensionModelMock->_set('extensionKey', 'foobar');
        $extensionModelMock->_set('version', '1.0.0');
        $downloadQueue = [
            $extensionModelMock
        ];

        $dependencyUtilityMock = $this->getAccessibleMock(DependencyUtility::class);
        $dependencyUtilityMock->expects(self::atLeastOnce())->method('checkDependencies');
        $installUtilityMock = $this->getMockBuilder(InstallUtility::class)->getMock();
        $installUtilityMock->expects(self::any())->method('enrichExtensionWithDetails')->willReturn([]);
        $extensionModelUtilityMock = $this->getMockBuilder(ExtensionModelUtility::class)->getMock();
        $extensionModelUtilityMock->expects(self::any())->method('mapExtensionArrayToModel')->willReturn($extensionModelMock);
        $managementMock->_set('dependencyUtility', $dependencyUtilityMock);
        $managementMock->_set('installUtility', $installUtilityMock);
        $managementMock->_set('extensionModelUtility', $extensionModelUtilityMock);

        $downloadQueueMock = $this->getAccessibleMock(DownloadQueue::class, ['removeExtensionFromQueue', 'addExtensionToInstallQueue']);
        $downloadUtilityMock = $this->getAccessibleMock(DownloadUtility::class, ['download']);
        $extensionModelMock->expects(self::atLeastOnce())->method('getExtensionKey')->willReturn('foobar');
        $managementMock->_set('downloadUtility', $downloadUtilityMock);
        $managementMock->_set('downloadQueue', $downloadQueueMock);
        $resolvedDependencies = $managementMock->_call('downloadDependencies', $downloadQueue);
        self::assertEquals(['downloaded' => ['foobar' => $extensionModelMock]], $resolvedDependencies);
    }

    /**
     * @test
     */
    public function uninstallDependenciesToBeUpdatedCallsUninstall()
    {
        $managementMock = $this->getAccessibleMock(ExtensionManagementService::class, [
            'dummy'
        ]);
        $extensionModelMock = $this->getAccessibleMock(Extension::class, ['getExtensionKey']);
        $extensionModelMock->_set('extensionKey', 'foobar');
        $extensionModelMock->_set('version', '1.0.0');
        $extensionModelMock->expects(self::atLeastOnce())->method('getExtensionKey')->willReturn('foobar');
        $downloadQueue = [
            $extensionModelMock
        ];
        $installUtility = $this->getAccessibleMock(InstallUtility::class, ['uninstall'], [], '', false);
        $installUtility->expects(self::once())->method('uninstall')->with('foobar');
        $managementMock->_set('installUtility', $installUtility);
        $managementMock->_call('uninstallDependenciesToBeUpdated', $downloadQueue);
    }

    /**
     * @test
     */
    public function uninstallDependenciesToBeUpdatedReturnsResolvedDependencies()
    {
        $managementMock = $this->getAccessibleMock(ExtensionManagementService::class, [
            'dummy'
        ]);
        $extensionModelMock = $this->getAccessibleMock(Extension::class, ['getExtensionKey']);
        $extensionModelMock->_set('extensionKey', 'foobar');
        $extensionModelMock->_set('version', '1.0.0');
        $extensionModelMock->expects(self::atLeastOnce())->method('getExtensionKey')->willReturn('foobar');
        $downloadQueue = [
            $extensionModelMock
        ];
        $installUtility = $this->getAccessibleMock(InstallUtility::class, ['uninstall'], [], '', false);
        $managementMock->_set('installUtility', $installUtility);
        $resolvedDependencies = $managementMock->_call('uninstallDependenciesToBeUpdated', $downloadQueue);
        self::assertEquals(['updated' => ['foobar' => $extensionModelMock]], $resolvedDependencies);
    }

    /**
     * @test
     */
    public function installDependenciesCallsInstall()
    {
        $managementMock = $this->getAccessibleMock(
            ExtensionManagementService::class,
            ['emitWillInstallExtensionsSignal', 'emitHasInstalledExtensionSignal']
        );
        /** @var Extension $extensionMock */
        $extensionMock = $this->getMockBuilder(Extension::class)
            ->setMethods(['dummy'])
            ->getMock();
        $extensionMock->setExtensionKey('foobar');
        $installQueue = [
            'foobar' => $extensionMock,
        ];
        $installUtility = $this->getAccessibleMock(InstallUtility::class, ['install', 'emitWillInstallExtensionsSignal'], [], '', false);
        $installUtility->expects(self::once())->method('install')->with('foobar');
        $managementMock->_set('installUtility', $installUtility);
        $managementMock->_call('installDependencies', $installQueue);
    }

    /**
     * @test
     */
    public function installDependenciesReturnsResolvedDependencies()
    {
        $managementMock = $this->getAccessibleMock(ExtensionManagementService::class, [
            'emitWillInstallExtensionsSignal',
            'emitHasInstalledExtensionSignal'
        ]);
        /** @var Extension $extensionMock */
        $extensionMock = $this->getMockBuilder(Extension::class)
            ->setMethods(['dummy'])
            ->getMock();
        $extensionMock->setExtensionKey('foobar');
        $installQueue = [
            'foobar' => $extensionMock,
        ];
        $installUtility = $this->getAccessibleMock(InstallUtility::class, ['install', 'emitWillInstallExtensionsSignal'], [], '', false);
        $installUtility->expects(self::once())->method('install')->with('foobar');
        $managementMock->_set('installUtility', $installUtility);
        $resolvedDependencies = $managementMock->_call('installDependencies', $installQueue);
        self::assertEquals([
            'installed' => [
                'foobar' => 'foobar'
            ]
        ], $resolvedDependencies);
    }
}
