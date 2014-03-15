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
 * Test for ExtensionModelUtilityTest
 *
 */
class ExtensionModelUtilityTest extends \TYPO3\CMS\Core\Tests\BaseTestCase {
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
		/** @var $dependencyUtility \TYPO3\CMS\Extensionmanager\Utility\ExtensionModelUtility */
		$dependencyUtility = $this->getAccessibleMock('TYPO3\\CMS\\Extensionmanager\\Utility\\ExtensionModelUtility', array('dummy'));
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
		/** @var $dependencyUtility \TYPO3\CMS\Extensionmanager\Utility\ExtensionModelUtility */
		$dependencyUtility = $this->getAccessibleMock('TYPO3\\CMS\\Extensionmanager\\Utility\\ExtensionModelUtility', array('dummy'));
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
	 * @test
	 * @dataProvider convertDependenciesToObjectSetsVersionDataProvider
	 * @param array $dependencies
	 * @param array $returnValue
	 * @return void
	 */
	public function convertDependenciesToObjectSetsVersion(array $dependencies, array $returnValue) {
		$serializedDependencies = serialize($dependencies);
		/** @var $dependencyUtility \TYPO3\CMS\Extensionmanager\Utility\ExtensionModelUtility */
		$dependencyUtility = $this->getAccessibleMock('TYPO3\\CMS\\Extensionmanager\\Utility\\ExtensionModelUtility', array('dummy'));
		$objectManagerMock = $this->getAccessibleMock('TYPO3\\CMS\\Extbase\\Object\\ObjectManager', array('get'));
		$dependencyModelMock = $this->getAccessibleMock('TYPO3\\CMS\\Extensionmanager\\Domain\\Model\\Dependency', array('setHighestVersion', 'setLowestVersion'));
		$objectManagerMock->expects($this->any())->method('get')->will($this->returnValue($dependencyModelMock));
		$dependencyUtility->_set('objectManager', $objectManagerMock);
		$dependencyModelMock->expects($this->atLeastOnce())->method('setLowestVersion')->with($this->identicalTo($returnValue[0]));
		$dependencyModelMock->expects($this->atLeastOnce())->method('setHighestVersion')->with($this->identicalTo($returnValue[1]));
		$dependencyUtility->convertDependenciesToObjects($serializedDependencies);
	}
}
