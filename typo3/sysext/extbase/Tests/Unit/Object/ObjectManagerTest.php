<?php
namespace TYPO3\CMS\Extbase\Tests\Unit\Object;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2012 Extbase Team (http://forge.typo3.org/projects/typo3v4-mvc)
 *  Extbase is a backport of TYPO3 Flow. All credits go to the TYPO3 Flow team.
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
/**
 * Testcase for class ObjectManager.
 */
class ObjectManagerTest extends \TYPO3\CMS\Extbase\Tests\Unit\BaseTestCase {

	/**
	 * @var \TYPO3\CMS\Core\SingletonInterface
	 */
	protected $singleton;

	/**
	 * @var object
	 */
	protected $prototype;

	/**
	 * @var \TYPO3\CMS\Extbase\Object\ObjectManager|\PHPUnit_Framework_MockObject_MockObject|\Tx_Phpunit_Interface_AccessibleObject
	 */
	protected $objectManager;

	public function setUp() {
		$classNameSingleton = uniqid('Singleton_');
		eval('class ' . $classNameSingleton . ' implements \TYPO3\CMS\Core\SingletonInterface {}');
		$this->singleton = new $classNameSingleton;

		$classNamePrototype = uniqid('Prototype_');
		eval('class ' . $classNamePrototype . ' {}');
		$this->prototype = new $classNamePrototype;

		$objectContainer = $this->getMock('TYPO3\CMS\Extbase\Object\ObjectContainer', array('getInstance'));
		$objectContainer->expects($this->any())->method('getInstance')->with(
			$this->logicalOr(
				$this->equalTo($classNameSingleton),
				$this->equalTo($classNamePrototype),
				$this->equalTo('DateTime')
			), array())->will(
			$this->returnCallback(function ($param) {
				if (substr($param, 0, strlen('Singleton_')) == 'Singleton_') {
					return $this->singleton;
				}
				if (substr($param, 0, strlen('Prototype_')) == 'Prototype_') {
					return $this->prototype;
				}
				if ($param == 'DateTime') {
					return \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('DateTime');
				}
				return NULL;
			})
		);

		$this->objectManager = $this->getAccessibleMock('TYPO3\CMS\Extbase\Object\ObjectManager', array('dummy'), array(), '', FALSE);
		$this->objectManager->_set('objectContainer', $objectContainer);
	}

	/**
	 * @test
	 * @author Alexander Schnitzler <alex.schnitzler@typovision.de>
	 */
	public function isRegisteredReturnsTrueIfClassCanBeFound() {
		$this->assertTrue($this->objectManager->isRegistered('TYPO3\CMS\Extbase\Object\ObjectManager'));
	}

	/**
	 * @test
	 * @author Alexander Schnitzler <alex.schnitzler@typovision.de>
	 */
	public function createReturnsInstanceIfObjectIsPrototype() {
		$this->assertSame($this->prototype, $this->objectManager->create(get_class($this->prototype)));
	}

	/**
	 * @test
	 * @expectedException \TYPO3\CMS\Extbase\Object\Exception\WrongScopeException
	 * @author Alexander Schnitzler <alex.schnitzler@typovision.de>
	 */
	public function createThrowsWrongScopeExceptionIfObjectIsNotOfTypeSingleton() {
		$this->objectManager->create(get_class($this->singleton));
	}

	/**
	 * @test
	 * @author Alexander Schnitzler <alex.schnitzler@typovision.de>
	 */
	public function createReturnsInstanceOfDateTimeIfObjectIsDateTime() {
		$this->assertSame('DateTime', get_class($this->objectManager->create('DateTime')));
	}

	/**
	 * @test
	 * @author Alexander Schnitzler <alex.schnitzler@typovision.de>
	 */
	public function getReturnsInstanceIfObjectIsPrototype() {
		$this->assertSame($this->prototype, $this->objectManager->get(get_class($this->prototype)));
	}

	/**
	 * @test
	 * @author Alexander Schnitzler <alex.schnitzler@typovision.de>
	 */
	public function getReturnsInstanceIfObjectIsSingleton() {
		$this->assertSame($this->singleton, $this->objectManager->get(get_class($this->singleton)));
	}
}

?>