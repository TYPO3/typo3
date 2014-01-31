<?php
namespace TYPO3\CMS\Core\Tests\Functional\DataHandling\Regular;

/***************************************************************
 * Copyright notice
 *
 * (c) 2014 Oliver Hader <oliver.hader@typo3.org>
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

require_once dirname(dirname(__FILE__)) . '/AbstractDataHandlerActionTestCase.php';

/**
 * Functional test for the DataHandler
 */
abstract class AbstractActionTestCase extends \TYPO3\CMS\Core\Tests\Functional\DataHandling\AbstractDataHandlerActionTestCase {

	const VALUE_PageId = 89;
	const VALUE_PageIdTarget = 90;
	const VALUE_PageIdWebsite = 1;
	const VALUE_ContentIdFirst = 297;
	const VALUE_ContentIdLast = 298;
	const VALUE_LanguageId = 1;

	const TABLE_Page = 'pages';
	const TABLE_Content = 'tt_content';

	/**
	 * @var string
	 */
	protected $dataSetDirectory = 'typo3/sysext/core/Tests/Functional/DataHandling/Regular/DataSet/';

	public function setUp() {
		parent::setUp();
		$this->importScenarioDataSet('LiveDefaultPages');
		$this->importScenarioDataSet('LiveDefaultElements');
	}

	/**
	 * Content records
	 */

	/**
	 * @test
	 */
	public function createContentRecords() {
		// Creating record at the beginning of the page
		$this->actionService->createNewRecord(self::TABLE_Content, self::VALUE_PageId, array('header' => 'Testing #1'));
		// Creating record at the end of the page (after last one)
		$this->actionService->createNewRecord(self::TABLE_Content, -self::VALUE_ContentIdLast, array('header' => 'Testing #2'));
		$this->assertAssertionDataSet('createContentRecords');
	}

	/**
	 * @test
	 */
	public function modifyContentRecord() {
		$this->actionService->modifyRecord(self::TABLE_Content, self::VALUE_ContentIdLast, array('header' => 'Testing #1'));
		$this->assertAssertionDataSet('modifyContentRecord');
	}

	/**
	 * @test
	 */
	public function deleteContentRecord() {
		$this->actionService->deleteRecord(self::TABLE_Content, self::VALUE_ContentIdLast);
		$this->assertAssertionDataSet('deleteContentRecord');
	}

	/**
	 * @test
	 */
	public function copyContentRecord() {
		$this->actionService->copyRecord(self::TABLE_Content, self::VALUE_ContentIdLast, self::VALUE_PageId);
		$this->assertAssertionDataSet('copyContentRecord');
	}

	/**
	 * @test
	 */
	public function localizeContentRecord() {
		$this->actionService->localizeRecord(self::TABLE_Content, self::VALUE_ContentIdLast, self::VALUE_LanguageId);
		$this->assertAssertionDataSet('localizeContentRecord');
	}

	/**
	 * @test
	 */
	public function changeContentRecordSorting() {
		$this->actionService->moveRecord(self::TABLE_Content, self::VALUE_ContentIdFirst, -self::VALUE_ContentIdLast);
		$this->assertAssertionDataSet('changeContentRecordSorting');
	}

	/**
	 * @test
	 */
	public function moveContentRecordToDifferentPage() {
		$this->actionService->moveRecord(self::TABLE_Content, self::VALUE_ContentIdLast, self::VALUE_PageIdTarget);
		$this->assertAssertionDataSet('moveContentRecordToDifferentPage');
	}

	/**
	 * @test
	 */
	public function moveContentRecordToDifferentPageAndChangeSorting() {
		$this->actionService->moveRecord(self::TABLE_Content, self::VALUE_ContentIdLast, self::VALUE_PageIdTarget);
		$this->actionService->moveRecord(self::TABLE_Content, self::VALUE_ContentIdFirst, -self::VALUE_ContentIdLast);
		$this->assertAssertionDataSet('moveContentRecordToDifferentPageAndChangeSorting');
	}

	/**
	 * Page records
	 */

	/**
	 * @test
	 */
	public function createPageRecord() {
		$this->actionService->createNewRecord(self::TABLE_Page, self::VALUE_PageId, array('title' => 'Testing #1'));
		$this->assertAssertionDataSet('createPageRecord');
	}

	/**
	 * @test
	 */
	public function modifyPageRecord() {
		$this->actionService->modifyRecord(self::TABLE_Page, self::VALUE_PageId, array('title' => 'Testing #1'));
		$this->assertAssertionDataSet('modifyPageRecord');
	}

	/**
	 * @test
	 */
	public function deletePageRecord() {
		$this->actionService->deleteRecord(self::TABLE_Page, self::VALUE_PageId);
		$this->assertAssertionDataSet('deletePageRecord');
	}

	/**
	 * @test
	 */
	public function copyPageRecord() {
		$this->actionService->copyRecord(self::TABLE_Page, self::VALUE_PageId, self::VALUE_PageIdTarget);
		$this->assertAssertionDataSet('copyPageRecord');
	}

	/**
	 * @test
	 */
	public function localizePageRecord() {
		$this->actionService->localizeRecord(self::TABLE_Page, self::VALUE_PageId, self::VALUE_LanguageId);
		$this->assertAssertionDataSet('localizePageRecord');
	}

	/**
	 * @test
	 */
	public function changePageRecordSorting() {
		$this->actionService->moveRecord(self::TABLE_Page, self::VALUE_PageId, -self::VALUE_PageIdTarget);
		$this->assertAssertionDataSet('changePageRecordSorting');
	}

	/**
	 * @test
	 */
	public function movePageRecordToDifferentPage() {
		$this->actionService->moveRecord(self::TABLE_Page, self::VALUE_PageId, self::VALUE_PageIdTarget);
		$this->assertAssertionDataSet('movePageRecordToDifferentPage');
	}

	/**
	 * @test
	 */
	public function movePageRecordToDifferentPageAndChangeSorting() {
		$this->actionService->moveRecord(self::TABLE_Page, self::VALUE_PageIdTarget, self::VALUE_PageIdWebsite);
		$this->actionService->moveRecord(self::TABLE_Page, self::VALUE_PageId, -self::VALUE_PageIdTarget);
		$this->assertAssertionDataSet('movePageRecordToDifferentPageAndChangeSorting');
	}

}
