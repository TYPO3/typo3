<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 Oliver Hader <oliver@typo3.org>
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
 * Testcase for class t3lib_matchCondition.
 *
 * @author	Oliver Hader <oliver@typo3.org>
 * @package TYPO3
 * @subpackage t3lib
 */
class t3lib_matchCondition_testcase extends tx_phpunit_testcase {
	/**
	 * @var	boolean
	 */
	protected $backupGlobals = true;

	/**
	 * @var	array
	 */
	private $backupServer;

	/**
	 * @var	t3lib_matchCondition
	 */
	private $matchCondition;

	public function setUp() {
		$this->backupServer = $_SERVER;
		$this->matchCondition = t3lib_div::makeInstance('t3lib_matchCondition');
	}

	public function tearDown() {
		unset($this->matchCondition);
		$_SERVER = $this->backupServer;
	}

	/**
	 * Tests whether a condition matches Internet Explorer 7 on Windows.
	 * 
	 * @return	void
	 * @test
	 */
	public function conditionMatchesInternetExplorer7Windows() {
		$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 6.0)';
		$result = $this->matchCondition->match('[browser = msie] && [version = 7] && [system = winNT]');
		$this->assertTrue($result);
	}

	/**
	 * Tests whether a condition does not match Internet Explorer 7 on Windows.
	 * 
	 * @return	void
	 * @test
	 */
	public function conditionDoesNotMatchInternetExplorer7Windows() {
		$_SERVER['HTTP_USER_AGENT'] = 'Opera/9.25 (Windows NT 6.0; U; en)';
		$result = $this->matchCondition->match('[browser = msie] && [version = 7] && [system = winNT]');
		$this->assertFalse($result);
	}

	/**
	 * Tests whether the browserInfo hook is called.
	 * 
	 * @return	void
	 * @test
	 */
	public function browserInfoHookIsCalled() {
		$browserInfoHookMock = $this->getMock(uniqid('tx_browserInfoHook'), array('browserInfo'));
		$browserInfoHookMock->expects($this->atLeastOnce())->method('browserInfo');
		$this->matchCondition->hookObjectsArr = array($browserInfoHookMock);

		$this->matchCondition->match('[browser = msie] && [version = 7] && [system = winNT]');
	}

	/**
	 * Tests whether numerical comparison matches.
	 * @test
	 */
	public function conditionMatchesOnEqualExpression() {
		$this->assertTrue($this->matchCondition->match('[globalVar = LIT:10 = 10]'));
		$this->assertTrue($this->matchCondition->match('[globalVar = LIT:10.1 = 10.1]'));

		$this->assertTrue($this->matchCondition->match('[globalVar = LIT:10 == 10]'));
		$this->assertTrue($this->matchCondition->match('[globalVar = LIT:10.1 == 10.1]'));
	}

	/**
	 * Tests whether numerical comparison matches.
	 * @test
	 */
	public function conditionMatchesOnNotEqualExpression() {
		$this->assertTrue($this->matchCondition->match('[globalVar = LIT:10 != 20]'));
		$this->assertTrue($this->matchCondition->match('[globalVar = LIT:10.1 != 10.2]'));
	}

	/**
	 * Tests whether numerical comparison matches.
	 * @test
	 */
	public function conditionMatchesOnLowerThanExpression() {
		$this->assertTrue($this->matchCondition->match('[globalVar = LIT:10 < 20]'));
		$this->assertTrue($this->matchCondition->match('[globalVar = LIT:10.1 < 10.2]'));
	}

	/**
	 * Tests whether numerical comparison matches.
	 * @test
	 */
	public function conditionMatchesOnLowerThanOrEqualExpression() {
		$this->assertTrue($this->matchCondition->match('[globalVar = LIT:10 <= 10]'));
		$this->assertTrue($this->matchCondition->match('[globalVar = LIT:10 <= 20]'));

		$this->assertTrue($this->matchCondition->match('[globalVar = LIT:10.1 <= 10.1]'));
		$this->assertTrue($this->matchCondition->match('[globalVar = LIT:10.1 <= 10.2]'));
	}

	/**
	 * Tests whether numerical comparison matches.
	 * @test
	 */
	public function conditionMatchesOnGreaterThanExpression() {
		$this->assertTrue($this->matchCondition->match('[globalVar = LIT:20 > 10]'));
		$this->assertTrue($this->matchCondition->match('[globalVar = LIT:10.2 > 10.1]'));
	}

	/**
	 * Tests whether numerical comparison matches.
	 * @test
	 */
	public function conditionMatchesOnGreaterThanOrEqualExpression() {
		$this->assertTrue($this->matchCondition->match('[globalVar = LIT:10 >= 10]'));
		$this->assertTrue($this->matchCondition->match('[globalVar = LIT:20 >= 10]'));

		$this->assertTrue($this->matchCondition->match('[globalVar = LIT:10.1 >= 10.1]'));
		$this->assertTrue($this->matchCondition->match('[globalVar = LIT:10.2 >= 10.1]'));
	}
}
?>