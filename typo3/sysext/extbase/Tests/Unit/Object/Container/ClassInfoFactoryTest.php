<?php
namespace TYPO3\CMS\Extbase\Tests\Unit\Object\Container;

/***************************************************************
 *  Copyright notice
 *  (c) 2010 Daniel Pötzinger
 *  (c) 2010 Bastian Waidelich <bastian@typo3.org>
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
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
require_once \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('extbase') . 'Tests/Unit/Object/Container/Fixtures/Testclasses.php';

/**
 * Testcase for class t3lib_object_ClassInfoFactory.
 *
 * @author Daniel Pötzinger
 * @author Bastian Waidelich <bastian@typo3.org>
 */
class ClassInfoFactoryTest extends \TYPO3\CMS\Extbase\Tests\Unit\BaseTestCase {

	/**
	 * @var \TYPO3\CMS\Extbase\Object\Container\ClassInfoFactory
	 */
	private $classInfoFactory;

	/**

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
}

?>