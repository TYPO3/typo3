<?php
namespace TYPO3\CMS\Frontend\Tests\Unit\View;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011-2013 Christian Kuhn <lolli@schwarzbu.ch>
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
 * Testcase for TYPO3\CMS\Frontend\View\AdminPanelView
 *
 * @author Christian Kuhn <lolli@schwarzbu.ch>
 */
class AdminPanelViewTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/////////////////////////////////////////////
	// Test concerning extendAdminPanel hook
	/////////////////////////////////////////////

	/**
	 * @test
	 * @expectedException \UnexpectedValueException
	 */
	public function extendAdminPanelHookThrowsExceptionIfHookClassDoesNotImplementInterface() {
		$hookClass = uniqid('tx_coretest');
		eval('class ' . $hookClass . ' {}');
		$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_adminpanel.php']['extendAdminPanel'][] = $hookClass;
		/** @var $adminPanelMock \PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Frontend\View\AdminPanelView */
		$adminPanelMock = $this->getMock('TYPO3\\CMS\\Frontend\\View\\AdminPanelView', array('dummy'), array(), '', FALSE);
		$adminPanelMock->display();
	}

	/**
	 * @test
	 */
	public function extendAdminPanelHookCallsExtendAdminPanelMethodOfHook() {
		$hookClass = uniqid('tx_coretest');
		$hookMock = $this->getMock('TYPO3\\CMS\\Frontend\\View\\AdminPanelViewHookInterface', array(), array(), $hookClass);
		$GLOBALS['T3_VAR']['getUserObj'][$hookClass] = $hookMock;
		$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_adminpanel.php']['extendAdminPanel'][] = $hookClass;
		/** @var $adminPanelMock \PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Frontend\View\AdminPanelView */
		$adminPanelMock = $this->getMock('TYPO3\\CMS\\Frontend\\View\\AdminPanelView', array('dummy'), array(), '', FALSE);
		$hookMock->expects($this->once())->method('extendAdminPanel')->with($this->isType('string'), $this->isInstanceOf('TYPO3\\CMS\\Frontend\\View\\AdminPanelView'));
		$adminPanelMock->display();
	}

}

?>