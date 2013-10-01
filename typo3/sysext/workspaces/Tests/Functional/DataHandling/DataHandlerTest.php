<?php
namespace TYPO3\CMS\Workspaces\Tests\Functional\DataHandling;

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

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Functional test for the DataHandler
 */
class DataHandlerTest extends \TYPO3\CMS\Core\Tests\FunctionalTestCase {

	protected $coreExtensionsToLoad = array('version', 'workspaces');

	public function setUp() {
		parent::setUp();

		/** @var $backendUser \TYPO3\CMS\Core\Authentication\BackendUserAuthentication */
		$backendUser = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Authentication\\BackendUserAuthentication');
		$backendUser->user['admin'] = 1;
		$GLOBALS['BE_USER'] = $backendUser;
		\TYPO3\CMS\Core\Core\Bootstrap::getInstance()->initializeLanguageObject();

		$this->importDataSet(ORIGINAL_ROOT . 'typo3/sysext/core/Tests/Functional/Fixtures/pages.xml');
		$this->importDataSet(dirname(__FILE__) . '/../Fixtures/pages.xml');
	}

	/**
	 * @test
	 */
	public function canChangeTtContentInTheWorkspace() {
		$this->importDataSet(ORIGINAL_ROOT . 'typo3/sysext/core/Tests/Functional/Fixtures/tt_content.xml');
		$this->importDataSet(dirname(__FILE__) . '/../Fixtures/sys_workspace.xml');

		$workspaceId = 90;
		/** @var $backendUser \TYPO3\CMS\Core\Authentication\BackendUserAuthentication */
		$backendUser = $GLOBALS['BE_USER'];
		$backendUser->workspace = $workspaceId;

		$dataHandler = $this->getDataHandler();
		$uid = 1;
		$dataArray = array(
			'tt_content' => array(
				$uid => array(
					'header' => "Test Title [workspace 90]",
				)
			)
		);

		$dataHandler->start($dataArray, array());
		$dataHandler->process_datamap();

		$versionedId = $dataHandler->getAutoVersionId('tt_content', $uid);

		$database = $this->getDatabase();
		$row = $database->exec_SELECTgetSingleRow('*', 'tt_content', 'uid = ' . $versionedId);
		$this->assertNotEmpty($row);

		$this->assertEquals($versionedId, $row['uid']);
		$this->assertEquals(-1, $row['pid']);
		$this->assertEquals("Test Title [workspace $workspaceId]", $row['header']);
		$this->assertEquals(0, $row['l18n_parent'], 'wrong l18n_parent');
		$this->assertEquals(1, $row['t3_origuid'], 'wrong t3_origuid');

		$this->assertEquals($uid, $row['t3ver_oid']);
		$this->assertEquals(1, $row['t3ver_id']); // first version of this record
		$this->assertEquals($workspaceId, $row['t3ver_wsid']);
		$this->assertEquals('Auto-created for WS #' . $workspaceId, $row['t3ver_label']);
		$this->assertEquals(0, $row['t3ver_state']);
		$this->assertEquals(0, $row['t3ver_stage']);
		$this->assertEquals(0, $row['t3ver_count']);
		$this->assertEquals(0, $row['t3ver_tstamp']); //last published date
		$this->assertEquals(0, $row['t3ver_move_id']);
	}

	/**
	 * @return \TYPO3\CMS\Core\DataHandling\DataHandler
	 */
	protected function getDataHandler() {
		$dataHandler = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\DataHandling\\DataHandler');
		return $dataHandler;
	}
}
