<?php
namespace TYPO3\CMS\Extensionmanager\Tests\Unit\Utility;

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
 * Test for DependencyUtility
 *
 */
class DependencyUtilityTest extends \TYPO3\CMS\Extbase\Tests\Unit\BaseTestCase {

	/**
	 * @test
	 * @return void
	 */
	public function convertDependenciesToObjectsCreatesObjectStorage() {
		$serializedDependencies = serialize(array(
			'depends' => array(
				'php' => '5.1.0-0.0.0',
				'typo3' => '4.2.0-4.4.99',
				'fn_lib' => ''
			)
		));
		/** @var $dependencyUtility \TYPO3\CMS\Extensionmanager\Utility\DependencyUtility */
		$dependencyUtility = $this->getAccessibleMock('TYPO3\\CMS\\Extensionmanager\\Utility\\DependencyUtility', array('dummy'));
		$objectManagerMock = $this->getAccessibleMock('TYPO3\\CMS\\Extbase\\Object\\ObjectManager', array('get'));
		$dependencyModelMock = $this->getAccessibleMock('TYPO3\\CMS\\Extensionmanager\\Domain\\Model\\Dependency', array('dummy'));
		$objectManagerMock->expects($this->any())->method('get')->will($this->returnValue($dependencyModelMock));
		$dependencyUtility->_set('objectManager', $objectManagerMock);
		$objectStorage = $dependencyUtility->convertDependenciesToObjects($serializedDependencies);
		$this->assertTrue($objectStorage instanceof \SplObjectStorage);
	}

	/**
	 * @test
	 * @return void
	 */
	public function convertDependenciesToObjectsSetsIdentifier() {
		$serializedDependencies = serialize(array(
			'depends' => array(
				'php' => '5.1.0-0.0.0',
				'typo3' => '4.2.0-4.4.99',
				'fn_lib' => ''
			)
		));
		/** @var $dependencyUtility \TYPO3\CMS\Extensionmanager\Utility\DependencyUtility */
		$dependencyUtility = $this->getAccessibleMock('TYPO3\\CMS\\Extensionmanager\\Utility\\DependencyUtility', array('dummy'));
		$objectManagerMock = $this->getAccessibleMock('TYPO3\\CMS\\Extbase\\Object\\ObjectManager', array('get'));
		$dependencyModelMock = $this->getAccessibleMock('TYPO3\\CMS\\Extensionmanager\\Domain\\Model\\Dependency', array('setIdentifier'));
		$objectManagerMock->expects($this->any())->method('get')->will($this->returnValue($dependencyModelMock));
		$dependencyUtility->_set('objectManager', $objectManagerMock);
		$dependencyModelMock->expects($this->at(0))->method('setIdentifier')->with('php');
		$dependencyModelMock->expects($this->at(1))->method('setIdentifier')->with('typo3');
		$dependencyModelMock->expects($this->at(2))->method('setIdentifier')->with('fn_lib');
		$dependencyUtility->convertDependenciesToObjects($serializedDependencies);
	}

	/**
	 * @return array
	 */
	public function convertDependenciesToObjectSetsVersionDataProvider() {
		return array(
			'everything ok' => array(
				array(
					'depends' => array(
						'typo3' => '4.2.0-4.4.99'
					)
				),
				array(
					'4.2.0',
					'4.4.99'
				)
			),
			'empty high value' => array(
				array(
					'depends' => array(
						'typo3' => '4.2.0-0.0.0'
					)
				),
				array(
					'4.2.0',
					''
				)
			),
			'empty low value' => array(
				array(
					'depends' => array(
						'typo3' => '0.0.0-4.4.99'
					)
				),
				array(
					'',
					'4.4.99'
				)
			),
			'only one value' => array(
				array(
					'depends' => array(
						'typo3' => '4.4.99'
					)
				),
				array(
					'4.4.99',
					'',
				)
			),
		);
	}

	/**
	 * @test 	 * @return void
	 * @dataProvider convertDependenciesToObjectSetsVersionDataProvider
	 * @param $dependencyString
	 * @param $returnValue
	 * @return void
	 */
	public function convertDependenciesToObjectSetsVersion($dependencyString, $returnValue) {
		$serializedDependencies = serialize($dependencyString);
		/** @var $dependencyUtility \TYPO3\CMS\Extensionmanager\Utility\DependencyUtility */
		$dependencyUtility = $this->getAccessibleMock('TYPO3\\CMS\\Extensionmanager\\Utility\\DependencyUtility', array('dummy'));
		$objectManagerMock = $this->getAccessibleMock('TYPO3\\CMS\\Extbase\\Object\\ObjectManager', array('get'));
		$dependencyModelMock = $this->getAccessibleMock('TYPO3\\CMS\\Extensionmanager\\Domain\\Model\\Dependency', array('setHighestVersion', 'setLowestVersion'));
		$objectManagerMock->expects($this->any())->method('get')->will($this->returnValue($dependencyModelMock));
		$dependencyUtility->_set('objectManager', $objectManagerMock);
		$dependencyModelMock->expects($this->atLeastOnce())->method('setLowestVersion')->with($this->identicalTo($returnValue[0]));
		$dependencyModelMock->expects($this->atLeastOnce())->method('setHighestVersion')->with($this->identicalTo($returnValue[1]));
		$dependencyUtility->convertDependenciesToObjects($serializedDependencies);
	}

	/**
	 * @test
	 * @return void
	 */
	public function checkTypo3DependencyThrowsExceptionIfVersionNumberIsTooLow() {
		$dependencyModelMock = $this->getAccessibleMock('TYPO3\\CMS\\Extensionmanager\\Domain\\Model\\Dependency', array('getHighestVersion', 'getLowestVersion'));
		$dependencyModelMock->expects($this->atLeastOnce())->method('getLowestVersion')->will($this->returnValue('15.0.0'));
		$dependencyModelMock->_set('identifier', 'typo3');
		$dependencyUtility = $this->getAccessibleMock('TYPO3\\CMS\\Extensionmanager\\Utility\\DependencyUtility', array('dummy'));
		$this->setExpectedException('TYPO3\\CMS\\Extensionmanager\\Exception\\ExtensionManagerException', 'Your TYPO3 version is lower than necessary. You need at least TYPO3 version 15.0.0');
		$dependencyUtility->_call('checkTypo3Dependency', $dependencyModelMock);
	}

	/**
	 * @test
	 * @return void
	 */
	public function checkTypo3DependencyThrowsExceptionIfVersionNumberIsTooHigh() {
		$dependencyModelMock = $this->getAccessibleMock('TYPO3\\CMS\\Extensionmanager\\Domain\\Model\\Dependency', array('getHighestVersion', 'getLowestVersion'));
		$dependencyModelMock->expects($this->atLeastOnce())->method('getHighestVersion')->will($this->returnValue('3.0.0'));
		$dependencyModelMock->expects($this->atLeastOnce())->method('getLowestVersion')->will($this->returnValue('1.0.0'));
		$dependencyModelMock->_set('identifier', 'typo3');
		$dependencyUtility = $this->getAccessibleMock('TYPO3\\CMS\\Extensionmanager\\Utility\\DependencyUtility', array('dummy'));
		$this->setExpectedException('TYPO3\\CMS\\Extensionmanager\\Exception\\ExtensionManagerException', 'Your TYPO3 version is higher than allowed. You can use TYPO3 versions 1.0.0 - 3.0.0');
		$dependencyUtility->_call('checkTypo3Dependency', $dependencyModelMock);
	}

	/**
	 * @test
	 * @return void
	 */
	public function checkTypo3DependencyThrowsExceptionIfIdentifierIsNotTypo3() {
		$dependencyModelMock = $this->getAccessibleMock('TYPO3\\CMS\\Extensionmanager\\Domain\\Model\\Dependency', array('getHighestVersion', 'getLowestVersion'));
		$dependencyModelMock->_set('identifier', '123');
		$dependencyUtility = $this->getAccessibleMock('TYPO3\\CMS\\Extensionmanager\\Utility\\DependencyUtility', array('dummy'));
		$this->setExpectedException('TYPO3\\CMS\\Extensionmanager\\Exception\\ExtensionManagerException', 'checkTypo3Dependency can only check TYPO3 dependencies. Found dependency with identifier "123"');
		$dependencyUtility->_call('checkTypo3Dependency', $dependencyModelMock);
	}

	/**
	 * @test
	 * @return void
	 */
	public function checkTypo3DependencyReturnsTrueIfVersionNumberIsInRange() {
		$dependencyModelMock = $this->getAccessibleMock('TYPO3\\CMS\\Extensionmanager\\Domain\\Model\\Dependency', array('getHighestVersion', 'getLowestVersion'));
		$dependencyModelMock->expects($this->atLeastOnce())->method('getHighestVersion')->will($this->returnValue('15.0.0'));
		$dependencyModelMock->expects($this->atLeastOnce())->method('getLowestVersion')->will($this->returnValue('1.0.0'));
		$dependencyModelMock->_set('identifier', 'typo3');
		$dependencyUtility = $this->getAccessibleMock('TYPO3\\CMS\\Extensionmanager\\Utility\\DependencyUtility', array('dummy'));
		$this->assertTrue($dependencyUtility->_call('checkTypo3Dependency', $dependencyModelMock));
	}

	/**
	 * @test
	 * @return void
	 */
	public function checkTypo3DependencyCanHandleEmptyVersionHighestVersion() {
		$dependencyModelMock = $this->getAccessibleMock('TYPO3\\CMS\\Extensionmanager\\Domain\\Model\\Dependency', array('getHighestVersion', 'getLowestVersion'));
		$dependencyModelMock->expects($this->atLeastOnce())->method('getHighestVersion')->will($this->returnValue(''));
		$dependencyModelMock->expects($this->atLeastOnce())->method('getLowestVersion')->will($this->returnValue('1.0.0'));
		$dependencyModelMock->_set('identifier', 'typo3');
		$dependencyUtility = $this->getAccessibleMock('TYPO3\\CMS\\Extensionmanager\\Utility\\DependencyUtility', array('dummy'));
		$this->assertTrue($dependencyUtility->_call('checkTypo3Dependency', $dependencyModelMock));
	}

	/**
	 * @test
	 * @return void
	 */
	public function checkTypo3DependencyCanHandleEmptyVersionLowestVersion() {
		$dependencyModelMock = $this->getAccessibleMock('TYPO3\\CMS\\Extensionmanager\\Domain\\Model\\Dependency', array('getHighestVersion', 'getLowestVersion'));
		$dependencyModelMock->expects($this->atLeastOnce())->method('getHighestVersion')->will($this->returnValue('15.0.0'));
		$dependencyModelMock->expects($this->atLeastOnce())->method('getLowestVersion')->will($this->returnValue(''));
		$dependencyModelMock->_set('identifier', 'typo3');
		$dependencyUtility = $this->getAccessibleMock('TYPO3\\CMS\\Extensionmanager\\Utility\\DependencyUtility', array('dummy'));
		$this->assertTrue($dependencyUtility->_call('checkTypo3Dependency', $dependencyModelMock));
	}

	/**
	 * @test
	 * @return void
	 */
	public function checkPhpDependencyThrowsExceptionIfVersionNumberIsTooLow() {
		$dependencyModelMock = $this->getAccessibleMock('TYPO3\\CMS\\Extensionmanager\\Domain\\Model\\Dependency', array('getHighestVersion', 'getLowestVersion'));
		$dependencyModelMock->expects($this->atLeastOnce())->method('getLowestVersion')->will($this->returnValue('15.0.0'));
		$dependencyModelMock->_set('identifier', 'php');
		$dependencyUtility = $this->getAccessibleMock('TYPO3\\CMS\\Extensionmanager\\Utility\\DependencyUtility', array('dummy'));
		$this->setExpectedException('TYPO3\\CMS\\Extensionmanager\\Exception\\ExtensionManagerException', 'Your PHP version is lower than necessary. You need at least PHP version 15.0.0');
		$dependencyUtility->_call('checkPhpDependency', $dependencyModelMock);
	}

	/**
	 * @test
	 * @return void
	 */
	public function checkPhpDependencyThrowsExceptionIfVersionNumberIsTooHigh() {
		$dependencyModelMock = $this->getAccessibleMock('TYPO3\\CMS\\Extensionmanager\\Domain\\Model\\Dependency', array('getHighestVersion', 'getLowestVersion'));
		$dependencyModelMock->expects($this->atLeastOnce())->method('getHighestVersion')->will($this->returnValue('3.0.0'));
		$dependencyModelMock->expects($this->atLeastOnce())->method('getLowestVersion')->will($this->returnValue('1.0.0'));
		$dependencyModelMock->_set('identifier', 'PHP');
		$dependencyUtility = $this->getAccessibleMock('TYPO3\\CMS\\Extensionmanager\\Utility\\DependencyUtility', array('dummy'));
		$this->setExpectedException('TYPO3\\CMS\\Extensionmanager\\Exception\\ExtensionManagerException', 'Your PHP version is higher than allowed. You can use PHP versions 1.0.0 - 3.0.0');
		$dependencyUtility->_call('checkPhpDependency', $dependencyModelMock);
	}

	/**
	 * @test
	 * @return void
	 */
	public function checkPhpDependencyThrowsExceptionIfIdentifierIsNotTypo3() {
		$dependencyModelMock = $this->getAccessibleMock('TYPO3\\CMS\\Extensionmanager\\Domain\\Model\\Dependency', array('getHighestVersion', 'getLowestVersion'));
		$dependencyModelMock->_set('identifier', '123');
		$dependencyUtility = $this->getAccessibleMock('TYPO3\\CMS\\Extensionmanager\\Utility\\DependencyUtility', array('dummy'));
		$this->setExpectedException('TYPO3\\CMS\\Extensionmanager\\Exception\\ExtensionManagerException', 'checkPhpDependency can only check PHP dependencies. Found dependency with identifier "123"');
		$dependencyUtility->_call('checkPhpDependency', $dependencyModelMock);
	}

	/**
	 * @test
	 * @return void
	 */
	public function checkPhpDependencyReturnsTrueIfVersionNumberIsInRange() {
		$dependencyModelMock = $this->getAccessibleMock('TYPO3\\CMS\\Extensionmanager\\Domain\\Model\\Dependency', array('getHighestVersion', 'getLowestVersion'));
		$dependencyModelMock->expects($this->atLeastOnce())->method('getHighestVersion')->will($this->returnValue('15.0.0'));
		$dependencyModelMock->expects($this->atLeastOnce())->method('getLowestVersion')->will($this->returnValue('1.0.0'));
		$dependencyModelMock->_set('identifier', 'PHP');
		$dependencyUtility = $this->getAccessibleMock('TYPO3\\CMS\\Extensionmanager\\Utility\\DependencyUtility', array('dummy'));
		$this->assertTrue($dependencyUtility->_call('checkPhpDependency', $dependencyModelMock));
	}

	/**
	 * @test
	 * @return void
	 */
	public function checkPhpDependencyCanHandleEmptyVersionHighestVersion() {
		$dependencyModelMock = $this->getAccessibleMock('TYPO3\\CMS\\Extensionmanager\\Domain\\Model\\Dependency', array('getHighestVersion', 'getLowestVersion'));
		$dependencyModelMock->expects($this->atLeastOnce())->method('getHighestVersion')->will($this->returnValue(''));
		$dependencyModelMock->expects($this->atLeastOnce())->method('getLowestVersion')->will($this->returnValue('1.0.0'));
		$dependencyModelMock->_set('identifier', 'PHP');
		$dependencyUtility = $this->getAccessibleMock('TYPO3\\CMS\\Extensionmanager\\Utility\\DependencyUtility', array('dummy'));
		$this->assertTrue($dependencyUtility->_call('checkPhpDependency', $dependencyModelMock));
	}

	/**
	 * @test
	 * @return void
	 */
	public function checkPhpDependencyCanHandleEmptyVersionLowestVersion() {
		$dependencyModelMock = $this->getAccessibleMock('TYPO3\\CMS\\Extensionmanager\\Domain\\Model\\Dependency', array('getHighestVersion', 'getLowestVersion'));
		$dependencyModelMock->expects($this->atLeastOnce())->method('getHighestVersion')->will($this->returnValue('15.0.0'));
		$dependencyModelMock->expects($this->atLeastOnce())->method('getLowestVersion')->will($this->returnValue(''));
		$dependencyModelMock->_set('identifier', 'PHP');
		$dependencyUtility = $this->getAccessibleMock('TYPO3\\CMS\\Extensionmanager\\Utility\\DependencyUtility', array('dummy'));
		$this->assertTrue($dependencyUtility->_call('checkPhpDependency', $dependencyModelMock));
	}

	/**
	 * @test
	 * @return void
	 */
	public function checkDependenciesCallsMethodToCheckPhpDependencies() {
		$dependencyModelMock = $this->getAccessibleMock('TYPO3\\CMS\\Extensionmanager\\Domain\\Model\\Dependency', array('getHighestVersion', 'getLowestVersion'));
		$dependencyModelMock->_set('identifier', 'php');
		$dependencyStorage = new \SplObjectStorage();
		$dependencyStorage->attach($dependencyModelMock);
		$dependencyUtility = $this->getAccessibleMock('TYPO3\\CMS\\Extensionmanager\\Utility\\DependencyUtility', array('checkPhpDependency', 'checkTypo3Dependency'));
		$dependencyUtility->expects($this->atLeastOnce())->method('checkPhpDependency');
		$dependencyUtility->_call('checkDependencies', $dependencyStorage);
	}

	/**
	 * @test
	 * @return void
	 */
	public function checkDependenciesCallsMethodToCheckTypo3Dependencies() {
		$dependencyModelMock = $this->getAccessibleMock('TYPO3\\CMS\\Extensionmanager\\Domain\\Model\\Dependency', array('getHighestVersion', 'getLowestVersion'));
		$dependencyModelMock->_set('identifier', 'TyPo3');
		$dependencyStorage = new \SplObjectStorage();
		$dependencyStorage->attach($dependencyModelMock);
		$dependencyUtility = $this->getAccessibleMock('TYPO3\\CMS\\Extensionmanager\\Utility\\DependencyUtility', array('checkPhpDependency', 'checkTypo3Dependency'));
		$dependencyUtility->expects($this->atLeastOnce())->method('checkTypo3Dependency');
		$dependencyUtility->_call('checkDependencies', $dependencyStorage);
	}

	/**
	 * @test
	 * @return void
	 */
	public function isVersionCompatibleReturnsTrueForCompatibleVersion() {
		$dependencyModelMock = $this->getAccessibleMock('TYPO3\\CMS\\Extensionmanager\\Domain\\Model\\Dependency', array('getHighestVersion', 'getLowestVersion'));
		$dependencyModelMock->expects($this->atLeastOnce())->method('getHighestVersion')->will($this->returnValue('15.0.0'));
		$dependencyModelMock->expects($this->atLeastOnce())->method('getLowestVersion')->will($this->returnValue('1.0.0'));
		$version = '3.3.3';
		$dependencyUtility = $this->getAccessibleMock('TYPO3\\CMS\\Extensionmanager\\Utility\\DependencyUtility', array('dummy'));
		$this->assertTrue($dependencyUtility->_call('isVersionCompatible', $version, $dependencyModelMock));
	}

	/**
	 * @test
	 * @return void
	 */
	public function isVersionCompatibleReturnsFalseForIncompatibleVersion() {
		$dependencyModelMock = $this->getAccessibleMock('TYPO3\\CMS\\Extensionmanager\\Domain\\Model\\Dependency', array('getHighestVersion', 'getLowestVersion'));
		$dependencyModelMock->expects($this->atLeastOnce())->method('getHighestVersion')->will($this->returnValue('1.0.1'));
		$dependencyModelMock->expects($this->atLeastOnce())->method('getLowestVersion')->will($this->returnValue('1.0.0'));
		$version = '3.3.3';
		$dependencyUtility = $this->getAccessibleMock('TYPO3\\CMS\\Extensionmanager\\Utility\\DependencyUtility', array('dummy'));
		$this->assertFalse($dependencyUtility->_call('isVersionCompatible', $version, $dependencyModelMock));
	}

	/**
	 * @test
	 * @return void
	 */
	public function isDependentExtensionAvailableReturnsTrueIfExtensionIsAvailable() {
		$availableExtensions = array(
			'dummy' => array(),
			'foo' => array(),
			'bar' => array()
		);
		$listUtilityMock = $this->getAccessibleMock('TYPO3\\CMS\\Extensionmanager\\Utility\\ListUtility', array('getAvailableExtensions'));
		$listUtilityMock->expects($this->atLeastOnce())->method('getAvailableExtensions')->will($this->returnValue($availableExtensions));
		$dependencyUtility = $this->getAccessibleMock('TYPO3\\CMS\\Extensionmanager\\Utility\\DependencyUtility', array('dummy'));
		$dependencyUtility->_set('listUtility', $listUtilityMock);
		$this->assertTrue($dependencyUtility->_call('isDependentExtensionAvailable', 'dummy'));
	}

	/**
	 * @test
	 * @return void
	 */
	public function isDependentExtensionAvailableReturnsFalseIfExtensionIsNotAvailable() {
		$availableExtensions = array(
			'dummy' => array(),
			'foo' => array(),
			'bar' => array()
		);
		$listUtilityMock = $this->getAccessibleMock('TYPO3\\CMS\\Extensionmanager\\Utility\\ListUtility', array('getAvailableExtensions'));
		$listUtilityMock->expects($this->atLeastOnce())->method('getAvailableExtensions')->will($this->returnValue($availableExtensions));
		$dependencyUtility = $this->getAccessibleMock('TYPO3\\CMS\\Extensionmanager\\Utility\\DependencyUtility', array('dummy'));
		$dependencyUtility->_set('listUtility', $listUtilityMock);
		$this->assertFalse($dependencyUtility->_call('isDependentExtensionAvailable', '42'));
	}

	/**
	 * @test
	 * @return void
	 */
	public function isAvailableVersionCompatibleCallsIsVersionCompatibleWithExtensionVersion() {
		$emConfUtility = $this->getAccessibleMock('TYPO3\\CMS\\Extensionmanager\\Utility\\EmConfUtility', array('includeEmConf'));
		$emConfUtility->expects($this->once())->method('includeEmConf')->will($this->returnValue(array(
			'key' => 'dummy',
			'version' => '1.0.0'
		)));
		$dependencyUtility = $this->getAccessibleMock('TYPO3\\CMS\\Extensionmanager\\Utility\\DependencyUtility', array('setAvailableExtensions', 'isVersionCompatible'));
		$dependencyModelMock = $this->getAccessibleMock('TYPO3\\CMS\\Extensionmanager\\Domain\\Model\\Dependency', array('getIdentifier'));
		$dependencyModelMock->expects($this->once())->method('getIdentifier')->will($this->returnValue('dummy'));
		$dependencyUtility->_set('emConfUtility', $emConfUtility);
		$dependencyUtility->_set('availableExtensions', array(
			'dummy' => array(
				'foo' => '42'
			)
		));
		$dependencyUtility->expects($this->once())->method('setAvailableExtensions');
		$dependencyUtility->expects($this->once())->method('isVersionCompatible')->with('1.0.0', $this->anything());
		$dependencyUtility->_call('isAvailableVersionCompatible', $dependencyModelMock);
	}

	/**
	 * @test
	 * @return void
	 */
	public function isExtensionDownloadableFromTerReturnsTrueIfOneVersionExists() {
		$extensionRepositoryMock = $this->getAccessibleMock('TYPO3\\CMS\\Extensionmanager\\Domain\\Repository\\ExtensionRepository', array('countByExtensionKey'));
		$extensionRepositoryMock->expects($this->once())->method('countByExtensionKey')->with('test123')->will($this->returnValue(1));
		$dependencyUtility = $this->getAccessibleMock('TYPO3\\CMS\\Extensionmanager\\Utility\\DependencyUtility', array('dummy'));
		$dependencyUtility->_set('extensionRepository', $extensionRepositoryMock);
		$count = $dependencyUtility->_call('isExtensionDownloadableFromTer', 'test123');
		$this->assertTrue($count);
	}

	/**
	 * @test
	 * @return void
	 */
	public function isExtensionDownloadableFromTerReturnsFalseIfNoVersionExists() {
		$extensionRepositoryMock = $this->getAccessibleMock('TYPO3\\CMS\\Extensionmanager\\Domain\\Repository\\ExtensionRepository', array('countByExtensionKey'));
		$extensionRepositoryMock->expects($this->once())->method('countByExtensionKey')->with('test123')->will($this->returnValue(0));
		$dependencyUtility = $this->getAccessibleMock('TYPO3\\CMS\\Extensionmanager\\Utility\\DependencyUtility', array('dummy'));
		$dependencyUtility->_set('extensionRepository', $extensionRepositoryMock);
		$count = $dependencyUtility->_call('isExtensionDownloadableFromTer', 'test123');
		$this->assertFalse($count);
	}

	/**
	 * @test
	 * @return void
	 */
	public function isDownloadableVersionCompatibleReturnsTrueIfCompatibleVersionExists() {
		$dependencyModelMock = $this->getAccessibleMock('TYPO3\\CMS\\Extensionmanager\\Domain\\Model\\Dependency', array('getIdentifier', 'getHighestVersion', 'getLowestVersion'));
		$dependencyModelMock->expects($this->once())->method('getIdentifier')->will($this->returnValue('dummy'));
		$dependencyModelMock->expects($this->once())->method('getHighestVersion')->will($this->returnValue('10.0.0'));
		$dependencyModelMock->expects($this->once())->method('getLowestVersion')->will($this->returnValue('1.0.0'));
		$extensionRepositoryMock = $this->getAccessibleMock('TYPO3\\CMS\\Extensionmanager\\Domain\\Repository\\ExtensionRepository', array('countByVersionRangeAndExtensionKey'));
		$extensionRepositoryMock->expects($this->once())->method('countByVersionRangeAndExtensionKey')->with('dummy', 1000000, 10000000)->will($this->returnValue(array('1234', '5678')));
		$dependencyUtility = $this->getAccessibleMock('TYPO3\\CMS\\Extensionmanager\\Utility\\DependencyUtility', array('dummy'));
		$dependencyUtility->_set('extensionRepository', $extensionRepositoryMock);
		$count = $dependencyUtility->_call('isDownloadableVersionCompatible', $dependencyModelMock);
		$this->assertTrue($count);
	}

	/**
	 * @test
	 * @return void
	 */
	public function isDownloadableVersionCompatibleReturnsFalseIfIncompatibleVersionExists() {
		$dependencyModelMock = $this->getAccessibleMock('TYPO3\\CMS\\Extensionmanager\\Domain\\Model\\Dependency', array('getIdentifier'));
		$dependencyModelMock->expects($this->once())->method('getIdentifier')->will($this->returnValue('dummy'));
		$extensionRepositoryMock = $this->getAccessibleMock('TYPO3\\CMS\\Extensionmanager\\Domain\\Repository\\ExtensionRepository', array('countByVersionRangeAndExtensionKey'));
		$extensionRepositoryMock->expects($this->once())->method('countByVersionRangeAndExtensionKey')->with('dummy', 1000000, 2000000)->will($this->returnValue(array()));
		$dependencyUtility = $this->getAccessibleMock('TYPO3\\CMS\\Extensionmanager\\Utility\\DependencyUtility', array('getLowestAndHighestIntegerVersions'));
		$dependencyUtility->_set('extensionRepository', $extensionRepositoryMock);
		$dependencyUtility->expects($this->once())->method('getLowestAndHighestIntegerVersions')->will($this->returnValue(array(
			'lowestIntegerVersion' => 1000000,
			'highestIntegerVersion' => 2000000
		)));
		$count = $dependencyUtility->_call('isDownloadableVersionCompatible', $dependencyModelMock);
		$this->assertFalse($count);
	}

	/**
	 * @test
	 * @return void
	 */
	public function getLowestAndHighestIntegerVersionsReturnsArrayWithVersions() {
		$dependencyModelMock = $this->getAccessibleMock('TYPO3\\CMS\\Extensionmanager\\Domain\\Model\\Dependency', array('getHighestVersion', 'getLowestVersion'));
		$dependencyModelMock->expects($this->once())->method('getHighestVersion')->will($this->returnValue('2.0.0'));
		$dependencyModelMock->expects($this->once())->method('getLowestVersion')->will($this->returnValue('1.0.0'));
		$dependencyUtility = $this->getAccessibleMock('TYPO3\\CMS\\Extensionmanager\\Utility\\DependencyUtility', array('dummy'));
		$versions = $dependencyUtility->_call('getLowestAndHighestIntegerVersions', $dependencyModelMock);
		$this->assertEquals(array(
			'lowestIntegerVersion' => 1000000,
			'highestIntegerVersion' => 2000000
		), $versions);
	}

	/**
	 * @test
	 * @return void
	 */
	public function getLatestCompatibleExtensionByIntegerVersionDependencyWillReturnExtensionModelOfLatestExtension() {
		$extension1 = new \TYPO3\CMS\Extensionmanager\Domain\Model\Extension();
		$extension1->setExtensionKey('foo');
		$extension1->setVersion('1.0.0');
		$extension2 = new \TYPO3\CMS\Extensionmanager\Domain\Model\Extension();
		$extension2->setExtensionKey('bar');
		$extension2->setVersion('1.0.42');
		$className = uniqid('objectStorage');
		eval('class ' . $className . ' {' . 'public $extensions = array();' . 'public function getFirst() {' . '  return $this->extensions[0];' . '}' . '}');
		$myStorage = new $className();
		$myStorage->extensions[] = $extension1;
		$myStorage->extensions[] = $extension2;
		$dependencyModelMock = $this->getAccessibleMock('TYPO3\\CMS\\Extensionmanager\\Domain\\Model\\Dependency', array('getIdentifier'));
		$dependencyModelMock->expects($this->once())->method('getIdentifier')->will($this->returnValue('foobar'));
		$dependencyUtility = $this->getAccessibleMock('TYPO3\\CMS\\Extensionmanager\\Utility\\DependencyUtility', array('getLowestAndHighestIntegerVersions'));
		$dependencyUtility->expects($this->once())->method('getLowestAndHighestIntegerVersions')->will($this->returnValue(array(
			'lowestIntegerVersion' => 1000000,
			'highestIntegerVersion' => 2000000
		)));
		$extensionRepositoryMock = $this->getAccessibleMock('TYPO3\\CMS\\Extensionmanager\\Domain\\Repository\\ExtensionRepository', array('findByVersionRangeAndExtensionKeyOrderedByVersion'));
		$extensionRepositoryMock->expects($this->once())->method('findByVersionRangeAndExtensionKeyOrderedByVersion')->with('foobar', 1000000, 2000000)->will($this->returnValue($myStorage));
		$dependencyUtility->_set('extensionRepository', $extensionRepositoryMock);
		$extension = $dependencyUtility->_call('getLatestCompatibleExtensionByIntegerVersionDependency', $dependencyModelMock);
		$this->assertTrue($extension instanceof \TYPO3\CMS\Extensionmanager\Domain\Model\Extension);
		$this->assertEquals('foo', $extension->getExtensionKey());
	}

}


?>