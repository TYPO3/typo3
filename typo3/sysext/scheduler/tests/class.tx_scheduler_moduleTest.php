<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2011 Andy Grunwald <andreas.grunwald@wmdb.de>
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
 * Testcase for class "tx_scheduler_Module"
 *
 * @package TYPO3
 * @subpackage tx_scheduler
 *
 * @author Andy Grunwald <andreas.grunwald@wmdb.de>
 */
class tx_scheduler_ModuleTest extends tx_phpunit_testcase {

	/**
	 * Object to Test: tx_scheduler_Module
	 * 
	 * @var tx_scheduler_Module
	 */
	protected $testObject = NULL;

	public function setUp() {
		$this->testObject = new tx_scheduler_Module();
	}

	public function tearDown() {
		$this->testObject = NULL;
	}

	public function checkDateWithStrToTimeValuesDataProvider() {
		return array(
			array('now', strtotime('now')),
			array('10 September 2000', strtotime('10 September 2000')),
			array('+1 day', strtotime('+1 day')),
			array('+1 week', strtotime('+1 week')),
			array('+1 week 2 days 4 hours 2 seconds', strtotime('+1 week 2 days 4 hours 2 seconds')),
			array('next Thursday', strtotime('next Thursday')),
			array('last Monday', strtotime('last Monday')),
		);
	}

	/**
	 * @dataProvider checkDateWithStrToTimeValuesDataProvider
	 * @test
	 * @see http://de.php.net/manual/de/function.strtotime.php
	 */
	public function checkDateWithStrToTimeValues($strToTimeValue, $expectedTimestamp) {
		$this->assertSame($expectedTimestamp, $this->testObject->checkDate($strToTimeValue), 'Failes with value \'' . $strToTimeValue . '\'');
	}

	public function checkDateWithTYPO3sDateSyntaxDataProvider() {
		return array(
			array('00:00 2011-05-30', mktime(0, 0, 0, 5, 30, 2011)),
			array('00:01 2011-05-30', mktime(0, 1, 0, 5, 30, 2011)),
			array('23:59 2011-05-30', mktime(23, 59, 0, 5, 30, 2011)),
			array('15:35 2000-12-24', mktime(15, 35, 0, 12, 24, 2000)),
			array('00:01 1970-01-01', mktime(0, 1, 0, 1, 1, 1970)),
			array('17:26 2020-03-15', mktime(17, 26, 0, 3, 15, 2020)),
			array('1:5 2020-03-15', mktime(1, 5, 0, 3, 15, 2020)),
			array('10:50 2020-3-5', mktime(10, 50, 0, 3, 5, 2020)),
			array('00:01 1968-01-01', -63158340),
		);
	}

	/**
	 * @dataProvider checkDateWithTYPO3sDateSyntaxDataProvider
	 * @test
	 */
	public function checkDateWithTYPO3sDateSyntax($typo3DateValue, $expectedTimestamp) {
		$this->assertSame($expectedTimestamp, $this->testObject->checkDate($typo3DateValue), 'Failes with value \'' . $typo3DateValue . '\'');
	}

	public function checkDateWithInvalidDateValuesDataProvider() {
		return array(
			array('Not Good'),
			array('HH:ii yyyy-mm-dd'),
		);
	}

	/**
	 * @dataProvider checkDateWithInvalidDateValuesDataProvider
	 * expectedException InvalidArgumentException
	 * @test
	 */
	public function checkDateWithInvalidDateValues($dateValue) {
		$result = '';
		
		try {
			$result = $this->testObject->checkDate($dateValue);
		}catch (InvalidArgumentException $expected) {
			return;
		}
		
 		$this->fail('Failes with value \'' . $dateValue . '\'. Returned \'' . $result . '\'.');
	}
}
?>