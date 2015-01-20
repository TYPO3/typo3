<?php
namespace TYPO3\CMS\Frontend\Tests\Unit\View;

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

/**
 * Test case
 */
class AdminPanelViewTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * Set up
	 */
	public function setUp() {
		$GLOBALS['LANG'] = $this->getMock('TYPO3\\CMS\\Lang\\LanguageService', array(), array(), '', FALSE);
	}

	/**
	 * @test
	 */
	public function extGetFeAdminValueReturnsTimestamp() {
		$strTime = '2013-01-01 01:00:00';
		$timestamp = strtotime($strTime);

		$backendUser = $this->getMock('TYPO3\\CMS\\Core\\Authentication\\BackendUserAuthentication');
		$backendUser->uc['TSFE_adminConfig']['preview_simulateDate'] = $timestamp;
		unset($backendUser->extAdminConfig['override.']['preview.']);
		unset($backendUser->extAdminConfig['override.']['preview']);
		$GLOBALS['BE_USER'] = $backendUser;

		$adminPanelMock = $this->getMock('TYPO3\\CMS\\Frontend\\View\\AdminPanelView', array('isAdminModuleEnabled','isAdminModuleOpen'), array(), '', FALSE);
		$adminPanelMock->expects($this->any())->method('isAdminModuleEnabled')->will($this->returnValue(TRUE));
		$adminPanelMock->expects($this->any())->method('isAdminModuleOpen')->will($this->returnValue(TRUE));

		$timestampReturned = $adminPanelMock->extGetFeAdminValue('preview', 'simulateDate');
		$this->assertEquals($timestamp, $timestampReturned);
	}

	/////////////////////////////////////////////
	// Test concerning extendAdminPanel hook
	/////////////////////////////////////////////

	/**
	 * @test
	 * @expectedException \UnexpectedValueException
	 */
	public function extendAdminPanelHookThrowsExceptionIfHookClassDoesNotImplementInterface() {
		$hookClass = $this->getUniqueId('tx_coretest');
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
		$hookClass = $this->getUniqueId('tx_coretest');
		$hookMock = $this->getMock('TYPO3\\CMS\\Frontend\\View\\AdminPanelViewHookInterface', array(), array(), $hookClass);
		$GLOBALS['T3_VAR']['getUserObj'][$hookClass] = $hookMock;
		$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_adminpanel.php']['extendAdminPanel'][] = $hookClass;
		/** @var $adminPanelMock \PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Frontend\View\AdminPanelView */
		$adminPanelMock = $this->getMock('TYPO3\\CMS\\Frontend\\View\\AdminPanelView', array('extGetLL'), array(), '', FALSE);
		$hookMock->expects($this->once())->method('extendAdminPanel')->with($this->isType('string'), $this->isInstanceOf('TYPO3\\CMS\\Frontend\\View\\AdminPanelView'));
		$adminPanelMock->display();
	}

}
