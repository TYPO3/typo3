<?php
namespace TYPO3\CMS\Core\Tests\Functional\DataHandling;

/***************************************************************
 * Copyright notice
 *
 * (c) 2013 Tymoteusz Motylewski <t.motylewski@gmail.com>
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Functional test for the DataHandler
 */
class DataHandlerTest extends \TYPO3\CMS\Core\Tests\FunctionalTestCase {

	public function setUp() {
		parent::setUp();

		/** @var $backendUser \TYPO3\CMS\Core\Authentication\BackendUserAuthentication */
		$backendUser = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Authentication\\BackendUserAuthentication');
		$backendUser->user['admin'] = 1;
		$GLOBALS['BE_USER'] = $backendUser;
		\TYPO3\CMS\Core\Core\Bootstrap::getInstance()->initializeLanguageObject();
		$this->importDataSet(dirname(__FILE__) . '/../Fixtures/pages.xml');
	}

	/**
	 * @test
	 */
	public function canCreateTtContent() {
		$dataHandler = $this->getDataHandler();

		$temporaryId = uniqid('NEW');
		$dataArray = array(
			'tt_content' => array(
				$temporaryId => array(
					'pid' => 1,
					'header' => "Test Title",
				)
			)
		);

		$dataHandler->start($dataArray, array());
		$dataHandler->process_datamap();
		$uid = $dataHandler->substNEWwithIDs[$temporaryId];

		$this->assertGreaterThanOrEqual(1, $uid);

		$database = $this->getDatabase();
		$row = $database->exec_SELECTgetSingleRow('*', 'tt_content', 'uid = ' . $uid);
		$this->assertNotEmpty($row);

		$this->assertEquals($dataArray['tt_content'][$temporaryId]['pid'], $row['pid']);
		$this->assertEquals($dataArray['tt_content'][$temporaryId]['header'], $row['header']);
		$this->assertEquals($uid, $row['uid']);
	}

	/**
	 * @test
	 */
	public function canLocalizeTtContent() {
		$this->importDataSet(dirname(__FILE__) . '/../Fixtures/sys_language.xml');
		$this->importDataSet(dirname(__FILE__) . '/../Fixtures/pages_language_overlay.xml');
		$this->importDataSet(dirname(__FILE__) . '/../Fixtures/tt_content.xml');

		$dataHandler = $this->getDataHandler();
		$originalRecordId  = 1;
		$languageRecordUid = 1;

		$commandMap = array(
			'tt_content' => array(
				$originalRecordId => array(
					'localize' => $languageRecordUid
				)
			)
		);

		$dataHandler->start(array(), $commandMap);
		$dataHandler->process_cmdmap();
		$uid = $dataHandler->copyMappingArray_merged['tt_content'][$originalRecordId];
		$this->assertGreaterThanOrEqual(2, $uid);

		$database = $this->getDatabase();
		$row = $database->exec_SELECTgetSingleRow('*', 'tt_content', 'uid = ' . $uid);
		$this->assertNotEmpty($row);

		$this->assertEquals(1, $row['pid']);
		$this->assertContains('Test content', $row['header']);
		$this->assertEquals($uid, $row['uid']);
		$this->assertEquals(1, $row['l18n_parent']);
	}

	/**
	 * @return \TYPO3\CMS\Core\DataHandling\DataHandler
	 */
	protected function getDataHandler() {
		$dataHandler = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\DataHandling\\DataHandler');
		return $dataHandler;
	}
}
