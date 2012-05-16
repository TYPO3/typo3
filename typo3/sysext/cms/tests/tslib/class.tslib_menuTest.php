<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012 Stefan Galinski <stefan.galinski@gmail.com>
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
 * Testcase for the "tslib_menu" class in the TYPO3 Core.
 *
 * @package TYPO3
 * @subpackage tslib
 *
 * @author Stefan Galinski <stefan.galinski@gmail.com>
 */
class tslib_menuTest extends Tx_Extbase_Tests_Unit_BaseTestCase {
	/**
	 * @var tslib_menu
	 */
	private $fixture = NULL;

	/**
	 * @var array
	 */
	private $backupGlobalVariables = array();

	public function setUp() {
		$proxy = $this->buildAccessibleProxy('tslib_menu');
		$this->fixture = new $proxy;

		$backupGlobalVariables['TYPO3_DB'] = $GLOBALS['TYPO3_DB'];
		$GLOBALS['TYPO3_DB'] = $this->getMock('t3lib_db');

		$backupGlobalVariables['TSFE'] = $GLOBALS['TSFE'];
		$GLOBALS['TSFE'] = $this->getMock('tslib_fe');
	}

	public function tearDown() {
		foreach ($this->backupGlobalVariables as $key => $data) {
			$GLOBALS[$key] = $data;
		}

		unset($this->fixture);
	}

	////////////////////////////////
	// Tests concerning sectionIndex
	////////////////////////////////

	/**
	 * Prepares a test for the method sectionIndex
	 *
	 * @return void
	 */
	protected function prepareSectionIndexTest() {
		$this->fixture->sys_page = $this->getMock('t3lib_pageSelect');
		$this->fixture->parent_cObj = $this->getMock('tslib_cObj');
	}

	/**
	 * @test
	 */
	public function sectionIndexReturnsEmptyArrayIfTheRequestedPageCouldNotBeFetched() {
		$this->prepareSectionIndexTest();

		$this->fixture->sys_page->expects($this->once())->method('getPage')
			->will($this->returnValue(NULL));

		$result = $this->fixture->_call('sectionIndex', 'field');
		$this->assertEquals($result, array());
	}

	/**
	 * @test
	 */
	public function sectionIndexUsesTheInternalIdIfNoPageIdWasGiven() {
		$this->prepareSectionIndexTest();

		$this->fixture->id = 10;
		$this->fixture->sys_page->expects($this->once())->method('getPage')
			->will($this->returnValue(NULL))->with(10);

		$result = $this->fixture->_call('sectionIndex', 'field');
		$this->assertEquals($result, array());
	}

	/**
	 * @test
	 * @expectedException UnexpectedValueException
	 */
	public function sectionIndexThrowsAnExceptionIfTheInternalQueryFails() {
		$this->prepareSectionIndexTest();

		$this->fixture->sys_page->expects($this->once())->method('getPage')
			->will($this->returnValue(array()));

		$this->fixture->parent_cObj->expects($this->once())->method('exec_getQuery')
			->will($this->returnValue(0));

		$this->fixture->_call('sectionIndex', 'field');
	}

	/**
	 * @test
	 */
	public function sectionIndexReturnsOverlaidRowBasedOnTheLanguageOfTheGivenPage() {
		$this->prepareSectionIndexTest();

		$this->fixture->mconf['sectionIndex.']['type'] = 'all';
		$GLOBALS['TSFE']->sys_language_contentOL = 1;

		$this->fixture->sys_page->expects($this->once())->method('getPage')
			->will($this->returnValue(array('_PAGES_OVERLAY_LANGUAGE' => 1)));

		$this->fixture->parent_cObj->expects($this->once())->method('exec_getQuery')
			->will($this->returnValue(1));

		$GLOBALS['TYPO3_DB']->expects($this->exactly(2))->method('sql_fetch_assoc')
			->will($this->onConsecutiveCalls(
				$this->returnValue(array('uid' => 0, 'header' => 'NOT_OVERLAID')),
				$this->returnValue(FALSE))
			);

		$this->fixture->sys_page->expects($this->once())->method('getRecordOverlay')
			->will($this->returnValue(array('uid' => 0, 'header' => 'OVERLAID')));

		$result = $this->fixture->_call('sectionIndex', 'field');
		$this->assertEquals($result[0]['title'], 'OVERLAID');
	}

	/**
	 * @return array
	 */
	public function sectionIndexFiltersDataProvider() {
		return array(
			'unfiltered fields' => array(
				1, array(
					'sectionIndex' => 1,
					'header' => 'foo',
					'header_layout' => 1,
				),
			),
			'with unset section index' => array(
				0, array(
					'sectionIndex' => 0,
					'header' => 'foo',
					'header_layout' => 1,
				),
			),
			'with unset header' => array(
				0, array(
					'sectionIndex' => 1,
					'header' => '',
					'header_layout' => 1,
				),
			),
			'with header layout 100' => array(
				0, array(
					'sectionIndex' => 1,
					'header' => 'foo',
					'header_layout' => 100,
				),
			),
		);
	}

	/**
	 * @test
	 * @dataProvider sectionIndexFiltersDataProvider
	 * @param integer $expectedAmount
	 * @param array $dataRow
	 */
	public function sectionIndexFilters($expectedAmount, array $dataRow) {
		$this->prepareSectionIndexTest();

		$this->fixture->mconf['sectionIndex.']['type'] = 'header';

		$this->fixture->sys_page->expects($this->once())->method('getPage')
			->will($this->returnValue(array()));

		$this->fixture->parent_cObj->expects($this->once())->method('exec_getQuery')
			->will($this->returnValue(1));

		$GLOBALS['TYPO3_DB']->expects($this->exactly(2))->method('sql_fetch_assoc')
			->will($this->onConsecutiveCalls(
				$this->returnValue($dataRow),
				$this->returnValue(FALSE))
			);

		$result = $this->fixture->_call('sectionIndex', 'field');
		$this->assertCount($expectedAmount, $result);
	}
}
?>