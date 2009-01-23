<?php
/***************************************************************
* Copyright notice
*
* (c) 2009 Oliver Klee (typo3-coding@oliverklee.de)
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

require_once(PATH_t3lib . 'class.t3lib_beuserauth.php');

require_once(PATH_t3lib . 'class.t3lib_tcemain.php');

/**
 * Testcase for the t3lib_TCEmain class in the TYPO3 core.
 *
 * @package TYPO3
 * @subpackage t3lib
 *
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class t3lib_tcemain_testcase extends tx_phpunit_testcase {
	/**
	 * @var t3lib_TCEmain
	 */
	private $fixture;

	/**
	 * @var t3lib_beUserAuth a simulated logged-in back-end user
	 */
	private $backEndUser;

	public function setUp() {
		$this->backEndUser = $this->createBackEndUser();

		$this->fixture = new t3lib_TCEmain();
		$this->fixture->start(array(), '', $this->backEndUser);
	}

	public function tearDown() {
		unset(
			$this->fixture->BE_USER, $this->fixture, $this->backEndUser
		);
	}


	//////////////////////
	// Utility functions
	//////////////////////

	/**
	 * Creates a back-end user.
	 *
	 * @return t3lib_beUserAuth a back-end user
	 */
	private function createBackEndUser() {
		$user = new t3lib_beUserAuth();
		$user->user = array();

		return $user;
	}


	////////////////////////////////////
	// Tests for the utility functions
	////////////////////////////////////

	public function testCreateBackEndUserCreatesBeUserAuthInstance() {
		$this->assertTrue(
			$this->createBackEndUser() instanceof t3lib_beUserAuth
		);
	}


	//////////////////////////////////////
	// Tests for the basic functionality
	//////////////////////////////////////

	public function testFixtureCanBeCreated() {
		$this->assertTrue(
			$this->fixture instanceof t3lib_TCEmain
		);
	}


	//////////////////////////////////////////
	// Test concerning checkModifyAccessList
	//////////////////////////////////////////

	public function testCheckModifyAccessListForAdminForContentTableReturnsTrue() {
		$this->fixture->admin = true;

		$this->assertTrue(
			$this->fixture->checkModifyAccessList('tt_content')
		);
	}

	public function testCheckModifyAccessListForNonAdminForContentTableReturnsFalse() {
		$this->fixture->admin = false;

		$this->assertFalse(
			$this->fixture->checkModifyAccessList('tt_content')
		);
	}

	public function testCheckModifyAccessListForNonAdminWithTableModifyAccessForContentTableReturnsTrue() {
		$this->fixture->admin = false;
		$this->backEndUser->groupData['tables_modify'] = 'tt_content';

		$this->assertTrue(
			$this->fixture->checkModifyAccessList('tt_content')
		);
	}

	public function testCheckModifyAccessListForAdminForBeUsersTableReturnsTrue() {
		$this->fixture->admin = true;

		$this->assertTrue(
			$this->fixture->checkModifyAccessList('be_users')
		);
	}

	public function testCheckModifyAccessListForNonAdminForBeUsersTableReturnsFalse() {
		$this->fixture->admin = false;

		$this->assertFalse(
			$this->fixture->checkModifyAccessList('be_users')
		);
	}

	public function testCheckModifyAccessListForNonAdminWithTableModifyAccessForBeUsersTableReturnsFalse() {
		$this->fixture->admin = false;
		$this->backEndUser->groupData['tables_modify'] = 'be_users';

		$this->assertFalse(
			$this->fixture->checkModifyAccessList('be_users')
		);
	}
}
?>