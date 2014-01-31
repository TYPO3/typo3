<?php
namespace TYPO3\CMS\Core\Tests\Functional\DataHandling\InlineRelationalRecordEditing\ForeignField;

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

require_once dirname(dirname(dirname(__FILE__))) . '/AbstractDataHandlerActionTestCase.php';

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
	const TABLE_Hotel = 'tx_irretutorial_1nff_hotel';
	const TABLE_Offer = 'tx_irretutorial_1nff_offer';

	/**
	 * @var string
	 */
	protected $dataSetDirectory = 'typo3/sysext/core/Tests/Functional/DataHandling/InlineRelationalRecordEditing/ForeignField/DataSet/';

	public function setUp() {
		parent::setUp();
		$this->importScenarioDataSet('LiveDefaultPages');
		$this->importScenarioDataSet('LiveDefaultElements');
	}

	/**
	 * Parent content records
	 */

	/**
	 * @test
	 */
	public function createParentContentRecord() {
		$this->actionService->createNewRecord(self::TABLE_Content, self::VALUE_PageId, array('header' => 'Testing #1'));
		$this->assertAssertionDataSet('createParentContentRecord');
	}

	/**
	 * @test
	 */
	public function modifyParentContentRecord() {
		$this->actionService->modifyRecord(self::TABLE_Content, self::VALUE_ContentIdLast, array('header' => 'Testing #1'));
		$this->assertAssertionDataSet('modifyParentContentRecord');
	}

	/**
	 * @test
	 */
	public function deleteParentContentRecord() {
		$this->actionService->deleteRecord(self::TABLE_Content, self::VALUE_ContentIdLast);
		$this->assertAssertionDataSet('deleteParentContentRecord');
	}

	/**
	 * @test
	 */
	public function copyParentContentRecord() {
		$this->actionService->copyRecord(self::TABLE_Content, self::VALUE_ContentIdLast, self::VALUE_PageId);
		$this->assertAssertionDataSet('copyParentContentRecord');
	}

	/**
	 * @test
	 */
	public function localizeParentContentRecord() {
		$this->actionService->localizeRecord(self::TABLE_Content, self::VALUE_ContentIdLast, self::VALUE_LanguageId);
		$this->assertAssertionDataSet('localizeParentContentRecord');
	}

	/**
	 * @test
	 */
	public function changeParentContentRecordSorting() {
		$this->actionService->moveRecord(self::TABLE_Content, self::VALUE_ContentIdFirst, -self::VALUE_ContentIdLast);
		$this->assertAssertionDataSet('changeParentContentRecordSorting');
	}

	/**
	 * @test
	 */
	public function moveParentContentRecordToDifferentPage() {
		$this->actionService->moveRecord(self::TABLE_Content, self::VALUE_ContentIdLast, self::VALUE_PageIdTarget);
		$this->assertAssertionDataSet('moveParentContentRecordToDifferentPage');
	}

	/**
	 * @test
	 */
	public function moveParentContentRecordToDifferentPageAndChangeSorting() {
		$this->actionService->moveRecord(self::TABLE_Content, self::VALUE_ContentIdLast, self::VALUE_PageIdTarget);
		$this->actionService->moveRecord(self::TABLE_Content, self::VALUE_ContentIdFirst, -self::VALUE_ContentIdLast);
		$this->assertAssertionDataSet('moveParentContentRecordToDifferentPageAndChangeSorting');
	}

	/**
	 * Page records
	 */

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
	 * IRRE Child Records
	 */

	/**
	 * @test
	 */
	public function createParentContentRecordWithHotelAndOfferChildRecords() {
		$this->actionService->createNewRecords(
			self::VALUE_PageId,
			array(
				self::TABLE_Offer => array('title' => 'Offer #1'),
				self::TABLE_Hotel => array('title' => 'Hotel #1', 'offers' => '__previousUid'),
				self::TABLE_Content => array('header' => 'Testing #1', 'tx_irretutorial_hotels' => '__previousUid'),
			)
		);
		$this->assertAssertionDataSet('createParentContentRecordWithHotelAndOfferChildRecords');
	}

	/**
	 * @test
	 */
	public function modifyOnlyHotelChildRecord() {
		$this->actionService->modifyRecord(self::TABLE_Hotel, 4, array('title' => 'Testing #1'));
		$this->assertAssertionDataSet('modifyOnlyHotelChildRecord');
	}

	/**
	 * @test
	 */
	public function modifyParentRecordAndChangeHotelChildRecordsSorting() {
		$this->actionService->modifyRecord(self::TABLE_Content, self::VALUE_ContentIdFirst, array('tx_irretutorial_hotels' => '4,3'));
		$this->assertAssertionDataSet('modifyParentRecordAndChangeHotelChildRecordsSorting');
	}

	/**
	 * @test
	 */
	public function modifyParentRecordWithHotelChildRecord() {
		$this->actionService->modifyRecords(
			self::VALUE_PageId,
			array(
				self::TABLE_Hotel => array('uid' => 4, 'title' => 'Testing #1'),
				self::TABLE_Content => array('uid' => self::VALUE_ContentIdLast, 'tx_irretutorial_hotels' => '3,4'),
			)
		);
		$this->assertAssertionDataSet('modifyParentRecordWithHotelChildRecord');
	}

	/**
	 * @test
	 */
	public function modifyParentRecordAndAddHotelChildRecord() {
		$this->actionService->modifyRecords(
			self::VALUE_PageId,
			array(
				self::TABLE_Hotel => array('uid' => '__NEW', 'title' => 'Hotel #2'),
				self::TABLE_Content => array('uid' => self::VALUE_ContentIdLast, 'tx_irretutorial_hotels' => '5,__previousUid'),
			)
		);
		$this->assertAssertionDataSet('modifyParentRecordAndAddHotelChildRecord');
	}

	/**
	 * @test
	 */
	public function modifyParentRecordAndDeleteHotelChildRecord() {
		$this->actionService->modifyRecord(
			self::TABLE_Content,
			self::VALUE_ContentIdFirst,
			array('tx_irretutorial_hotels' => '3'),
			array(self::TABLE_Hotel => array(4))
		);
		$this->assertAssertionDataSet('modifyParentRecordAndDeleteHotelChildRecord');
	}

}
