<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 Jochen Rau <jochen.rau@typoplanet.de>
*  All rights reserved
*
*  This class is a backport of the corresponding class of FLOW3.
*  All credits go to the v5 team.
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

class Tx_Extbase_Configuration_BackendConfigurationManager_testcase extends Tx_Extbase_BaseTestCase {

	/**
	 * @var array
	 */
	protected $getBackup;

	/**
	 * @var array
	 */
	protected $postBackup;

	/**
	 * @var t3lib_DB
	 */
	protected $typo3DbBackup;

	/**
	 * @var Tx_Extbase_Configuration_BackendConfigurationManager
	 */
	protected $backendConfigurationManager;

	/**
	 * Sets up this testcase
	 */
	public function setUp() {
		$this->getBackup = t3lib_div::_GET();
		$this->postBackup = t3lib_div::_POST();

		$this->typo3DbBackup = $GLOBALS['TYPO3_DB'];
		$GLOBALS['TYPO3_DB'] = $this->getMock('t3lib_DB', array());

		$this->backendConfigurationManager = $this->getMock($this->buildAccessibleProxy('Tx_Extbase_Configuration_BackendConfigurationManager'), array('dummy'));
	}

	/**
	 * Tears down this testcase
	 */
	public function tearDown() {
		t3lib_div::_GETset($this->getBackup);
		$_POST = $this->postBackup;
	}

	/**
	 * @test
	 */
	public function loadTypoScriptSetupCantBeTested() {
		$this->markTestIncomplete('This method can\'t be tested with the current TYPO3 version, because we can\'t mock objects returned from t3lib_div::makeInstance().');
	}

	/**
	 * @test
	 */
	public function getCurrentPageIdReturnsPageIdFromGet() {
		t3lib_div::_GETset(array('id' => 123));

		$expectedResult = 123;
		$actualResult = $this->backendConfigurationManager->_call('getCurrentPageId');

		$this->assertEquals($expectedResult, $actualResult);
	}

	/**
	 * @test
	 */
	public function getCurrentPageIdReturnsPageIdFromPost() {
		t3lib_div::_GETset(array('id' => 123));
		$_POST['id'] = 321;

		$expectedResult = 321;
		$actualResult = $this->backendConfigurationManager->_call('getCurrentPageId');

		$this->assertEquals($expectedResult, $actualResult);
	}

	/**
	 * @test
	 */
	public function getCurrentPageIdReturnsPidFromFirstRootTemplateIfIdIsNotSet() {
		$GLOBALS['TYPO3_DB']->expects($this->once())
			->method('exec_SELECTgetRows')
			->with('pid', 'sys_template', 'deleted=0 AND hidden=0 AND root=1', '', '', '1')
			->will(
				$this->returnValue(
					array(
						array('pid' => 123)
					)
				)
			);

		$expectedResult = 123;
		$actualResult = $this->backendConfigurationManager->_call('getCurrentPageId');

		$this->assertEquals($expectedResult, $actualResult);
	}

	/**
	 * @test
	 */
	public function getCurrentPageIdReturnsUidFromFirstRootPageIfIdIsNotSetAndNoRootTemplateWasFound() {
		$GLOBALS['TYPO3_DB']->expects($this->at(0))
			->method('exec_SELECTgetRows')
			->with('pid', 'sys_template', 'deleted=0 AND hidden=0 AND root=1', '', '', '1')
			->will($this->returnValue(array()));

		$GLOBALS['TYPO3_DB']->expects($this->at(1))
			->method('exec_SELECTgetRows')
			->with('uid', 'pages', 'deleted=0 AND hidden=0 AND is_siteroot=1', '', '', '1')
			->will(
				$this->returnValue(
					array(
						array('uid' => 321)
					)
				)
			);

		$expectedResult = 321;
		$actualResult = $this->backendConfigurationManager->_call('getCurrentPageId');

		$this->assertEquals($expectedResult, $actualResult);
	}

	/**
	 * @test
	 */
	public function getCurrentPageIdReturnsDefaultStoragePidIfIdIsNotSetNoRootTemplateAndRootPageWasFound() {
		$GLOBALS['TYPO3_DB']->expects($this->at(0))
			->method('exec_SELECTgetRows')
			->with('pid', 'sys_template', 'deleted=0 AND hidden=0 AND root=1', '', '', '1')
			->will($this->returnValue(array()));

		$GLOBALS['TYPO3_DB']->expects($this->at(1))
			->method('exec_SELECTgetRows')
			->with('uid', 'pages', 'deleted=0 AND hidden=0 AND is_siteroot=1', '', '', '1')
			->will($this->returnValue(array()));

		$expectedResult = 0;
		$actualResult = $this->backendConfigurationManager->_call('getCurrentPageId');

		$this->assertEquals($expectedResult, $actualResult);
	}
}
?>
