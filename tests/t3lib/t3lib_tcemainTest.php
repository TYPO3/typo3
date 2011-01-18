<?php
/***************************************************************
* Copyright notice
*
* (c) 2009-2011 Oliver Klee (typo3-coding@oliverklee.de)
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
 * Testcase for the t3lib_TCEmain class in the TYPO3 core.
 *
 * @package TYPO3
 * @subpackage t3lib
 *
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class t3lib_tcemainTest extends tx_phpunit_testcase {

	/**
	 * Enable backup of global and system variables
	 *
	 * @var boolean
	 */
	protected $backupGlobals = TRUE;

	/**
	 * Exclude TYPO3_DB from backup/ restore of $GLOBALS
	 * because resource types cannot be handled during serializing
	 *
	 * @var array
	 */
	protected $backupGlobalsBlacklist = array('TYPO3_DB');

	/**
	 * @var t3lib_TCEmain
	 */
	private $fixture;

	/**
	 * @var t3lib_beUserAuth a mock logged-in back-end user
	 */
	private $backEndUser;

	public function setUp() {
		$this->backEndUser = $this->getMock('t3lib_beUserAuth');

		$this->fixture = new t3lib_TCEmain();
		$this->fixture->start(array(), '', $this->backEndUser);
	}

	public function tearDown() {
		unset(
			$this->fixture->BE_USER, $this->fixture, $this->backEndUser
		);
	}


	//////////////////////////////////////
	// Tests for the basic functionality
	//////////////////////////////////////

	/**
	 * @test
	 */
	public function fixtureCanBeCreated() {
		$this->assertTrue(
			$this->fixture instanceof t3lib_TCEmain
		);
	}


	//////////////////////////////////////////
	// Test concerning checkModifyAccessList
	//////////////////////////////////////////

	/**
	 * @test
	 */
	public function adminIsAllowedToModifyNonAdminTable() {
		$this->fixture->admin = true;

		$this->assertTrue(
			$this->fixture->checkModifyAccessList('tt_content')
		);
	}

	/**
	 * @test
	 */
	public function nonAdminIsNorAllowedToModifyNonAdminTable() {
		$this->fixture->admin = false;

		$this->assertFalse(
			$this->fixture->checkModifyAccessList('tt_content')
		);
	}

	/**
	 * @test
	 */
	public function nonAdminWithTableModifyAccessIsAllowedToModifyNonAdminTable() {
		$this->fixture->admin = false;
		$this->backEndUser->groupData['tables_modify'] = 'tt_content';

		$this->assertTrue(
			$this->fixture->checkModifyAccessList('tt_content')
		);
	}

	/**
	 * @test
	 */
	public function adminIsAllowedToModifyAdminTable() {
		$this->fixture->admin = true;

		$this->assertTrue(
			$this->fixture->checkModifyAccessList('be_users')
		);
	}

	/**
	 * @test
	 */
	public function nonAdminIsNotAllowedToModifyAdminTable() {
		$this->fixture->admin = false;

		$this->assertFalse(
			$this->fixture->checkModifyAccessList('be_users')
		);
	}

	/**
	 * @test
	 */
	public function nonAdminWithTableModifyAccessIsNotAllowedToModifyAdminTable() {
		$this->fixture->admin = false;
		$this->backEndUser->groupData['tables_modify'] = 'be_users';

		$this->assertFalse(
			$this->fixture->checkModifyAccessList('be_users')
		);
	}

	/**
	 * @test
	 */
	public function evalCheckValueDouble2() {
		$testData = array (
						'-0,5' => '-0.50',
						'1000' => '1000.00',
						'1000,10' => '1000.10',
						'1000,0' => '1000.00',
						'600.000.000,00' => '600000000.00',
						'60aaa00' => '6000.00',
						);
		foreach ($testData as $value => $expectedReturnValue){
			$returnValue = $this->fixture->checkValue_input_Eval($value, array('double2'), '');
			$this->assertSame(
			$returnValue['value'],
			$expectedReturnValue
			);
		}
	}


	///////////////////////////////////////////
	// Tests concerning checkModifyAccessList
	///////////////////////////////////////////

	/**
	 * Tests whether a wrong interface on the 'checkModifyAccessList' hook throws an exception.
	 * @test
	 * @expectedException UnexpectedValueException
	 * @see t3lib_TCEmain::checkModifyAccessList()
	 */
	public function doesCheckModifyAccessListThrowExceptionOnWrongHookInterface() {
		$hookClass = uniqid('tx_coretest');
		eval('class ' . $hookClass . ' {}');

		$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['checkModifyAccessList'][] = $hookClass;

		$this->fixture->checkModifyAccessList('tt_content');
	}

	/**
	 * Tests whether the 'checkModifyAccessList' hook is called correctly.
	 * @test
	 * @see t3lib_TCEmain::checkModifyAccessList()
	 */
	public function doesCheckModifyAccessListHookGetsCalled() {
		$hookClass = uniqid('tx_coretest');
		$hookMock = $this->getMock(
			't3lib_TCEmain_checkModifyAccessListHook',
			array('checkModifyAccessList'),
			array(),
			$hookClass
		);
		$hookMock->expects($this->once())->method('checkModifyAccessList');

		$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['checkModifyAccessList'][] = $hookClass;
		$GLOBALS['T3_VAR']['getUserObj'][$hookClass] = $hookMock;

		$this->fixture->checkModifyAccessList('tt_content');
	}

	/**
	 * Tests whether the 'checkModifyAccessList' hook modifies the $accessAllowed variable.
	 * @test
	 * @see t3lib_TCEmain::checkModifyAccessList()
	 */
	public function doesCheckModifyAccessListHookModifyAccessAllowed() {
		$hookClass = uniqid('tx_coretest');
		eval('
			class ' . $hookClass . ' implements t3lib_TCEmain_checkModifyAccessListHook {
				public function checkModifyAccessList(&$accessAllowed, $table, t3lib_TCEmain $parent) { $accessAllowed = true; }
			}
		');

		$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['checkModifyAccessList'][] = $hookClass;

		$this->assertTrue($this->fixture->checkModifyAccessList('tt_content'));
	}
}
?>