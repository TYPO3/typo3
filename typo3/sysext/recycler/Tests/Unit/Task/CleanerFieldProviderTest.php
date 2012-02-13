<?php
namespace TYPO3\CMS\Recycler\Tests\Unit\Task;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Philipp Bergsmann <p.bergsmann@opendo.at>
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
 * Testcase
 */
class CleanerFieldProviderTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\CMS\Recycler\Task\CleanerFieldProvider
	 */
	protected $fixture = NULL;

	/**
	 * Sets up an instance of \TYPO3\CMS\Recycler\Task\CleanerFieldProvider
	 */
	public function setUp() {
		$this->fixture = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Recycler\\Task\\CleanerFieldProvider');
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
			array(\TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Recycler\\Task\\CleanerTask')),
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

		$taskMock = $this->getMock('TYPO3\\CMS\\Scheduler\\Controller\\SchedulerModuleController');
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
			array(\TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Recycler\\Task\\CleanerTask')),
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

		$taskMock = $this->getMock('TYPO3\\CMS\\Scheduler\\Controller\\SchedulerModuleController');
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

		$taskMock = $this->getMock('TYPO3\\CMS\\Scheduler\\Controller\\SchedulerModuleController');

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

		$taskMock = $this->getMock('TYPO3\\CMS\\Recycler\\Task\\CleanerTask');

		$taskMock->expects($this->once())
				 ->method('setTcaTables')
				 ->with($this->equalTo(array('pages')));

		$taskMock->expects($this->once())
				 ->method('setPeriod')
				 ->with($this->equalTo(14));

		$this->fixture->saveAdditionalFields($submittedData, $taskMock);
	}
}

?>