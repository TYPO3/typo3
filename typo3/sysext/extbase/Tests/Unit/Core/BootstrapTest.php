<?php
namespace TYPO3\CMS\Extbase\Tests\Unit\Core;

/***************************************************************
 *  Copyright notice
 *
 *  This class is a backport of the corresponding class of TYPO3 Flow.
 *  All credits go to the TYPO3 Flow team.
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
class BootstrapTest extends \TYPO3\CMS\Extbase\Tests\Unit\BaseTestCase {

	/**
	 * @var \TYPO3\CMS\Extbase\Core\Bootstrap|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface
	 */
	protected $bootstrapMock;

	/**
	 * @var \TYPO3\CMS\Extbase\Configuration\ConfigurationManager
	 */
	protected $configurationManager;

	/**
	 * @var \TYPO3\CMS\Extbase\Object\ObjectManager
	 */
	protected $objectManager;

	/**
	 * @var array
	 */
	protected $frameworkSettings = array();

	/**
	 * Sets up this testcase
	 */
	public function setUp() {
		$this->objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');

		$this->configurationManager = $this->getAccessibleMock('TYPO3\CMS\Extbase\Configuration\ConfigurationManager', array('getConfiguration'));
		$this->bootstrapMock = $this->getAccessibleMock('TYPO3\CMS\Extbase\Core\Bootstrap', array('inject'));
		$this->bootstrapMock->_set('objectManager', $this->objectManager);
	}

	/**
	 * @test
	 * @author Alexander Schnitzler <alex.schnitzler@typovision.de>
	 */
	public function pluginConfigurationAffectsAlternativeImplementationPropertyOfObjectContainer() {
		$this->frameworkSettings['objects'] = array(
			'TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface' => array(
				'className' => 'TYPO3\CMS\Extbase\Persistence\Reddis\PersistenceManager'
			)
		);

		$this->configurationManager->expects($this->any())->method('getConfiguration')->with('Framework')->will($this->returnValue($this->frameworkSettings));
		$this->bootstrapMock->_set('configurationManager', $this->configurationManager);
		$this->bootstrapMock->configureObjectManager();

		/** @var $objectContainer \TYPO3\CMS\Extbase\Object\Container\Container */
		$objectContainer = $this->objectManager->get('TYPO3\\CMS\\Extbase\\Object\\Container\\Container');

		$class = new \ReflectionClass(get_class($objectContainer));
		$property = $class->getProperty('alternativeImplementation');
		$property->setAccessible(TRUE);
		$alternativeImplementation = $property->getValue($objectContainer);

		$this->assertEquals(
			'TYPO3\CMS\Extbase\Persistence\Reddis\PersistenceManager',
			$alternativeImplementation['TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface']
		);
	}
}

?>