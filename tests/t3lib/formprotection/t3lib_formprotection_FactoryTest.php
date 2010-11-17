<?php
/***************************************************************
* Copyright notice
*
* (c) 2010 Oliver Klee (typo3-coding@oliverklee.de)
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

require_once('fixtures/class.t3lib_formprotection_testing.php');

/**
 * Testcase for the t3lib_formprotection_Factory class.
 *
 * $Id$
 *
 * @package TYPO3
 * @subpackage t3lib
 *
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 * @author Ernesto Baschny <ernst@cron-it.de>
 */
class t3lib_formprotection_FactoryTest extends tx_phpunit_testcase {
	public function setUp() {
	}

	public function tearDown() {
		t3lib_formprotection_Factory::purgeInstances();
	}


	/////////////////////////
	// Tests concerning get
	/////////////////////////

	/**
	 * @test
	 *
	 * @expectedException InvalidArgumentException
	 */
	public function getForInexistentClassThrowsException() {
		t3lib_formprotection_Factory::get('noSuchClass');
	}

	/**
	 * @test
	 *
	 * @expectedException InvalidArgumentException
	 */
	public function getForClassThatIsNoFormProtectionSubclassThrowsException() {
		t3lib_formprotection_Factory::get('t3lib_formprotection_FactoryTest');
	}

	/**
	 * @test
	 */
	public function getForTypeBackEndWithExistingBackEndReturnsBackEndFormProtection() {
		$this->assertTrue(
			t3lib_formprotection_Factory::get(
				't3lib_formprotection_BackendFormProtection'
			) instanceof t3lib_formprotection_BackendFormProtection
		);
	}

	/**
	 * @test
	 */
	public function getForTypeBackEndCalledTwoTimesReturnsTheSameInstance() {
		$this->assertSame(
			t3lib_formprotection_Factory::get(
				't3lib_formprotection_BackendFormProtection'
			),
			t3lib_formprotection_Factory::get(
				't3lib_formprotection_BackendFormProtection'
			)
		);
	}

	/**
	 * @test
	 */
	public function getForTypeInstallToolReturnsInstallToolFormProtection() {
		$this->assertTrue(
			t3lib_formprotection_Factory::get(
				't3lib_formprotection_InstallToolFormProtection'
			) instanceof t3lib_formprotection_InstallToolFormProtection
		);
	}

	/**
	 * @test
	 */
	public function getForTypeInstallToolCalledTwoTimesReturnsTheSameInstance() {
		$this->assertSame(
			t3lib_formprotection_Factory::get(
				't3lib_formprotection_InstallToolFormProtection'
			),
			t3lib_formprotection_Factory::get(
				't3lib_formprotection_InstallToolFormProtection'
			)
		);
	}

	/**
	 * @test
	 */
	public function getForTypesInstallToolAndBackEndReturnsDifferentInstances() {
		$this->assertNotSame(
			t3lib_formprotection_Factory::get(
				't3lib_formprotection_InstallToolFormProtection'
			),
			t3lib_formprotection_Factory::get(
				't3lib_formprotection_BackendFormProtection'
			)
		);
	}


	/////////////////////////
	// Tests concerning set
	/////////////////////////

	/**
	 * @test
	 */
	public function setSetsInstanceForType() {
		$instance = new t3lib_formProtection_Testing();
		t3lib_formprotection_Factory::set(
			't3lib_formprotection_BackendFormProtection', $instance
		);

		$this->assertSame(
			$instance,
			t3lib_formprotection_Factory::get(
				't3lib_formprotection_BackendFormProtection'
			)
		);
	}

	/**
	 * @test
	 */
	public function setNotSetsInstanceForOtherType() {
		$instance = new t3lib_formProtection_Testing();
		t3lib_formprotection_Factory::set(
			't3lib_formprotection_BackendFormProtection', $instance
		);

		$this->assertNotSame(
			$instance,
			t3lib_formprotection_Factory::get(
				't3lib_formprotection_InstallToolFormProtection'
			)
		);
	}
}
?>