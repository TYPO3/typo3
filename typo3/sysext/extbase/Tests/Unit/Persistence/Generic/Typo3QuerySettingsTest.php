<?php
namespace TYPO3\CMS\Extbase\Tests\Unit\Persistence\Generic;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Anja Leichsenring <anja.leichsenring@typo3.org>
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
 * Test case
 */
class Typo3QuerySettingsTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings
	 */
	protected $typo3QuerySettings;

	/**
	 * setup test environment
	 */
	public function setUp() {
		$this->typo3QuerySettings = $this->getAccessibleMock('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Typo3QuerySettings', array('dummy'));
	}

	/**
	 * @return array
	 */
	public function booleanValueProvider() {
		return array(
			'TRUE setting' => array(TRUE),
			'FALSE setting' => array(FALSE)
		);
	}

	/**
	 * @return array
	 */
	public function arrayValueProvider() {
		return array(
			'empty array' => array(array()),
			'two elements associative' => array(
				array(
					'one' => '42',
					21 => 12
				)
			),
			'three elements' => array(
				array(
					1,
					'dummy',
					array()
				)
			)
		);
	}

	/**
	 * @test
	 * @dataProvider booleanValueProvider
	 * @param boolean $input
	 */
	public function setRespectStoragePageSetsRespectStoragePageCorrectly($input) {
		$this->typo3QuerySettings->setRespectStoragePage($input);
		$this->assertEquals($input, $this->typo3QuerySettings->getRespectStoragePage());
	}

	/**
	 * @test
	 */
	public function setRespectStoragePageAllowsChaining() {
		$this->assertTrue($this->typo3QuerySettings->setRespectStoragePage(TRUE) instanceof \TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface);
	}

	/**
	 * @test
	 * @dataProvider arrayValueProvider
	 *
	 * @param array $input
	 */
	public function setStoragePageIdsSetsStoragePageIdsCorrectly($input) {
		$this->typo3QuerySettings->setStoragePageIds($input);
		$this->assertEquals($input, $this->typo3QuerySettings->getStoragePageIds());
	}

	/**
	 * @test
	 */
	public function setStoragePageIdsAllowsChaining() {
		$this->assertTrue($this->typo3QuerySettings->setStoragePageIds(array(1,2,3)) instanceof \TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface);
	}

	/**
	 * @test
	 * @dataProvider booleanValueProvider
	 *
	 * @param boolean $input
	 */
	public function setRespectSysLanguageSetsRespectSysLanguageCorrectly($input) {
		$this->typo3QuerySettings->setRespectSysLanguage($input);
		$this->assertEquals($input, $this->typo3QuerySettings->getRespectSysLanguage());
	}

	/**
	 * @test
	 */
	public function setRespectSysLanguageAllowsChaining() {
		$this->assertTrue($this->typo3QuerySettings->setRespectSysLanguage(TRUE) instanceof \TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface);
	}

	/**
	 * @test
	 */
	public function setLanguageUidAllowsChaining() {
		$this->assertTrue($this->typo3QuerySettings->setLanguageUid(42) instanceof \TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface);
	}

	/**
	 * @test
	 * @dataProvider booleanValueProvider
	 *
	 * @param boolean $input
	 */
	public function setIgnoreEnableFieldsSetsIgnoreEnableFieldsCorrectly($input) {
		$this->typo3QuerySettings->setIgnoreEnableFields($input);
		$this->assertEquals($input, $this->typo3QuerySettings->getIgnoreEnableFields());
	}

	/**
	 * @test
	 */
	public function setIgnoreEnableFieldsAllowsChaining() {
		$this->assertTrue($this->typo3QuerySettings->setIgnoreEnableFields(TRUE) instanceof \TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface);
	}

	/**
	 * @test
	 * @dataProvider arrayValueProvider
	 *
	 * @param array $input
	 */
	public function setEnableFieldsToBeIgnoredSetsEnableFieldsToBeIgnoredCorrectly($input) {
		$this->typo3QuerySettings->setEnableFieldsToBeIgnored($input);
		$this->assertEquals($input, $this->typo3QuerySettings->getEnableFieldsToBeIgnored());
	}

	/**
	 * @test
	 */
	public function setEnableFieldsToBeIgnoredAllowsChaining() {
		$this->assertTrue($this->typo3QuerySettings->setEnableFieldsToBeIgnored(array('starttime', 'endtime')) instanceof \TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface);
	}

	/**
	 * @test
	 * @dataProvider booleanValueProvider
	 *
	 * @param boolean $input
	 */
	public function setIncludeDeletedSetsIncludeDeletedCorrectly($input) {
		$this->typo3QuerySettings->setIncludeDeleted($input);
		$this->assertEquals($input, $this->typo3QuerySettings->getIncludeDeleted());
	}

	/**
	 * @test
	 */
	public function setIncludeDeletedAllowsChaining() {
		$this->assertTrue($this->typo3QuerySettings->setIncludeDeleted(TRUE) instanceof \TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface);
	}

	/**
	 * @test
	 * @dataProvider booleanValueProvider
	 *
	 * @param boolean $input
	 */
	public function setReturnRawQueryResultSetsReturnRawQueryResultCorrectly($input) {
		$this->typo3QuerySettings->setReturnRawQueryResult($input);
		$this->assertEquals($input, $this->typo3QuerySettings->getReturnRawQueryResult());
	}

	/**
	 * @test
	 */
	public function setReturnRawQueryResultAllowsChaining() {
		$this->assertTrue($this->typo3QuerySettings->setReturnRawQueryResult(TRUE) instanceof \TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface);
	}
}
