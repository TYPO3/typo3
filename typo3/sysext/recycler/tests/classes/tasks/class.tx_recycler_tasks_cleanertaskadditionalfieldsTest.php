<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012 Philipp Bergsmann <p.bergsmann@opendo.at>
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
 * Testcase for class "tx_recycler_tasks_CleanerTaskAdditionalFields"
 *
 * @author Philipp Bergsmann <p.bergsmann@opendo.at>
 * @package TYPO3
 * @subpackage tx_recycler
 */
class tx_recycler_tasks_CleanerTaskAdditionalFieldsTest extends Tx_Phpunit_TestCase {

	/**
	 * @var tx_recycler_tasks_CleanerTaskAdditionalFields
	 */
	protected $fixture = NULL;

	/**
	 * sets up an instance of tx_recycler_tasks_CleanerTaskAdditionalFields
	 */
	public function setUp() {
		$this->fixture = t3lib_div::makeInstance('tx_recycler_tasks_CleanerTaskAdditionalFields');
	}

	public function tearDown() {
		unset($this->fixture);
	}

	/**
	 * Dataprovider for the function "validationLogsPeriodError"
	 *
	 * @return array
	 */
	public function periodInvalidData() {
		return array(
			array('abc'),
			array(t3lib_div::makeInstance('tx_recycler_tasks_CleanerTask')),
			array(NULL),
			array(''),
			array(0),
			array('1234abc')
		);
	}

	/**
	 * @test
	 * @dataProvider periodInvalidData
	 */
	public function validationLogsPeriodError($period) {
		$submittedData = array(
			'RecyclerCleanerPeriod' => $period,
			'RecyclerCleanerTCA' => array('pages')
		);

		$taskMock = $this->getMock('tx_scheduler_Module');
		$taskMock->expects($this->once())
				 ->method('addMessage')
				 ->with($this->equalTo($GLOBALS['LANG']->sL('LLL:EXT:recycler/locallang_tasks.xlf:cleanerTaskErrorPeriod', TRUE)));

		$this->fixture->validateAdditionalFields($submittedData, $taskMock);
	}

	/**
	 * Dataprovider for the function "validationLogsTableError"
	 *
	 * @return array
	 */
	public function tablesInvalidData() {
		return array(
			array('abc'),
			array(t3lib_div::makeInstance('tx_recycler_tasks_CleanerTask')),
			array(NULL),
			array(123)
		);
	}

	/**
	 * @test
	 * @dataProvider tablesInvalidData
	 */
	public function validationLogsTableError($table) {
		$submittedData = array(
			'RecyclerCleanerPeriod' => 14,
			'RecyclerCleanerTCA' => $table
		);

		$taskMock = $this->getMock('tx_scheduler_Module');
		$taskMock->expects($this->once())
				 ->method('addMessage')
				 ->with($this->equalTo($GLOBALS['LANG']->sL('LLL:EXT:recycler/locallang_tasks.xlf:cleanerTaskErrorTCAempty', TRUE)));

		$this->fixture->validateAdditionalFields($submittedData, $taskMock);
	}

	/**
	 * @test
	 */
	public function validationIsTrueIfValid() {
		$submittedData = array(
			'RecyclerCleanerPeriod' => 14,
			'RecyclerCleanerTCA' => array('pages')
		);

		$taskMock = $this->getMock('tx_scheduler_Module');

		$this->assertTrue($this->fixture->validateAdditionalFields($submittedData, $taskMock));
	}

	/**
	 * @test
	 */
	public function fieldsAreSavedCorrect() {
		$submittedData = array(
			'RecyclerCleanerPeriod' => 14,
			'RecyclerCleanerTCA' => array('pages')
		);

		$taskMock = $this->getMock('tx_recycler_tasks_CleanerTask');

		$taskMock->expects($this->once())
				 ->method('setTCATables')
				 ->with($this->equalTo(array('pages')));

		$taskMock->expects($this->once())
				 ->method('setPeriod')
				 ->with($this->equalTo(14));

		$this->fixture->saveAdditionalFields($submittedData, $taskMock);
	}
}

?>