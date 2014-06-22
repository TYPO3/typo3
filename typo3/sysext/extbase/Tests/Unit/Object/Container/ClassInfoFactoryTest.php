<?php
namespace TYPO3\CMS\Extbase\Tests\Unit\Object\Container;

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

require_once \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('extbase') . 'Tests/Unit/Object/Container/Fixtures/Testclasses.php';

/**
 * Test case
 */
class ClassInfoFactoryTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\CMS\Extbase\Object\Container\ClassInfoFactory
	 */
	protected $classInfoFactory;

	/**
	 * Set up
	 */
	public function setUp() {
		$this->classInfoFactory = new \TYPO3\CMS\Extbase\Object\Container\ClassInfoFactory();
	}

	/**
	 * @test
	 * @expectedException \TYPO3\CMS\Extbase\Object\Container\Exception\UnknownObjectException
	 */
	public function buildClassInfoFromClassNameThrowsExceptionIfGivenClassNameCantBeReflected() {
		$this->classInfoFactory->buildClassInfoFromClassName('SomeNonExistingClass');
	}

	/**
	 * @test
	 */
	public function buildClassInfoDoesNotIncludeInjectSettingsMethodInListOfInjectMethods() {
		$classInfo = $this->classInfoFactory->buildClassInfoFromClassName('t3lib_object_tests_class_with_injectsettings');
		$this->assertEquals(array('injectFoo' => 't3lib_object_tests_resolveablecyclic1'), $classInfo->getInjectMethods());
	}

	/**
	 * @test
	 */
	public function buildClassInfoDetectsPropertiesToInjectByAnnotation() {
		$classInfo = $this->classInfoFactory->buildClassInfoFromClassName('TYPO3\\CMS\\Extbase\\Tests\\Fixture\\ClassWithInjectProperties');
		$this->assertEquals(array('secondDummyClass' => 'TYPO3\\CMS\\Extbase\\Tests\\Fixture\\SecondDummyClass'), $classInfo->getInjectProperties());
	}

	/**
	 * @test
	 */
	public function buildClassInfoReturnsCustomClassInfoForDateTime() {

		/** @var \PHPUnit_Framework_MockObject_MockObject | \TYPO3\CMS\Extbase\Object\Container\ClassInfoFactory $classInfoFactory */
		$classInfoFactory = $this->getMock('TYPO3\CMS\Extbase\Object\Container\ClassInfoFactory', array('dummy'));
		$classInfoFactory->expects($this->never())->method('getConstructorArguments');

		$classInfo = $classInfoFactory->buildClassInfoFromClassName('DateTime');
		$this->assertEquals(
			new \TYPO3\CMS\Extbase\Object\Container\ClassInfo('DateTime', array(), array(), FALSE, FALSE, array()),
			$classInfo
		);
	}
}
