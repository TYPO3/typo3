<?php
namespace TYPO3\CMS\Extbase\Tests\Unit\Core;

/**
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
 * Test case
 */
class BootstrapTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var array A backup of registered singleton instances
	 */
	protected $singletonInstances = array();

	/**
	 * Sets up this testcase
	 */
	public function setUp() {
		$this->singletonInstances = \TYPO3\CMS\Core\Utility\GeneralUtility::getSingletonInstances();
	}

	public function tearDown() {
		\TYPO3\CMS\Core\Utility\GeneralUtility::purgeInstances();
		\TYPO3\CMS\Core\Utility\GeneralUtility::resetSingletonInstances($this->singletonInstances);
		parent::tearDown();
	}

	/**
	 * @test
	 */
	public function configureObjectManagerRespectsOverridingOfAlternativeObjectRegistrationViaPluginConfiguration() {
		/** @var $objectContainer \TYPO3\CMS\Extbase\Object\Container\Container|\PHPUnit_Framework_MockObject_MockObject */
		$objectContainer = $this->getMock('TYPO3\CMS\Extbase\Object\Container\Container', array('registerImplementation'));
		$objectContainer->expects($this->once())->method('registerImplementation')->with('TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface', 'TYPO3\CMS\Extbase\Persistence\Reddis\PersistenceManager');
		\TYPO3\CMS\Core\Utility\GeneralUtility::setSingletonInstance('TYPO3\CMS\Extbase\Object\Container\Container', $objectContainer);

		$frameworkSettings['objects'] = array(
			'TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface.' => array(
				'className' => 'TYPO3\CMS\Extbase\Persistence\Reddis\PersistenceManager'
			)
		);

		/** @var $configurationManagerMock \TYPO3\CMS\Extbase\Configuration\ConfigurationManager|\PHPUnit_Framework_MockObject_MockObject|\Tx_Phpunit_Interface_AccessibleObject */
		$configurationManagerMock = $this->getAccessibleMock('TYPO3\\CMS\\Extbase\\Configuration\\ConfigurationManager', array('getConfiguration'));
		$configurationManagerMock->expects($this->any())->method('getConfiguration')->with('Framework')->will($this->returnValue($frameworkSettings));

		/** @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface|\PHPUnit_Framework_MockObject_MockObject  $objectManager */
		$objectManager = $this->getMock('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');

		/** @var $bootstrapMock \TYPO3\CMS\Extbase\Core\Bootstrap|\PHPUnit_Framework_MockObject_MockObject|\Tx_Phpunit_Interface_AccessibleObject */
		$bootstrapMock = $this->getAccessibleMock('TYPO3\CMS\Extbase\Core\Bootstrap', array('inject'));
		$bootstrapMock->_set('objectManager', $objectManager);
		$bootstrapMock->_set('configurationManager', $configurationManagerMock);
		$bootstrapMock->configureObjectManager();
	}
}
