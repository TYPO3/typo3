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

	const FIELD_ContentHotel = 'tx_irretutorial_1nff_hotels';
	const FIELD_HotelOffer = 'offers';

	/**
	 * @var string
	 */
	protected $dataSetDirectory = 'typo3/sysext/core/Tests/Functional/DataHandling/InlineRelationalRecordEditing/ForeignField/DataSet/';

	public function setUp() {
		parent::setUp();
		$this->importScenarioDataSet('LiveDefaultPages');
		$this->importScenarioDataSet('LiveDefaultElements');

		$this->setUpFrontendRootPage(1, array('typo3/sysext/core/Tests/Functional/Fixtures/Frontend/JsonRenderer.ts'));
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

		$responseContent = $this->getFrontendResponse(self::VALUE_PageId)->getResponseContent();
		$this->assertResponseContentHasRecords($responseContent, self::TABLE_Content, 'header', 'Testing #1');
	}

	/**
	 * @test
	 */
	public function modifyParentContentRecord() {
		$this->actionService->modifyRecord(self::TABLE_Content, self::VALUE_ContentIdLast, array('header' => 'Testing #1'));
		$this->assertAssertionDataSet('modifyParentContentRecord');

		$responseContent = $this->getFrontendResponse(self::VALUE_PageId)->getResponseContent();
		$this->assertResponseContentHasRecords($responseContent, self::TABLE_Content, 'header', 'Testing #1');
		$this->assertResponseContentStructureHasRecords(
			$responseContent, self::TABLE_Content . ':' . self::VALUE_ContentIdLast, self::FIELD_ContentHotel,
			self::TABLE_Hotel, 'title', array('Hotel #1')
		);
	}

	/**
	 * @test
	 */
	public function deleteParentContentRecord() {
		$this->actionService->deleteRecord(self::TABLE_Content, self::VALUE_ContentIdLast);
		$this->assertAssertionDataSet('deleteParentContentRecord');

		$responseContent = $this->getFrontendResponse(self::VALUE_PageId)->getResponseContent();
		$this->assertResponseContentDoesNotHaveRecords($responseContent, self::TABLE_Content, 'header', 'Regular Element #2');
	}

	/**
	 * @test
	 */
	public function copyParentContentRecord() {
		$newTableIds = $this->actionService->copyRecord(self::TABLE_Content, self::VALUE_ContentIdLast, self::VALUE_PageId);
		$this->assertAssertionDataSet('copyParentContentRecord');

		$newContentId = $newTableIds[self::TABLE_Content][self::VALUE_ContentIdLast];
		$responseContent = $this->getFrontendResponse(self::VALUE_PageId)->getResponseContent();
		$this->assertResponseContentStructureHasRecords(
			$responseContent, self::TABLE_Content . ':' . $newContentId, self::FIELD_ContentHotel,
			self::TABLE_Hotel, 'title', array('Hotel #1')
		);
	}

	/**
	 * @test
	 */
	public function localizeParentContentRecord() {
		$this->actionService->localizeRecord(self::TABLE_Content, self::VALUE_ContentIdLast, self::VALUE_LanguageId);
		$this->assertAssertionDataSet('localizeParentContentRecord');

		$responseContent = $this->getFrontendResponse(self::VALUE_PageId, self::VALUE_LanguageId)->getResponseContent();
		$this->assertResponseContentStructureHasRecords(
			$responseContent, self::TABLE_Content . ':' . self::VALUE_ContentIdLast, self::FIELD_ContentHotel,
			self::TABLE_Hotel, 'title', array('[Translate to Dansk:] Hotel #1')
		);
	}

	/**
	 * @test
	 */
	public function changeParentContentRecordSorting() {
		$this->actionService->moveRecord(self::TABLE_Content, self::VALUE_ContentIdFirst, -self::VALUE_ContentIdLast);
		$this->assertAssertionDataSet('changeParentContentRecordSorting');

		$responseContent = $this->getFrontendResponse(self::VALUE_PageId)->getResponseContent();
		$this->assertResponseContentStructureHasRecords(
			$responseContent, self::TABLE_Content . ':' . self::VALUE_ContentIdFirst, self::FIELD_ContentHotel,
			self::TABLE_Hotel, 'title', array('Hotel #1', 'Hotel #2')
		);
		$this->assertResponseContentStructureHasRecords(
			$responseContent, self::TABLE_Content . ':' . self::VALUE_ContentIdLast, self::FIELD_ContentHotel,
			self::TABLE_Hotel, 'title', array('Hotel #1')
		);
	}

	/**
	 * @test
	 */
	public function moveParentContentRecordToDifferentPage() {
		$this->actionService->moveRecord(self::TABLE_Content, self::VALUE_ContentIdLast, self::VALUE_PageIdTarget);
		$this->assertAssertionDataSet('moveParentContentRecordToDifferentPage');

		$responseContent = $this->getFrontendResponse(self::VALUE_PageIdTarget)->getResponseContent();
		$this->assertResponseContentHasRecords($responseContent, self::TABLE_Content, 'header', 'Regular Element #2');
		$this->assertResponseContentStructureHasRecords(
			$responseContent, self::TABLE_Content . ':' . self::VALUE_ContentIdLast, self::FIELD_ContentHotel,
			self::TABLE_Hotel, 'title', array('Hotel #1')
		);
	}

	/**
	 * @test
	 */
	public function moveParentContentRecordToDifferentPageAndChangeSorting() {
		$this->actionService->moveRecord(self::TABLE_Content, self::VALUE_ContentIdLast, self::VALUE_PageIdTarget);
		$this->actionService->moveRecord(self::TABLE_Content, self::VALUE_ContentIdFirst, -self::VALUE_ContentIdLast);
		$this->assertAssertionDataSet('moveParentContentRecordToDifferentPageAndChangeSorting');

		$responseContent = $this->getFrontendResponse(self::VALUE_PageIdTarget)->getResponseContent();
		$this->assertResponseContentHasRecords($responseContent, self::TABLE_Content, 'header', array('Regular Element #2', 'Regular Element #1'));
		$this->assertResponseContentStructureHasRecords(
			$responseContent, self::TABLE_Content . ':' . self::VALUE_ContentIdFirst, self::FIELD_ContentHotel,
			self::TABLE_Hotel, 'title', array('Hotel #1', 'Hotel #2')
		);
		$this->assertResponseContentStructureHasRecords(
			$responseContent, self::TABLE_Content . ':' . self::VALUE_ContentIdLast, self::FIELD_ContentHotel,
			self::TABLE_Hotel, 'title', array('Hotel #1')
		);
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

		$responseContent = $this->getFrontendResponse(self::VALUE_PageId)->getResponseContent();
		$this->assertResponseContentHasRecords($responseContent, self::TABLE_Page, 'title', 'Testing #1');
	}

	/**
	 * @test
	 */
	public function deletePageRecord() {
		$this->actionService->deleteRecord(self::TABLE_Page, self::VALUE_PageId);
		$this->assertAssertionDataSet('deletePageRecord');

		$response = $this->getFrontendResponse(self::VALUE_PageId, 0, 0, 0, FALSE);
		$this->assertContains('PageNotFoundException', $response->getError());
	}

	/**
	 * @test
	 */
	public function copyPageRecord() {
		$newTableIds = $this->actionService->copyRecord(self::TABLE_Page, self::VALUE_PageId, self::VALUE_PageIdTarget);
		$this->assertAssertionDataSet('copyPageRecord');

		$newPageId = $newTableIds[self::TABLE_Page][self::VALUE_PageId];
		$responseContent = $this->getFrontendResponse($newPageId)->getResponseContent();
		$this->assertResponseContentHasRecords($responseContent, self::TABLE_Hotel, 'title', array('Hotel #1', 'Hotel #2', 'Hotel #1'));
	}

	/**
	 * IRRE Child Records
	 */

	/**
	 * @test
	 */
	public function createParentContentRecordWithHotelAndOfferChildRecords() {
		$newTableIds = $this->actionService->createNewRecords(
			self::VALUE_PageId,
			array(
				self::TABLE_Offer => array('title' => 'Offer #1'),
				self::TABLE_Hotel => array('title' => 'Hotel #1', self::FIELD_HotelOffer => '__previousUid'),
				self::TABLE_Content => array('header' => 'Testing #1', self::FIELD_ContentHotel => '__previousUid'),
			)
		);
		$this->assertAssertionDataSet('createParentContentRecordWithHotelAndOfferChildRecords');

		$newContentId = $newTableIds[self::TABLE_Content][0];
		$responseContent = $this->getFrontendResponse(self::VALUE_PageId)->getResponseContent();
		$this->assertResponseContentHasRecords($responseContent, self::TABLE_Content, 'header', 'Testing #1');
		$this->assertResponseContentStructureHasRecords(
			$responseContent, self::TABLE_Content . ':' . $newContentId, self::FIELD_ContentHotel,
			self::TABLE_Hotel, 'title', 'Hotel #1'
		);
	}

	/**
	 * @test
	 */
	public function createAndCopyParentContentRecordWithHotelAndOfferChildRecords() {
		$newTableIds = $this->actionService->createNewRecords(
			self::VALUE_PageId,
			array(
				self::TABLE_Offer => array('title' => 'Offer #1'),
				self::TABLE_Hotel => array('title' => 'Hotel #1', self::FIELD_HotelOffer => '__previousUid'),
				self::TABLE_Content => array('header' => 'Testing #1', self::FIELD_ContentHotel => '__previousUid'),
			)
		);
		$newContentId = $newTableIds[self::TABLE_Content][0];
		$newHotelId = $newTableIds[self::TABLE_Hotel][0];
		$copiedTableIds = $this->actionService->copyRecord(self::TABLE_Content, $newContentId, self::VALUE_PageId);
		$this->assertAssertionDataSet('createAndCopyParentContentRecordWithHotelAndOfferChildRecords');

		$copiedContentId = $copiedTableIds[self::TABLE_Content][$newContentId];
		$copiedHotelId = $copiedTableIds[self::TABLE_Hotel][$newHotelId];
		$responseContent = $this->getFrontendResponse(self::VALUE_PageId)->getResponseContent();
		$this->assertResponseContentStructureHasRecords(
			$responseContent, self::TABLE_Content . ':' . $newContentId, self::FIELD_ContentHotel,
			self::TABLE_Hotel, 'title', 'Hotel #1'
		);
		$this->assertResponseContentStructureHasRecords(
			$responseContent, self::TABLE_Content . ':' . $copiedContentId, self::FIELD_ContentHotel,
			self::TABLE_Hotel, 'title', 'Hotel #1'
		);
		$this->assertResponseContentStructureHasRecords(
			$responseContent, self::TABLE_Hotel . ':' . $copiedHotelId, self::FIELD_HotelOffer,
			self::TABLE_Offer, 'title', 'Offer #1'
		);
	}

	/**
	 * @test
	 */
	public function createAndLocalizeParentContentRecordWithHotelAndOfferChildRecords() {
		// @todo Localizing the new child records is broken in the Core
		$newTableIds = $this->actionService->createNewRecords(
			self::VALUE_PageId,
			array(
				self::TABLE_Offer => array('title' => 'Offer #1'),
				self::TABLE_Hotel => array('title' => 'Hotel #1', self::FIELD_HotelOffer => '__previousUid'),
				self::TABLE_Content => array('header' => 'Testing #1', self::FIELD_ContentHotel => '__previousUid'),
			)
		);
		$newContentId = $newTableIds[self::TABLE_Content][0];
		$newHotelId = $newTableIds[self::TABLE_Hotel][0];
		$localizedTableIds = $this->actionService->localizeRecord(self::TABLE_Content, $newContentId, self::VALUE_LanguageId);
		$this->assertAssertionDataSet('createAndLocalizeParentContentRecordWithHotelAndOfferChildRecords');

		$localizedContentId = $localizedTableIds[self::TABLE_Content][$newContentId];
		$localizedHotelId = $localizedTableIds[self::TABLE_Hotel][$newHotelId];
		$responseContent = $this->getFrontendResponse(self::VALUE_PageId, self::VALUE_LanguageId)->getResponseContent();

		// @todo Does not work since children don't point to live-default record
		/*
			$this->assertResponseContentStructureHasRecords(
				$responseContent, self::TABLE_Content . ':' . $localizedContentId, self::FIELD_ContentHotel,
				self::TABLE_Hotel, 'title', '[Translate to Dansk:] Hotel #1'
			);
			$this->assertResponseContentStructureHasRecords(
				$responseContent, self::TABLE_Hotel . ':' . $localizedHotelId, self::FIELD_HotelOffer,
				self::TABLE_Offer, 'title', '[Translate to Dansk:] Offer #1'
			);
		*/
	}

	/**
	 * @test
	 */
	public function modifyOnlyHotelChildRecord() {
		$this->actionService->modifyRecord(self::TABLE_Hotel, 4, array('title' => 'Testing #1'));
		$this->assertAssertionDataSet('modifyOnlyHotelChildRecord');

		$responseContent = $this->getFrontendResponse(self::VALUE_PageId)->getResponseContent();
		$this->assertResponseContentStructureHasRecords(
			$responseContent, self::TABLE_Content . ':' . self::VALUE_ContentIdFirst, self::FIELD_ContentHotel,
			self::TABLE_Hotel, 'title', array('Hotel #1', 'Testing #1')
		);
	}

	/**
	 * @test
	 */
	public function modifyParentRecordAndChangeHotelChildRecordsSorting() {
		$this->actionService->modifyRecord(self::TABLE_Content, self::VALUE_ContentIdFirst, array(self::FIELD_ContentHotel => '4,3'));
		$this->assertAssertionDataSet('modifyParentRecordAndChangeHotelChildRecordsSorting');

		$responseContent = $this->getFrontendResponse(self::VALUE_PageId)->getResponseContent();
		$this->assertResponseContentStructureHasRecords(
			$responseContent, self::TABLE_Content . ':' . self::VALUE_ContentIdFirst, self::FIELD_ContentHotel,
			self::TABLE_Hotel, 'title', array('Hotel #2', 'Hotel #1')
		);
	}

	/**
	 * @test
	 */
	public function modifyParentRecordWithHotelChildRecord() {
		$this->actionService->modifyRecords(
			self::VALUE_PageId,
			array(
				self::TABLE_Hotel => array('uid' => 4, 'title' => 'Testing #1'),
				self::TABLE_Content => array('uid' => self::VALUE_ContentIdFirst, self::FIELD_ContentHotel => '3,4'),
			)
		);
		$this->assertAssertionDataSet('modifyParentRecordWithHotelChildRecord');

		$responseContent = $this->getFrontendResponse(self::VALUE_PageId)->getResponseContent();
		$this->assertResponseContentStructureHasRecords(
			$responseContent, self::TABLE_Content . ':' . self::VALUE_ContentIdFirst, self::FIELD_ContentHotel,
			self::TABLE_Hotel, 'title', array('Hotel #1', 'Testing #1')
		);
	}

	/**
	 * @test
	 */
	public function modifyParentRecordAndAddHotelChildRecord() {
		$this->actionService->modifyRecords(
			self::VALUE_PageId,
			array(
				self::TABLE_Hotel => array('uid' => '__NEW', 'title' => 'Hotel #2'),
				self::TABLE_Content => array('uid' => self::VALUE_ContentIdLast, self::FIELD_ContentHotel => '5,__previousUid'),
			)
		);
		$this->assertAssertionDataSet('modifyParentRecordAndAddHotelChildRecord');

		$responseContent = $this->getFrontendResponse(self::VALUE_PageId)->getResponseContent();
		$this->assertResponseContentStructureHasRecords(
			$responseContent, self::TABLE_Content . ':' . self::VALUE_ContentIdLast, self::FIELD_ContentHotel,
			self::TABLE_Hotel, 'title', array('Hotel #1', 'Hotel #2')
		);
	}

	/**
	 * @test
	 */
	public function modifyParentRecordAndDeleteHotelChildRecord() {
		$this->actionService->modifyRecord(
			self::TABLE_Content,
			self::VALUE_ContentIdFirst,
			array(self::FIELD_ContentHotel => '3'),
			array(self::TABLE_Hotel => array(4))
		);
		$this->assertAssertionDataSet('modifyParentRecordAndDeleteHotelChildRecord');

		$responseContent = $this->getFrontendResponse(self::VALUE_PageId)->getResponseContent();
		$this->assertResponseContentStructureHasRecords(
			$responseContent, self::TABLE_Content . ':' . self::VALUE_ContentIdLast, self::FIELD_ContentHotel,
			self::TABLE_Hotel, 'title', 'Hotel #1'
		);
		$this->assertResponseContentStructureDoesNotHaveRecords(
			$responseContent, self::TABLE_Content . ':' . self::VALUE_ContentIdLast, self::FIELD_ContentHotel,
			self::TABLE_Hotel, 'title', 'Hotel #2'
		);
	}

}
