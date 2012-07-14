<?php
namespace TYPO3\CMS\Core\Tests\Functional\Collection;

/***************************************************************
 * Copyright notice
 *
 * (c) 2013 Fabien Udriot <fabien.udriot@typo3.org>
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
 * A copy is found in the textfile GPL.txt and important notices to the license
 * from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Test case
 *
 * @author Fabien Udriot <fabien.udriot@typo3.org>
 */
class StaticRecordCollectionTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var integer
	 */
	protected $mockCollectionUid = 0;

	/**
	 * @var integer
	 */
	protected $mockFileUid = 0;

	/**
	 * @var \Tx_Phpunit_Framework
	 */
	protected $testingFramework;

	/**
	 * @var \TYPO3\CMS\Core\Database\DatabaseConnection
	 */
	protected $database;

	/**
	 * Set up
	 */
	public function setUp() {
		$this->database = $GLOBALS['TYPO3_DB'];
		$this->testingFramework = new \Tx_Phpunit_Framework('sys_file');
	}

	/**
	 * Tear down
	 */
	public function tearDown() {
//		$this->testingFramework->cleanUp();
		$GLOBALS['TYPO3_DB'] = $this->database;
	}

	/**
	 * @test
	 */
	public function canLoadFileCollection() {
		$this->createDummyRecords();
		/** @var $collection \TYPO3\CMS\Core\Collection\StaticRecordCollection */
		$collection = \TYPO3\CMS\Core\Collection\StaticRecordCollection::load(
			$this->mockCollectionUid,
			TRUE
		);
		$this->assertEquals('1', $collection->count());
	}

	/**
	 * @test
	 */
	public function canCreateFileCollection() {
		$collectionRecord = array(
			'uid' => 0,
			'title' => uniqid('title'),
			'description' => uniqid('description'),
			'table_name' => 'items'
		);
		$collection = \TYPO3\CMS\Core\Collection\StaticRecordCollection::create($collectionRecord);
		$this->assertInstanceOf('\\TYPO3\\CMS\Core\\Collection\\StaticRecordCollection', $collection);
	}

	/**
	 * Create dummy records
	 */
	protected function createDummyRecords() {
		// Creating a new file collection
		$values = array(
			'title' => uniqid('title'),
			'type' => 'static',
			'files' => '1',
		);
		$this->mockCollectionUid = $this->testingFramework->createRecord('sys_file_collection', $values);

		// Creating a new file record
		$values = array(
			'identifier' => uniqid('identifier'),
			'name' => uniqid('name'),
		);
		$this->mockFileUid = $this->testingFramework->createRecord('sys_file', $values);

		// Creating a new file reference
		$values = array(
			'uid_local' => $this->mockFileUid,
			'uid_foreign' => $this->mockCollectionUid,
			'tablenames' => 'sys_file_collection',
			'fieldname' => 'files'
		);
		$this->testingFramework->createRecord('sys_file_reference', $values);
	}
}

?>