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
        $managementMock->expects($this->any())->method('downloadMainExtension')->will($this->returnValue([]));
        $managementMock->expects($this->any())->method('isAutomaticInstallationEnabled')->will($this->returnValue([false]));
        $extensionModelMock = $this->getAccessibleMock(Extension::class);
        $extensionModelMock->_set('extensionKey', 'foobar');
        $extensionModelMock->_set('version', '1.0.0');
        $dependencyUtilityMock = $this->getAccessibleMock(DependencyUtility::class, ['checkDependencies']);
        $dependencyUtilityMock->expects($this->atLeastOnce())->method('checkDependencies');
        $managementMock->_set('dependencyUtility', $dependencyUtilityMock);
        $downloadQueueMock = $this->getAccessibleMock(DownloadQueue::class, ['isCopyQueueEmpty', 'isQueueEmpty', 'resetExtensionQueue', 'addExtensionToInstallQueue']);
        $downloadQueueMock->expects($this->any())->method('isCopyQueueEmpty')->willReturn(true);
        $downloadQueueMock->expects($this->at(1))->method('isQueueEmpty')->with('download')->willReturn(false);
        $downloadQueueMock->expects($this->at(4))->method('isQueueEmpty')->with('download')->willReturn(true);
        $downloadQueueMock->expects($this->at(5))->method('isQueueEmpty')->with('update')->willReturn(true);
        $downloadQueueMock->expects($this->atLeastOnce())->method('resetExtensionQueue')->will($this->returnValue([
            'download' => [
                'foo' => $extensionModelMock
            ]
        ]));
        $managementMock->_set('downloadQueue', $downloadQueueMock);
        $managementMock->expects($this->once())->method('downloadDependencies')->with(['foo' => $extensionModelMock])->will($this->returnValue([]));
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
        $managementMock->expects($this->any())->method('downloadMainExtension')->will($this->returnValue([]));
        $managementMock->expects($this->any())->method('isAutomaticInstallationEnabled')->will($this->returnValue(true));
        $extensionModelMock = $this->getAccessibleMock(Extension::class);
        $extensionModelMock->_set('extensionKey', 'foobar');
        $extensionModelMock->_set('version', '1.0.0');
        $dependencyUtilityMock = $this->getAccessibleMock(DependencyUtility::class, ['checkDependencies']);
        $dependencyUtilityMock->expects($this->atLeastOnce())->method('checkDependencies');
        $managementMock->_set('dependencyUtility', $dependencyUtilityMock);
        $downloadQueueMock = $this->getAccessibleMock(DownloadQueue::class, ['isCopyQueueEmpty', 'isQueueEmpty', 'resetExtensionQueue', 'addExtensionToInstallQueue']);
        $downloadQueueMock->expects($this->any())->method('isCopyQueueEmpty')->willReturn(true);
        $downloadQueueMock->expects($this->at(1))->method('isQueueEmpty')->with('download')->willReturn(false);
        $downloadQueueMock->expects($this->at(4))->method('isQueueEmpty')->with('download')->willReturn(true);
        $downloadQueueMock->expects($this->at(5))->method('isQueueEmpty')->with('update')->willReturn(true);
        $downloadQueueMock->expects($this->atLeastOnce())->method('resetExtensionQueue')->will($this->returnValue([
            'update' => [
                'foo' => $extensionModelMock
            ]
        ]));
        $managementMock->_set('downloadQueue', $downloadQueueMock);
        $managementMock->expects($this->once())->method('downloadDependencies')->with(['foo' => $extensionModelMock])->will($this->returnValue([]));
        $managementMock->expects($this->once())->method('uninstallDependenciesToBeUpdated')->with(['foo' => $extensionModelMock])->will($this->returnValue([]));
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
        $dependencyUtilityMock->expects($this->atLeastOnce())->method('checkDependencies');
        $installUtilityMock = $this->getMockBuilder(InstallUtility::class)->getMock();
        $installUtilityMock->expects($this->any())->method('enrichExtensionWithDetails')->will($this->returnValue([]));
        $extensionModelUtilityMock = $this->getMockBuilder(ExtensionModelUtility::class)->getMock();
        $extensionModelUtilityMock->expects($this->any())->method('mapExtensionArrayToModel')->will($this->returnValue($extensionModelMock));
        $managementMock->_set('dependencyUtility', $dependencyUtilityMock);
        $managementMock->_set('installUtility', $installUtilityMock);
        $managementMock->_set('extensionModelUtility', $extensionModelUtilityMock);

        $downloadQueue = [
            $extensionModelMock
        ];
        $downloadQueueMock = $this->getAccessibleMock(DownloadQueue::class, ['removeExtensionFromQueue', 'addExtensionToInstallQueue']);
        $downloadUtilityMock = $this->getAccessibleMock(DownloadUtility::class, ['download']);
        $downloadUtilityMock->expects($this->once())->method('download')->with($extensionModelMock);
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
        $dependencyUtilityMock->expects($this->atLeastOnce())->method('checkDependencies');
        $installUtilityMock = $this->getMockBuilder(InstallUtility::class)->getMock();
        $installUtilityMock->expects($this->any())->method('enrichExtensionWithDetails')->will($this->returnValue([]));
        $extensionModelUtilityMock = $this->getMockBuilder(ExtensionModelUtility::class)->getMock();
        $extensionModelUtilityMock->expects($this->any())->method('mapExtensionArrayToModel')->will($this->returnValue($extensionModelMock));
        $managementMock->_set('dependencyUtility', $dependencyUtilityMock);
        $managementMock->_set('installUtility', $installUtilityMock);
        $managementMock->_set('extensionModelUtility', $extensionModelUtilityMock);

        $downloadQueueMock = $this->getAccessibleMock(DownloadQueue::class, ['removeExtensionFromQueue', 'addExtensionToInstallQueue']);
        $downloadUtilityMock = $this->getAccessibleMock(DownloadUtility::class, ['download']);
        $downloadQueueMock->expects($this->once())->method('removeExtensionFromQueue')->with($extensionModelMock);
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
        $dependencyUtilityMock->expects($this->atLeastOnce())->method('checkDependencies');
        $installUtilityMock = $this->getMockBuilder(InstallUtility::class)->getMock();
        $installUtilityMock->expects($this->any())->method('enrichExtensionWithDetails')->will($this->returnValue([]));
        $extensionModelUtilityMock = $this->getMockBuilder(ExtensionModelUtility::class)->getMock();
        $extensionModelUtilityMock->expects($this->any())->method('mapExtensionArrayToModel')->will($this->returnValue($extensionModelMock));
        $managementMock->_set('dependencyUtility', $dependencyUtilityMock);
        $managementMock->_set('installUtility', $installUtilityMock);
        $managementMock->_set('extensionModelUtility', $extensionModelUtilityMock);

        $downloadQueueMock = $this->getAccessibleMock(DownloadQueue::class, ['removeExtensionFromQueue', 'addExtensionToInstallQueue']);
        $downloadUtilityMock = $this->getAccessibleMock(DownloadUtility::class, ['download']);
        $extensionModelMock->expects($this->atLeastOnce())->method('getExtensionKey')->will($this->returnValue('foobar'));
        $managementMock->_set('downloadUtility', $downloadUtilityMock);
        $managementMock->_set('downloadQueue', $downloadQueueMock);
        $resolvedDependencies = $managementMock->_call('downloadDependencies', $downloadQueue);
        $this->assertEquals(['downloaded' => ['foobar' => $extensionModelMock]], $resolvedDependencies);
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
        $extensionModelMock->expects($this->atLeastOnce())->method('getExtensionKey')->will($this->returnValue('foobar'));
        $downloadQueue = [
            $extensionModelMock
        ];
        $installUtility = $this->getAccessibleMock(InstallUtility::class, ['uninstall'], [], '', false);
        $installUtility->expects($this->once())->method('uninstall')->with('foobar');
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
        $extensionModelMock->expects($this->atLeastOnce())->method('getExtensionKey')->will($this->returnValue('foobar'));
        $downloadQueue = [
            $extensionModelMock
        ];
        $installUtility = $this->getAccessibleMock(InstallUtility::class, ['uninstall'], [], '', false);
        $managementMock->_set('installUtility', $installUtility);
        $resolvedDependencies = $managementMock->_call('uninstallDependenciesToBeUpdated', $downloadQueue);
        $this->assertEquals(['updated' => ['foobar' => $extensionModelMock]], $resolvedDependencies);
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
        $installUtility->expects($this->once())->method('install')->with('foobar');
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
        $installUtility->expects($this->once())->method('install')->with('foobar');
        $managementMock->_set('installUtility', $installUtility);
        $resolvedDependencies = $managementMock->_call('installDependencies', $installQueue);
        $this->assertEquals([
            'installed' => [
                'foobar' => 'foobar'
            ]
        ], $resolvedDependencies);
    }
}
