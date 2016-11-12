<?php
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

/**
 * Testcase
 *
 */
class ExtensionManagementServiceTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @test
     * @return void
     */
    public function resolveDependenciesCallsDownloadDependenciesIfDownloadKeyExistsInQueue()
    {
        $managementMock = $this->getAccessibleMock(
            \TYPO3\CMS\Extensionmanager\Service\ExtensionManagementService::class,
            ['downloadDependencies', 'uninstallDependenciesToBeUpdated', 'setInExtensionRepository', 'downloadMainExtension', 'isAutomaticInstallationEnabled']
        );
        $managementMock->expects($this->any())->method('downloadMainExtension')->will($this->returnValue([]));
        $managementMock->expects($this->any())->method('isAutomaticInstallationEnabled')->will($this->returnValue([false]));
        $extensionModelMock = $this->getAccessibleMock(\TYPO3\CMS\Extensionmanager\Domain\Model\Extension::class);
        $extensionModelMock->_set('extensionKey', 'foobar');
        $extensionModelMock->_set('version', '1.0.0');
        $dependencyUtilityMock = $this->getAccessibleMock(\TYPO3\CMS\Extensionmanager\Utility\DependencyUtility::class, ['checkDependencies']);
        $dependencyUtilityMock->expects($this->atLeastOnce())->method('checkDependencies');
        $managementMock->_set('dependencyUtility', $dependencyUtilityMock);
        $downloadQueueMock = $this->getAccessibleMock(\TYPO3\CMS\Extensionmanager\Domain\Model\DownloadQueue::class, ['isCopyQueueEmpty', 'isQueueEmpty', 'resetExtensionQueue', 'addExtensionToInstallQueue']);
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
     * @return void
     */
    public function resolveDependenciesCallsUpdateAndDownloadDependenciesIfUpdateKeyExistsInQueue()
    {
        $managementMock = $this->getAccessibleMock(
            \TYPO3\CMS\Extensionmanager\Service\ExtensionManagementService::class,
            ['downloadDependencies', 'uninstallDependenciesToBeUpdated', 'setInExtensionRepository', 'downloadMainExtension', 'isAutomaticInstallationEnabled']
        );
        $managementMock->expects($this->any())->method('downloadMainExtension')->will($this->returnValue([]));
        $managementMock->expects($this->any())->method('isAutomaticInstallationEnabled')->will($this->returnValue(true));
        $extensionModelMock = $this->getAccessibleMock(\TYPO3\CMS\Extensionmanager\Domain\Model\Extension::class);
        $extensionModelMock->_set('extensionKey', 'foobar');
        $extensionModelMock->_set('version', '1.0.0');
        $dependencyUtilityMock = $this->getAccessibleMock(\TYPO3\CMS\Extensionmanager\Utility\DependencyUtility::class, ['checkDependencies']);
        $dependencyUtilityMock->expects($this->atLeastOnce())->method('checkDependencies');
        $managementMock->_set('dependencyUtility', $dependencyUtilityMock);
        $downloadQueueMock = $this->getAccessibleMock(\TYPO3\CMS\Extensionmanager\Domain\Model\DownloadQueue::class, ['isCopyQueueEmpty', 'isQueueEmpty', 'resetExtensionQueue', 'addExtensionToInstallQueue']);
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
     * @return void
     */
    public function downloadDependenciesCallsDownloadUtilityDownloadMethod()
    {
        $managementMock = $this->getAccessibleMock(\TYPO3\CMS\Extensionmanager\Service\ExtensionManagementService::class, [
            'dummy'
        ]);

        $extensionModelMock = $this->getAccessibleMock(\TYPO3\CMS\Extensionmanager\Domain\Model\Extension::class, ['getExtensionKey']);
        $extensionModelMock->_set('extensionKey', 'foobar');
        $extensionModelMock->_set('version', '1.0.0');

        $dependencyUtilityMock = $this->getAccessibleMock(\TYPO3\CMS\Extensionmanager\Utility\DependencyUtility::class);
        $dependencyUtilityMock->expects($this->atLeastOnce())->method('checkDependencies');
        $installUtilityMock = $this->getMock(\TYPO3\CMS\Extensionmanager\Utility\InstallUtility::class);
        $installUtilityMock->expects($this->any())->method('enrichExtensionWithDetails')->will($this->returnValue([]));
        $extensionModelUtilityMock = $this->getMock(\TYPO3\CMS\Extensionmanager\Utility\ExtensionModelUtility::class);
        $extensionModelUtilityMock->expects($this->any())->method('mapExtensionArrayToModel')->will($this->returnValue($extensionModelMock));
        $managementMock->_set('dependencyUtility', $dependencyUtilityMock);
        $managementMock->_set('installUtility', $installUtilityMock);
        $managementMock->_set('extensionModelUtility', $extensionModelUtilityMock);

        $downloadQueue = [
            $extensionModelMock
        ];
        $downloadQueueMock = $this->getAccessibleMock(\TYPO3\CMS\Extensionmanager\Domain\Model\DownloadQueue::class, ['removeExtensionFromQueue', 'addExtensionToInstallQueue']);
        $downloadUtilityMock = $this->getAccessibleMock(\TYPO3\CMS\Extensionmanager\Utility\DownloadUtility::class, ['download']);
        $downloadUtilityMock->expects($this->once())->method('download')->with($extensionModelMock);
        $managementMock->_set('downloadUtility', $downloadUtilityMock);
        $managementMock->_set('downloadQueue', $downloadQueueMock);
        $managementMock->_call('downloadDependencies', $downloadQueue);
    }

    /**
     * @test
     * @return void
     */
    public function downloadDependenciesCallsRemoveExtensionFromQueue()
    {
        $managementMock = $this->getAccessibleMock(\TYPO3\CMS\Extensionmanager\Service\ExtensionManagementService::class, [
            'dummy'
        ]);

        /** @var \TYPO3\CMS\Extensionmanager\Domain\Model\Extension $extensionModelMock */
        $extensionModelMock = $this->getMock(\TYPO3\CMS\Extensionmanager\Domain\Model\Extension::class, ['getExtensionKey']);
        $extensionModelMock->setExtensionKey('foobar');
        $extensionModelMock->setVersion('1.0.0');
        $downloadQueue = [
            $extensionModelMock
        ];

        $dependencyUtilityMock = $this->getAccessibleMock(\TYPO3\CMS\Extensionmanager\Utility\DependencyUtility::class);
        $dependencyUtilityMock->expects($this->atLeastOnce())->method('checkDependencies');
        $installUtilityMock = $this->getMock(\TYPO3\CMS\Extensionmanager\Utility\InstallUtility::class);
        $installUtilityMock->expects($this->any())->method('enrichExtensionWithDetails')->will($this->returnValue([]));
        $extensionModelUtilityMock = $this->getMock(\TYPO3\CMS\Extensionmanager\Utility\ExtensionModelUtility::class);
        $extensionModelUtilityMock->expects($this->any())->method('mapExtensionArrayToModel')->will($this->returnValue($extensionModelMock));
        $managementMock->_set('dependencyUtility', $dependencyUtilityMock);
        $managementMock->_set('installUtility', $installUtilityMock);
        $managementMock->_set('extensionModelUtility', $extensionModelUtilityMock);

        $downloadQueueMock = $this->getAccessibleMock(\TYPO3\CMS\Extensionmanager\Domain\Model\DownloadQueue::class, ['removeExtensionFromQueue', 'addExtensionToInstallQueue']);
        $downloadUtilityMock = $this->getAccessibleMock(\TYPO3\CMS\Extensionmanager\Utility\DownloadUtility::class, ['download']);
        $downloadQueueMock->expects($this->once())->method('removeExtensionFromQueue')->with($extensionModelMock);
        $managementMock->_set('downloadUtility', $downloadUtilityMock);
        $managementMock->_set('downloadQueue', $downloadQueueMock);
        $managementMock->_call('downloadDependencies', $downloadQueue);
    }

    /**
     * @test
     * @return void
     */
    public function downloadDependenciesReturnsResolvedDependencies()
    {
        $managementMock = $this->getAccessibleMock(\TYPO3\CMS\Extensionmanager\Service\ExtensionManagementService::class, [
            'dummy'
        ]);

        $extensionModelMock = $this->getAccessibleMock(\TYPO3\CMS\Extensionmanager\Domain\Model\Extension::class, ['getExtensionKey']);
        $extensionModelMock->_set('extensionKey', 'foobar');
        $extensionModelMock->_set('version', '1.0.0');
        $downloadQueue = [
            $extensionModelMock
        ];

        $dependencyUtilityMock = $this->getAccessibleMock(\TYPO3\CMS\Extensionmanager\Utility\DependencyUtility::class);
        $dependencyUtilityMock->expects($this->atLeastOnce())->method('checkDependencies');
        $installUtilityMock = $this->getMock(\TYPO3\CMS\Extensionmanager\Utility\InstallUtility::class);
        $installUtilityMock->expects($this->any())->method('enrichExtensionWithDetails')->will($this->returnValue([]));
        $extensionModelUtilityMock = $this->getMock(\TYPO3\CMS\Extensionmanager\Utility\ExtensionModelUtility::class);
        $extensionModelUtilityMock->expects($this->any())->method('mapExtensionArrayToModel')->will($this->returnValue($extensionModelMock));
        $managementMock->_set('dependencyUtility', $dependencyUtilityMock);
        $managementMock->_set('installUtility', $installUtilityMock);
        $managementMock->_set('extensionModelUtility', $extensionModelUtilityMock);

        $downloadQueueMock = $this->getAccessibleMock(\TYPO3\CMS\Extensionmanager\Domain\Model\DownloadQueue::class, ['removeExtensionFromQueue', 'addExtensionToInstallQueue']);
        $downloadUtilityMock = $this->getAccessibleMock(\TYPO3\CMS\Extensionmanager\Utility\DownloadUtility::class, ['download']);
        $extensionModelMock->expects($this->atLeastOnce())->method('getExtensionKey')->will($this->returnValue('foobar'));
        $managementMock->_set('downloadUtility', $downloadUtilityMock);
        $managementMock->_set('downloadQueue', $downloadQueueMock);
        $resolvedDependencies = $managementMock->_call('downloadDependencies', $downloadQueue);
        $this->assertEquals(['downloaded' => ['foobar' => $extensionModelMock]], $resolvedDependencies);
    }

    /**
     * @test
     * @return void
     */
    public function uninstallDependenciesToBeUpdatedCallsUninstall()
    {
        $managementMock = $this->getAccessibleMock(\TYPO3\CMS\Extensionmanager\Service\ExtensionManagementService::class, [
            'dummy'
        ]);
        $extensionModelMock = $this->getAccessibleMock(\TYPO3\CMS\Extensionmanager\Domain\Model\Extension::class, ['getExtensionKey']);
        $extensionModelMock->_set('extensionKey', 'foobar');
        $extensionModelMock->_set('version', '1.0.0');
        $extensionModelMock->expects($this->atLeastOnce())->method('getExtensionKey')->will($this->returnValue('foobar'));
        $downloadQueue = [
            $extensionModelMock
        ];
        $installUtility = $this->getAccessibleMock(\TYPO3\CMS\Extensionmanager\Utility\InstallUtility::class, ['uninstall'], [], '', false);
        $installUtility->expects($this->once())->method('uninstall')->with('foobar');
        $managementMock->_set('installUtility', $installUtility);
        $managementMock->_call('uninstallDependenciesToBeUpdated', $downloadQueue);
    }

    /**
     * @test
     * @return void
     */
    public function uninstallDependenciesToBeUpdatedReturnsResolvedDependencies()
    {
        $managementMock = $this->getAccessibleMock(\TYPO3\CMS\Extensionmanager\Service\ExtensionManagementService::class, [
            'dummy'
        ]);
        $extensionModelMock = $this->getAccessibleMock(\TYPO3\CMS\Extensionmanager\Domain\Model\Extension::class, ['getExtensionKey']);
        $extensionModelMock->_set('extensionKey', 'foobar');
        $extensionModelMock->_set('version', '1.0.0');
        $extensionModelMock->expects($this->atLeastOnce())->method('getExtensionKey')->will($this->returnValue('foobar'));
        $downloadQueue = [
            $extensionModelMock
        ];
        $installUtility = $this->getAccessibleMock(\TYPO3\CMS\Extensionmanager\Utility\InstallUtility::class, ['uninstall'], [], '', false);
        $managementMock->_set('installUtility', $installUtility);
        $resolvedDependencies = $managementMock->_call('uninstallDependenciesToBeUpdated', $downloadQueue);
        $this->assertEquals(['updated' => ['foobar' => $extensionModelMock]], $resolvedDependencies);
    }

    /**
     * @test
     * @return void
     */
    public function installDependenciesCallsInstall()
    {
        $managementMock = $this->getAccessibleMock(
            \TYPO3\CMS\Extensionmanager\Service\ExtensionManagementService::class,
            ['emitWillInstallExtensionsSignal', 'emitHasInstalledExtensionSignal']
        );
        /** @var \TYPO3\CMS\Extensionmanager\Domain\Model\Extension $extensionMock */
        $extensionMock = $this->getMock(\TYPO3\CMS\Extensionmanager\Domain\Model\Extension::class, ['dummy']);
        $extensionMock->setExtensionKey('foobar');
        $installQueue = [
            'foobar' => $extensionMock,
        ];
        $installUtility = $this->getAccessibleMock(\TYPO3\CMS\Extensionmanager\Utility\InstallUtility::class, ['install', 'emitWillInstallExtensionsSignal'], [], '', false);
        $installUtility->expects($this->once())->method('install')->with('foobar');
        $managementMock->_set('installUtility', $installUtility);
        $managementMock->_call('installDependencies', $installQueue);
    }

    /**
     * @test
     * @return void
     */
    public function installDependenciesReturnsResolvedDependencies()
    {
        $managementMock = $this->getAccessibleMock(\TYPO3\CMS\Extensionmanager\Service\ExtensionManagementService::class, [
            'emitWillInstallExtensionsSignal',
            'emitHasInstalledExtensionSignal'
        ]);
        /** @var \TYPO3\CMS\Extensionmanager\Domain\Model\Extension $extensionMock */
        $extensionMock = $this->getMock(\TYPO3\CMS\Extensionmanager\Domain\Model\Extension::class, ['dummy']);
        $extensionMock->setExtensionKey('foobar');
        $installQueue = [
            'foobar' => $extensionMock,
        ];
        $installUtility = $this->getAccessibleMock(\TYPO3\CMS\Extensionmanager\Utility\InstallUtility::class, ['install', 'emitWillInstallExtensionsSignal'], [], '', false);
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
