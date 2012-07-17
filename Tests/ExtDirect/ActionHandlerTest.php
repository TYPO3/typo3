<?php
	/***************************************************************
	 *  Copyright notice
	 *
	 *  (c) 2010-2011 Workspaces Team (http://forge.typo3.org/projects/show/typo3v4-workspaces)
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
	 *  A copy is found in the textfile GPL.txt and important notices to the license
	 *  from the author is found in LICENSE.txt distributed with these scripts.
	 *
	 *
	 *  This script is distributed in the hope that it will be useful,
	 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
	 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	 *  GNU General Public License for more details.
	 *
	 *  This copyright notice MUST APPEAR in all copies of the script!
	 ***************************************************************/

	/**
	 * @author Workspaces Team (http://forge.typo3.org/projects/show/typo3v4-workspaces)
	 * @package Workspaces
	 * @subpackage Service
	 */
class Tx_Workspaces_ExtDirect_ActionHandlerTest extends tx_phpunit_database_testcase {

	/**
	 * @var Tx_Workspaces_ExtDirect_ActionHandler
	 */
	protected $fixture;

	/**
	 * @test
	 **/
	public function sendToSpecificStageExecuteIgnoresDoublePublishes() {

		$this->importDataSet(dirname(__FILE__) . '/fixtures/dbDefaultWorkspaces.xml');
		$this->importDataSet(dirname(__FILE__) . '/fixtures/dbVersionedContent.xml');

			// prepare parameter
		$parameter = new stdClass();
		$parameter->additional = '';
		$parameter->receipients = array();
		$parameter->comments = '';
		$parameter->affects = new stdClass();
		$parameter->affects->nextStage = -20; // Send to LIVE

		$parameter->affects->elements = array();

			// first and only affected element
		$elementOne = new stdClass();
		$elementOne->table = 'tt_content';
		$elementOne->uid = 2;
		$elementOne->t3ver_oid = 1;
		$parameter->affects->elements[] = $elementOne;

		$recordBeforePublish = t3lib_BEfunc::getRecord('tt_content', 2);
		$this->assertEquals($recordBeforePublish['header'], 'Workspace version of original content');

			// first publish
		$result = $this->fixture->sendToSpecificStageExecute($parameter);
		$this->assertTrue($result['success']);

		$recordAfterFirstPublish = t3lib_BEfunc::getRecord('tt_content', 2);
		$this->assertEquals($recordAfterFirstPublish['t3ver_wsid'], 0);
		$this->assertEquals($recordAfterFirstPublish['header'], 'Original content');

			// second publish
		$result = $this->fixture->sendToSpecificStageExecute($parameter);
		$this->assertTrue($result['success']);

		$recordAfterSecondPublish = t3lib_BEfunc::getRecord('tt_content', 2);
		$this->assertEquals($recordAfterSecondPublish['t3ver_wsid'], 0);
		$this->assertEquals($recordAfterSecondPublish['header'], 'Original content'); // in case of an error, this will again be "Workspace version of original content"
	}

	/**
	 *
	 */
	public function setUp() {
		$this->createDatabase();
		$db = $this->useTestDatabase();
		$this->importStdDB();
		$this->importExtensions(array('cms', 'version'));

		$this->fixture = $this->objectManager->get('Tx_Workspaces_ExtDirect_ActionHandler');
	}

	/**
	 *
	 */
	public function tearDown() {
		$this->cleanDatabase();
		$this->dropDatabase();
		$GLOBALS['TYPO3_DB']->sql_select_db(TYPO3_db);
	}

	/**
	 * Injects an untainted clone of the object manager and all its referencing
	 * objects for every test.
	 *
	 * @return void
	 */
	public function runBare() {
		$objectManager = t3lib_div::makeInstance('Tx_Extbase_Object_ObjectManager');
		$this->objectManager =  clone $objectManager;
		parent::runBare();
	}
}

?>