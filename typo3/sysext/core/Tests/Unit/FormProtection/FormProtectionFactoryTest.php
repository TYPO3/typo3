<?php
namespace TYPO3\CMS\Core\Tests\Unit\FormProtection;

/***************************************************************
 * Copyright notice
 *
 * (c) 2010-2013 Oliver Klee (typo3-coding@oliverklee.de)
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

require_once 'Fixtures/FormProtectionTesting.php';

/**
 * Testcase
 *
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 * @author Ernesto Baschny <ernst@cron-it.de>
 */
class FormprotectionFactoryTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	public function setUp() {

	}

	public function tearDown() {
		\TYPO3\CMS\Core\FormProtection\FormProtectionFactory::purgeInstances();
	}

	/////////////////////////
	// Tests concerning get
	/////////////////////////
	/**
	 * @test
	 * @expectedException \InvalidArgumentException
	 */
	public function getForInexistentClassThrowsException() {
		\TYPO3\CMS\Core\FormProtection\FormProtectionFactory::get('noSuchClass');
	}

	/**
	 * @test
	 * @expectedException \InvalidArgumentException
	 */
	public function getForClassThatIsNoFormProtectionSubclassThrowsException() {
		\TYPO3\CMS\Core\FormProtection\FormProtectionFactory::get('TYPO3\\CMS\\Core\\FormProtection\\FormProtectionFactoryTest');
	}

	/**
	 * @test
	 */
	public function getForTypeBackEndWithExistingBackEndReturnsBackEndFormProtection() {
		$this->assertTrue(\TYPO3\CMS\Core\FormProtection\FormProtectionFactory::get('TYPO3\\CMS\\Core\\FormProtection\\BackendFormProtection') instanceof \TYPO3\CMS\Core\FormProtection\BackendFormProtection);
	}

	/**
	 * @test
	 */
	public function getForTypeBackEndCalledTwoTimesReturnsTheSameInstance() {
		$this->assertSame(\TYPO3\CMS\Core\FormProtection\FormProtectionFactory::get('TYPO3\\CMS\\Core\\FormProtection\\BackendFormProtection'), \TYPO3\CMS\Core\FormProtection\FormProtectionFactory::get('TYPO3\\CMS\\Core\\FormProtection\\BackendFormProtection'));
	}

	/**
	 * @test
	 */
	public function getForTypeInstallToolReturnsInstallToolFormProtection() {
		$this->assertTrue(\TYPO3\CMS\Core\FormProtection\FormProtectionFactory::get('TYPO3\\CMS\\Core\\FormProtection\\InstallToolFormProtection') instanceof \TYPO3\CMS\Core\FormProtection\InstallToolFormProtection);
	}

	/**
	 * @test
	 */
	public function getForTypeInstallToolCalledTwoTimesReturnsTheSameInstance() {
		$this->assertSame(\TYPO3\CMS\Core\FormProtection\FormProtectionFactory::get('TYPO3\\CMS\\Core\\FormProtection\\InstallToolFormProtection'), \TYPO3\CMS\Core\FormProtection\FormProtectionFactory::get('TYPO3\\CMS\\Core\\FormProtection\\InstallToolFormProtection'));
	}

	/**
	 * @test
	 */
	public function getForTypesInstallToolAndBackEndReturnsDifferentInstances() {
		$this->assertNotSame(\TYPO3\CMS\Core\FormProtection\FormProtectionFactory::get('TYPO3\\CMS\\Core\\FormProtection\\InstallToolFormProtection'), \TYPO3\CMS\Core\FormProtection\FormProtectionFactory::get('TYPO3\\CMS\\Core\\FormProtection\\BackendFormProtection'));
	}

	/////////////////////////
	// Tests concerning set
	/////////////////////////
	/**
	 * @test
	 */
	public function setSetsInstanceForType() {
		$instance = new \TYPO3\CMS\Core\Tests\Unit\FormProtection\Fixtures\FormProtectionTesting();
		\TYPO3\CMS\Core\FormProtection\FormProtectionFactory::set('TYPO3\\CMS\\Core\\FormProtection\\BackendFormProtection', $instance);
		$this->assertSame($instance, \TYPO3\CMS\Core\FormProtection\FormProtectionFactory::get('TYPO3\\CMS\\Core\\FormProtection\\BackendFormProtection'));
	}

	/**
	 * @test
	 */
	public function setNotSetsInstanceForOtherType() {
		$instance = new \TYPO3\CMS\Core\Tests\Unit\FormProtection\Fixtures\FormProtectionTesting();
		\TYPO3\CMS\Core\FormProtection\FormProtectionFactory::set('TYPO3\\CMS\\Core\\FormProtection\\BackendFormProtection', $instance);
		$this->assertNotSame($instance, \TYPO3\CMS\Core\FormProtection\FormProtectionFactory::get('TYPO3\\CMS\\Core\\FormProtection\\InstallToolFormProtection'));
	}

}

?>