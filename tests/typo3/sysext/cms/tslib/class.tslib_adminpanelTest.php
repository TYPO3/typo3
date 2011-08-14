<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011 Christian Kuhn <lolli@schwarzbu.ch>
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

/**
 * Testcase for the "tslib_AdminPanel" class in the TYPO3 Core.
 *
 * @package TYPO3
 * @subpackage tslib
 * @author Christian Kuhn <lolli@schwarzbu.ch>
 */
class tslib_AdminPanelTest extends tx_phpunit_testcase {

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

	/////////////////////////////////////////////
	// Test concerning extendAdminPanel hook
	/////////////////////////////////////////////

	/**
	 * @test
	 * @expectedException UnexpectedValueException
	 */
	public function extendAdminPanelHookThrowsExceptionIfHookClassDoesNotImplementInterface() {
		$hookClass = uniqid('tx_coretest');
		eval('class ' . $hookClass . ' {}');

		$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_adminpanel.php']['extendAdminPanel'][] = $hookClass;

		$adminPanelMock = $this->getMock('tslib_AdminPanel', array('dummy'), array(), '', FALSE);
		$adminPanelMock->display();
	}

	/**
	 * @test
	 */
	public function extendAdminPanelHookCallsExtendAdminPanelMethodOfHook() {
		$hookClass = uniqid('tx_coretest');
		$hookMock = $this->getMock(
			'tslib_adminPanelHook',
			array(),
			array(),
			$hookClass
		);
		$GLOBALS['T3_VAR']['getUserObj'][$hookClass] = $hookMock;
		$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_adminpanel.php']['extendAdminPanel'][] = $hookClass;

		$adminPanelMock = $this->getMock('tslib_AdminPanel', array('dummy'), array(), '', FALSE);

		$hookMock->expects($this->once())
			->method('extendAdminPanel')
			->with($this->isType('string'), $this->isInstanceOf('tslib_AdminPanel'));

		$adminPanelMock->display();
	}
}
?>