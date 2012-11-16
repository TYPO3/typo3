<?php
namespace TYPO3\CMS\Extensionmanager\Tests\Unit\Service;

/***************************************************************
 * Copyright notice
 *
 * (c) 2012
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Testcase
 *
 */
class ExtensionManagementServiceTest extends \TYPO3\CMS\Extbase\Tests\Unit\BaseTestCase {

	/**
	 * @test
	 * @return void
	 */
	public function resolveDependenciesCallsDownloadDependenciesIfDownloadKeyExistsInQueue() {
		$managementMock = $this->getAccessibleMock(
			'TYPO3\\CMS\\Extensionmanager\\Service\\ExtensionManagementService',
			array('downloadDependencies', 'uninstallDependenciesToBeUpdated')
		);
		$extensionModelMock = $this->getAccessibleMock('TYPO3\\CMS\\Extensionmanager\\Domain\\Model\\Extension');
		$extensionModelMock->_set('extensionKey', 'foobar');
		$extensionModelMock->_set('version', '1.0.0');
		$dependencyUtilityMock = $this->getAccessibleMock('TYPO3\\CMS\\Extensionmanager\\Utility\\DependencyUtility', array('buildExtensionDependenciesTree'));
		$dependencyUtilityMock->expects($this->atLeastOnce())->method('buildExtensionDependenciesTree');
		$managementMock->_set('dependencyUtility', $dependencyUtilityMock);
		$downloadQueueMock = $this->getAccessibleMock('TYPO3\\CMS\\Extensionmanager\\Domain\\Model\\DownloadQueue', array('getExtensionQueue', 'addExtensionToInstallQueue'));
		$downloadQueueMock->expects($this->atLeastOnce())->method('getExtensionQueue')->will($this->returnValue(array(
			'download' => array(
				'foo' => $extensionModelMock
			)
		)));
		$managementMock->_set('downloadQueue', $downloadQueueMock);
		$managementMock->expects($this->once())->method('downloadDependencies')->with(array('foo' => $extensionModelMock))->will($this->returnValue(array()));
		$managementMock->_call('resolveDependenciesAndInstall', $extensionModelMock);
	}

	/**
	 * @test
	 * @return void
	 */
	public function resolveDependenciesCallsUpdateAndDownloadDependenciesIfUpdateKeyExistsInQueue() {
		$managementMock = $this->getAccessibleMock(
			'TYPO3\\CMS\\Extensionmanager\\Service\\ExtensionManagementService',
			array('downloadDependencies', 'uninstallDependenciesToBeUpdated')
		);
		$extensionModelMock = $this->getAccessibleMock('TYPO3\\CMS\\Extensionmanager\\Domain\\Model\\Extension');
		$extensionModelMock->_set('extensionKey', 'foobar');
		$extensionModelMock->_set('version', '1.0.0');
		$dependencyUtilityMock = $this->getAccessibleMock('TYPO3\\CMS\\Extensionmanager\\Utility\\DependencyUtility', array('buildExtensionDependenciesTree'));
		$dependencyUtilityMock->expects($this->atLeastOnce())->method('buildExtensionDependenciesTree');
		$managementMock->_set('dependencyUtility', $dependencyUtilityMock);
		$downloadQueueMock = $this->getAccessibleMock('TYPO3\\CMS\\Extensionmanager\\Domain\\Model\\DownloadQueue', array('getExtensionQueue', 'addExtensionToInstallQueue'));
		$downloadQueueMock->expects($this->atLeastOnce())->method('getExtensionQueue')->will($this->returnValue(array(
			'update' => array(
				'foo' => $extensionModelMock
			)
		)));
		$managementMock->_set('downloadQueue', $downloadQueueMock);
		$managementMock->expects($this->once())->method('downloadDependencies')->with(array('foo' => $extensionModelMock))->will($this->returnValue(array()));
		$managementMock->expects($this->once())->method('uninstallDependenciesToBeUpdated')->with(array('foo' => $extensionModelMock))->will($this->returnValue(array()));
		$managementMock->_call('resolveDependenciesAndInstall', $extensionModelMock);
	}

	/**
	 * @test
	 * @return void
	 */
	public function downloadDependenciesCallsDownloadUtilityDownloadMethod() {
		$managementMock = $this->getAccessibleMock('TYPO3\\CMS\\Extensionmanager\\Service\\ExtensionManagementService', array(
			'dummy'
		));
		$extensionModelMock = $this->getAccessibleMock('TYPO3\\CMS\\Extensionmanager\\Domain\\Model\\Extension', array('getExtensionKey'));
		$extensionModelMock->_set('extensionKey', 'foobar');
		$extensionModelMock->_set('version', '1.0.0');
		$downloadQueue = array(
			$extensionModelMock
		);
		$downloadQueueMock = $this->getAccessibleMock('TYPO3\\CMS\\Extensionmanager\\Domain\\Model\\DownloadQueue', array('removeExtensionFromQueue', 'addExtensionToInstallQueue'));
		$downloadUtilityMock = $this->getAccessibleMock('TYPO3\\CMS\\Extensionmanager\\Utility\\DownloadUtility', array('download'));
		$downloadUtilityMock->expects($this->once())->method('download')->with(clone $extensionModelMock);
		$managementMock->_set('downloadUtility', $downloadUtilityMock);
		$managementMock->_set('downloadQueue', $downloadQueueMock);
		$managementMock->_call('downloadDependencies', $downloadQueue);
	}

	/**
	 * @test
	 * @return void
	 */
	public function downloadDependenciesCallsRemoveExtensionFromQueue() {
		$managementMock = $this->getAccessibleMock('TYPO3\\CMS\\Extensionmanager\\Service\\ExtensionManagementService', array(
			'dummy'
		));
		$extensionModelMock = $this->getAccessibleMock('TYPO3\\CMS\\Extensionmanager\\Domain\\Model\\Extension', array('getExtensionKey'));
		$extensionModelMock->_set('extensionKey', 'foobar');
		$extensionModelMock->_set('version', '1.0.0');
		$downloadQueue = array(
			$extensionModelMock
		);
		$downloadQueueMock = $this->getAccessibleMock('TYPO3\\CMS\\Extensionmanager\\Domain\\Model\\DownloadQueue', array('removeExtensionFromQueue', 'addExtensionToInstallQueue'));
		$downloadUtilityMock = $this->getAccessibleMock('TYPO3\\CMS\\Extensionmanager\\Utility\\DownloadUtility', array('download'));
		$downloadQueueMock->expects($this->once())->method('removeExtensionFromQueue')->with(clone $extensionModelMock);
		$managementMock->_set('downloadUtility', $downloadUtilityMock);
		$managementMock->_set('downloadQueue', $downloadQueueMock);
		$managementMock->_call('downloadDependencies', $downloadQueue);
	}

	/**
	 * @test
	 * @return void
	 */
	public function downloadDependenciesReturnsResolvedDependencies() {
		$managementMock = $this->getAccessibleMock('TYPO3\\CMS\\Extensionmanager\\Service\\ExtensionManagementService', array(
			'dummy'
		));
		$extensionModelMock = $this->getAccessibleMock('TYPO3\\CMS\\Extensionmanager\\Domain\\Model\\Extension', array('getExtensionKey'));
		$extensionModelMock->_set('extensionKey', 'foobar');
		$extensionModelMock->_set('version', '1.0.0');
		$downloadQueue = array(
			$extensionModelMock
		);
		$downloadQueueMock = $this->getAccessibleMock('TYPO3\\CMS\\Extensionmanager\\Domain\\Model\\DownloadQueue', array('removeExtensionFromQueue', 'addExtensionToInstallQueue'));
		$downloadUtilityMock = $this->getAccessibleMock('TYPO3\\CMS\\Extensionmanager\\Utility\\DownloadUtility', array('download'));
		$extensionModelMock->expects($this->atLeastOnce())->method('getExtensionKey')->will($this->returnValue('foobar'));
		$managementMock->_set('downloadUtility', $downloadUtilityMock);
		$managementMock->_set('downloadQueue', $downloadQueueMock);
		$resolvedDependencies = $managementMock->_call('downloadDependencies', $downloadQueue);
		$this->assertEquals(array('downloaded' => array('foobar' => $extensionModelMock)), $resolvedDependencies);
	}

	/**
	 * @test
	 * @return void
	 */
	public function uninstallDependenciesToBeUpdatedCallsUninstall() {
		$managementMock = $this->getAccessibleMock('TYPO3\\CMS\\Extensionmanager\\Service\\ExtensionManagementService', array(
			'dummy'
		));
		$extensionModelMock = $this->getAccessibleMock('TYPO3\\CMS\\Extensionmanager\\Domain\\Model\\Extension', array('getExtensionKey'));
		$extensionModelMock->_set('extensionKey', 'foobar');
		$extensionModelMock->_set('version', '1.0.0');
		$extensionModelMock->expects($this->atLeastOnce())->method('getExtensionKey')->will($this->returnValue('foobar'));
		$downloadQueue = array(
			$extensionModelMock
		);
		$installUtility = $this->getAccessibleMock('TYPO3\\CMS\\Extensionmanager\\Utility\\InstallUtility', array('uninstall'));
		$installUtility->expects($this->once())->method('uninstall')->with('foobar');
		$managementMock->_set('installUtility', $installUtility);
		$managementMock->_call('uninstallDependenciesToBeUpdated', $downloadQueue);
	}

	/**
	 * @test
	 * @return void
	 */
	public function uninstallDependenciesToBeUpdatedReturnsResolvedDependencies() {
		$managementMock = $this->getAccessibleMock('TYPO3\\CMS\\Extensionmanager\\Service\\ExtensionManagementService', array(
			'dummy'
		));
		$extensionModelMock = $this->getAccessibleMock('TYPO3\\CMS\\Extensionmanager\\Domain\\Model\\Extension', array('getExtensionKey'));
		$extensionModelMock->_set('extensionKey', 'foobar');
		$extensionModelMock->_set('version', '1.0.0');
		$extensionModelMock->expects($this->atLeastOnce())->method('getExtensionKey')->will($this->returnValue('foobar'));
		$downloadQueue = array(
			$extensionModelMock
		);
		$installUtility = $this->getAccessibleMock('TYPO3\\CMS\\Extensionmanager\\Utility\\InstallUtility', array('uninstall'));
		$managementMock->_set('installUtility', $installUtility);
		$resolvedDependencies = $managementMock->_call('uninstallDependenciesToBeUpdated', $downloadQueue);
		$this->assertEquals(array('updated' => array('foobar' => $extensionModelMock)), $resolvedDependencies);
	}

	/**
	 * @test
	 * @return void
	 */
	public function installDependenciesCallsInstall() {
		$managementMock = $this->getAccessibleMock(
			'TYPO3\\CMS\\Extensionmanager\\Service\\ExtensionManagementService',
			array('dummy')
		);
		$installQueue = array(
			'foobar' => array(
				'key' => 'foobar',
				'siteRelPath' => 'path'
			)
		);
		$installUtility = $this->getAccessibleMock('TYPO3\\CMS\\Extensionmanager\\Utility\\InstallUtility', array('install'));
		$installUtility->expects($this->once())->method('install')->with(array(
			'key' => 'foobar',
			'siteRelPath' => 'path'
		));
		$managementMock->_set('installUtility', $installUtility);
		$managementMock->_call('installDependencies', $installQueue);
	}

	/**
	 * @test
	 * @return void
	 */
	public function installDependenciesReturnsResolvedDependencies() {
		$managementMock = $this->getAccessibleMock('TYPO3\\CMS\\Extensionmanager\\Service\\ExtensionManagementService', array(
			'dummy'
		));
		$installQueue = array(
			'foobar' => 'foobar'
		);
		$installUtility = $this->getAccessibleMock('TYPO3\\CMS\\Extensionmanager\\Utility\\InstallUtility', array('install'));
		$installUtility->expects($this->once())->method('install')->with('foobar');
		$managementMock->_set('installUtility', $installUtility);
		$resolvedDependencies = $managementMock->_call('installDependencies', $installQueue);
		$this->assertEquals(array(
			'installed' => array(
				'foobar' => 'foobar'
			)
		), $resolvedDependencies);
	}

}


?>