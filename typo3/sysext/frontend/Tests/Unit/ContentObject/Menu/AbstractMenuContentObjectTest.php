<?php
namespace TYPO3\CMS\Frontend\Tests\Unit\ContentObject\Menu;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012-2013 Stefan Galinski <stefan.galinski@gmail.com>
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
 * Testcase for TYPO3\CMS\Frontend\ContentObject\Menu\AbstractMenuContentObject
 *
 * @author Stefan Galinski <stefan.galinski@gmail.com>
 */
class AbstractMenuContentObjectTest extends \TYPO3\CMS\Extbase\Tests\Unit\BaseTestCase {

	/**
	 * A backup of the global database
	 *
	 * @var \TYPO3\CMS\Core\Database\DatabaseConnection
	 */
	protected $databaseBackup = NULL;

	/**
	 * @var \TYPO3\CMS\Frontend\ContentObject\Menu\AbstractMenuContentObject
	 */
	protected $fixture = NULL;

	/**
	 * Set up this testcase
	 */
	public function setUp() {
		$proxy = $this->buildAccessibleProxy('TYPO3\\CMS\\Frontend\\ContentObject\\Menu\\AbstractMenuContentObject');
		$this->fixture = new $proxy();
		$this->databaseBackup = $GLOBALS['TYPO3_DB'];
		$GLOBALS['TYPO3_DB'] = $this->getMock('TYPO3\\CMS\\Core\\Database\\DatabaseConnection');
		$GLOBALS['TSFE'] = $this->getMock('TYPO3\\CMS\\Frontend\\Controller\\TypoScriptFrontendController', array(), array($GLOBALS['TYPO3_CONF_VARS'], 1, 1));
		$GLOBALS['TSFE']->cObj = new \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer();
		$GLOBALS['TSFE']->page = array();
	}

	/**
	 * Tear down this testcase
	 */
	public function tearDown() {
		$GLOBALS['TYPO3_DB'] = $this->databaseBackup;
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
		$this->fixture->sys_page = $this->getMock('TYPO3\\CMS\\Frontend\\Page\\PageRepository');
		$this->fixture->parent_cObj = $this->getMock('TYPO3\\CMS\\Frontend\\ContentObject\\ContentObjectRenderer');
	}

	/**
	 * @test
	 */
	public function sectionIndexReturnsEmptyArrayIfTheRequestedPageCouldNotBeFetched() {
		$this->prepareSectionIndexTest();
		$this->fixture->sys_page->expects($this->once())->method('getPage')->will($this->returnValue(NULL));
		$result = $this->fixture->_call('sectionIndex', 'field');
		$this->assertEquals($result, array());
	}

	/**
	 * @test
	 */
	public function sectionIndexUsesTheInternalIdIfNoPageIdWasGiven() {
		$this->prepareSectionIndexTest();
		$this->fixture->id = 10;
		$this->fixture->sys_page->expects($this->once())->method('getPage')->will($this->returnValue(NULL))->with(10);
		$result = $this->fixture->_call('sectionIndex', 'field');
		$this->assertEquals($result, array());
	}

	/**
	 * @test
	 * @expectedException UnexpectedValueException
	 */
	public function sectionIndexThrowsAnExceptionIfTheInternalQueryFails() {
		$this->prepareSectionIndexTest();
		$this->fixture->sys_page->expects($this->once())->method('getPage')->will($this->returnValue(array()));
		$this->fixture->parent_cObj->expects($this->once())->method('exec_getQuery')->will($this->returnValue(0));
		$this->fixture->_call('sectionIndex', 'field');
	}

	/**
	 * @test
	 */
	public function sectionIndexReturnsOverlaidRowBasedOnTheLanguageOfTheGivenPage() {
		$this->prepareSectionIndexTest();
		$this->fixture->mconf['sectionIndex.']['type'] = 'all';
		$GLOBALS['TSFE']->sys_language_contentOL = 1;
		$this->fixture->sys_page->expects($this->once())->method('getPage')->will($this->returnValue(array('_PAGES_OVERLAY_LANGUAGE' => 1)));
		$this->fixture->parent_cObj->expects($this->once())->method('exec_getQuery')->will($this->returnValue(1));
		$GLOBALS['TYPO3_DB']->expects($this->exactly(2))->method('sql_fetch_assoc')->will($this->onConsecutiveCalls($this->returnValue(array('uid' => 0, 'header' => 'NOT_OVERLAID')), $this->returnValue(FALSE)));
		$this->fixture->sys_page->expects($this->once())->method('getRecordOverlay')->will($this->returnValue(array('uid' => 0, 'header' => 'OVERLAID')));
		$result = $this->fixture->_call('sectionIndex', 'field');
		$this->assertEquals($result[0]['title'], 'OVERLAID');
	}

	/**
	 * @return array
	 */
	public function sectionIndexFiltersDataProvider() {
		return array(
			'unfiltered fields' => array(
				1,
				array(
					'sectionIndex' => 1,
					'header' => 'foo',
					'header_layout' => 1
				)
			),
			'with unset section index' => array(
				0,
				array(
					'sectionIndex' => 0,
					'header' => 'foo',
					'header_layout' => 1
				)
			),
			'with unset header' => array(
				0,
				array(
					'sectionIndex' => 1,
					'header' => '',
					'header_layout' => 1
				)
			),
			'with header layout 100' => array(
				0,
				array(
					'sectionIndex' => 1,
					'header' => 'foo',
					'header_layout' => 100
				)
			)
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
		$this->fixture->sys_page->expects($this->once())->method('getPage')->will($this->returnValue(array()));
		$this->fixture->parent_cObj->expects($this->once())->method('exec_getQuery')->will($this->returnValue(1));
		$GLOBALS['TYPO3_DB']->expects($this->exactly(2))->method('sql_fetch_assoc')->will($this->onConsecutiveCalls($this->returnValue($dataRow), $this->returnValue(FALSE)));
		$result = $this->fixture->_call('sectionIndex', 'field');
		$this->assertCount($expectedAmount, $result);
	}

	/**
	 * @return array
	 */
	public function sectionIndexQueriesWithDifferentColPosDataProvider() {
		return array(
			'no configuration' => array(
				array(),
				'colPos=0'
			),
			'with useColPos 2' => array(
				array('useColPos' => 2),
				'colPos=2'
			),
			'with useColPos -1' => array(
				array('useColPos' => -1),
				''
			),
			'with stdWrap useColPos' => array(
				array(
					'useColPos.' => array(
						'wrap' => '2|'
					)
				),
				'colPos=2'
			)
		);
	}

	/**
	 * @test
	 * @dataProvider sectionIndexQueriesWithDifferentColPosDataProvider
	 * @param array $configuration
	 * @param string $whereClausePrefix
	 */
	public function sectionIndexQueriesWithDifferentColPos($configuration, $whereClausePrefix) {
		$this->prepareSectionIndexTest();
		$this->fixture->sys_page->expects($this->once())->method('getPage')->will($this->returnValue(array()));
		$this->fixture->mconf['sectionIndex.'] = $configuration;
		$queryConfiguration = array(
			'pidInList' => 12,
			'orderBy' => 'field',
			'languageField' => 'sys_language_uid',
			'where' => $whereClausePrefix
		);
		$this->fixture->parent_cObj->expects($this->once())->method('exec_getQuery')->with('tt_content', $queryConfiguration)->will($this->returnValue(1));
		$this->fixture->_call('sectionIndex', 'field', 12);
	}

	////////////////////////////////////
	// Tests concerning menu item states
	////////////////////////////////////
	/**
	 * @return array
	 */
	public function ifsubHasToCheckExcludeUidListDataProvider() {
		return array(
			'none excluded' => array (
				array(12, 34, 56),
				'1, 23, 456',
				TRUE
			),
			'one excluded' => array (
				array(1, 234, 567),
				'1, 23, 456',
				TRUE
			),
			'three excluded' => array (
				array(1, 23, 456),
				'1, 23, 456',
				FALSE
			),
			'empty excludeList' => array (
				array(1, 123, 45),
				'',
				TRUE
			),
			'empty menu' => array (
				array(),
				'1, 23, 456',
				FALSE
			),
		);

	}

	/**
	 * @test
	 * @dataProvider ifsubHasToCheckExcludeUidListDataProvider
	 * @param array $menuItems
	 * @param string $excludeUidList
	 * @param boolean $expectedResult
	 */
	public function ifsubHasToCheckExcludeUidList($menuItems, $excludeUidList, $expectedResult) {
		$menu = array();
		foreach ($menuItems as $page) {
			$menu[] = array('uid' => $page);
		}

		$this->prepareSectionIndexTest();
		$this->fixture->parent_cObj = $this->getMock('TYPO3\\CMS\\Frontend\\ContentObject\\ContentObjectRenderer', array());

		$this->fixture->sys_page->expects($this->once())->method('getMenu')->will($this->returnValue($menu));
		$this->fixture->menuArr = array(
			0 => array('uid' => 1)
		);
		$this->fixture->conf['excludeUidList'] = $excludeUidList;

		$this->assertEquals($expectedResult, $this->fixture->isItemState('IFSUB', 0));
	}
}


?>
