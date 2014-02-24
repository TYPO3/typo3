<?php
namespace TYPO3\CMS\Workspaces\Tests\Functional\DataHandling\IRRE\CSV;

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

require_once __DIR__ . '/../../../../../../core/Tests/Functional/DataHandling/AbstractDataHandlerActionTestCase.php';

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
	const VALUE_WorkspaceId = 1;

	const TABLE_Page = 'pages';
	const TABLE_Content = 'tt_content';
	const TABLE_Hotel = 'tx_irretutorial_1ncsv_hotel';
	const TABLE_Offer = 'tx_irretutorial_1ncsv_offer';

	const FIELD_ContentHotel = 'tx_irretutorial_1ncsv_hotels';
	const FIELD_HotelOffer = 'offers';

	/**
	 * @var string
	 */
	protected $dataSetDirectory = 'typo3/sysext/workspaces/Tests/Functional/DataHandling/IRRE/CSV/DataSet/';

	/**
	 * @var array
	 */
	protected $coreExtensionsToLoad = array(
		'version',
		'workspaces',
	);

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
	 * @see DataSet/Assertion/createParentContentRecord.csv
	 */
	public function createParentContent() {
		$this->actionService->createNewRecord(self::TABLE_Content, self::VALUE_PageId, array('header' => 'Testing #1'));
		$this->assertAssertionDataSet('createParentContent');

		$responseContent = $this->getFrontendResponse(self::VALUE_PageId, 0, self::VALUE_BackendUserId, self::VALUE_WorkspaceId)->getResponseContent();
		$this->assertResponseContentHasRecords($responseContent, self::TABLE_Content, 'header', 'Testing #1');
	}

	/**
	 * @test
	 * @see DataSet/Assertion/modifyParentContentRecord.csv
	 */
	public function modifyParentContent() {
		$this->actionService->modifyRecord(self::TABLE_Content, self::VALUE_ContentIdLast, array('header' => 'Testing #1'));
		$this->assertAssertionDataSet('modifyParentContent');

		$responseContent = $this->getFrontendResponse(self::VALUE_PageId, 0, self::VALUE_BackendUserId, self::VALUE_WorkspaceId)->getResponseContent();
		$this->assertResponseContentHasRecords($responseContent, self::TABLE_Content, 'header', 'Testing #1');

		// @todo Cannot direct select workspace version due to frontend SQL query
		// SELECT * FROM tx_irretutorial_1ncsv_hotel
		// WHERE tx_irretutorial_1ncsv_hotel.uid=6 AND tx_irretutorial_1ncsv_hotel.pid IN (89)
		// AND tx_irretutorial_1ncsv_hotel.deleted=0 AND (tx_irretutorial_1ncsv_hotel.t3ver_wsid=0 OR tx_irretutorial_1ncsv_hotel.t3ver_wsid=1)
		// AND tx_irretutorial_1ncsv_hotel.pid<>-1 ORDER BY sorting
		/*
			$this->assertResponseContentStructureHasRecords(
				$responseContent, self::TABLE_Content . ':' . self::VALUE_ContentIdLast, self::FIELD_ContentHotel,
				self::TABLE_Hotel, 'title', array('Hotel #1')
			);
		*/
	}

	/**
	 * @test
	 * @see DataSet/Assertion/deleteParentContentRecord.csv
	 */
	public function deleteParentContent() {
		$this->actionService->deleteRecord(self::TABLE_Content, self::VALUE_ContentIdLast);
		$this->assertAssertionDataSet('deleteParentContent');

		$responseContent = $this->getFrontendResponse(self::VALUE_PageId, 0, self::VALUE_BackendUserId, self::VALUE_WorkspaceId)->getResponseContent();
		$this->assertResponseContentDoesNotHaveRecords($responseContent, self::TABLE_Content, 'header', 'Regular Element #2');
	}

	/**
	 * @test
	 * @see DataSet/Assertion/deleteParentContentRecordAndDiscardDeletedParentRecord.csv
	 */
	public function deleteParentContentAndDiscardDeletedParent() {
		$newTableIds = $this->actionService->deleteRecord(self::TABLE_Content, self::VALUE_ContentIdLast);
		$versionedDeletedContentId = $newTableIds[self::TABLE_Content][self::VALUE_ContentIdLast];
		$this->actionService->clearWorkspaceRecord(self::TABLE_Content, $versionedDeletedContentId);
		$this->assertAssertionDataSet('deleteParentContentNDiscardDeletedParent');

		$responseContent = $this->getFrontendResponse(self::VALUE_PageId, 0, self::VALUE_BackendUserId, self::VALUE_WorkspaceId)->getResponseContent();
		$this->assertResponseContentHasRecords($responseContent, self::TABLE_Content, 'header', 'Regular Element #2');
	}

	/**
	 * @test
	 * @see DataSet/Assertion/copyParentContentRecord.csv
	 */
	public function copyParentContent() {
		$newTableIds = $this->actionService->copyRecord(self::TABLE_Content, self::VALUE_ContentIdLast, self::VALUE_PageId);
		$this->assertAssertionDataSet('copyParentContent');

		$newContentId = $newTableIds[self::TABLE_Content][self::VALUE_ContentIdLast];
		$responseContent = $this->getFrontendResponse(self::VALUE_PageId, 0, self::VALUE_BackendUserId, self::VALUE_WorkspaceId)->getResponseContent();
		$this->assertResponseContentStructureHasRecords(
			$responseContent, self::TABLE_Content . ':' . $newContentId, self::FIELD_ContentHotel,
			self::TABLE_Hotel, 'title', array('Hotel #1')
		);
	}

	/**
	 * @test
	 * @see DataSet/Assertion/localizeParentContentRecord.csv
	 */
	public function localizeParentContent() {
		$this->actionService->localizeRecord(self::TABLE_Content, self::VALUE_ContentIdLast, self::VALUE_LanguageId);
		$this->assertAssertionDataSet('localizeParentContent');

		$responseContent = $this->getFrontendResponse(self::VALUE_PageId, self::VALUE_LanguageId, self::VALUE_BackendUserId, self::VALUE_WorkspaceId)->getResponseContent();
		$this->assertResponseContentStructureHasRecords(
			$responseContent, self::TABLE_Content . ':' . self::VALUE_ContentIdLast, self::FIELD_ContentHotel,
			self::TABLE_Hotel, 'title', array('[Translate to Dansk:] Hotel #1')
		);
	}

	/**
	 * @test
	 * @see DataSet/Assertion/changeParentContentRecordSorting.csv
	 */
	public function changeParentContentSorting() {
		$this->actionService->moveRecord(self::TABLE_Content, self::VALUE_ContentIdFirst, -self::VALUE_ContentIdLast);
		$this->assertAssertionDataSet('changeParentContentSorting');

		$responseContent = $this->getFrontendResponse(self::VALUE_PageId, 0, self::VALUE_BackendUserId, self::VALUE_WorkspaceId)->getResponseContent();

		// @todo Cannot direct select workspace version due to frontend SQL query
		// SELECT * FROM tx_irretutorial_1ncsv_hotel
		// WHERE tx_irretutorial_1ncsv_hotel.uid=6 AND tx_irretutorial_1ncsv_hotel.pid IN (89)
		// AND tx_irretutorial_1ncsv_hotel.deleted=0 AND (tx_irretutorial_1ncsv_hotel.t3ver_wsid=0 OR tx_irretutorial_1ncsv_hotel.t3ver_wsid=1)
		// AND tx_irretutorial_1ncsv_hotel.pid<>-1 ORDER BY sorting
		/*
			$this->assertResponseContentStructureHasRecords(
				$responseContent, self::TABLE_Content . ':' . self::VALUE_ContentIdFirst, self::FIELD_ContentHotel,
				self::TABLE_Hotel, 'title', array('Hotel #1', 'Hotel #2')
			);
			$this->assertResponseContentStructureHasRecords(
				$responseContent, self::TABLE_Content . ':' . self::VALUE_ContentIdLast, self::FIELD_ContentHotel,
				self::TABLE_Hotel, 'title', array('Hotel #1')
			);
		*/
	}

	/**
	 * @test
	 * @see DataSet/Assertion/moveParentContentRecordToDifferentPage.csv
	 */
	public function moveParentContentToDifferentPage() {
		$this->actionService->moveRecord(self::TABLE_Content, self::VALUE_ContentIdLast, self::VALUE_PageIdTarget);
		$this->assertAssertionDataSet('moveParentContentToDifferentPage');

		$responseContent = $this->getFrontendResponse(self::VALUE_PageIdTarget, 0, self::VALUE_BackendUserId, self::VALUE_WorkspaceId)->getResponseContent();
		$this->assertResponseContentHasRecords($responseContent, self::TABLE_Content, 'header', 'Regular Element #2');

		// @todo Workspace child records gets lost due to core bug
		/*
		$this->assertResponseContentStructureHasRecords(
			$responseContent, self::TABLE_Content . ':' . self::VALUE_ContentIdLast, self::FIELD_ContentHotel,
			self::TABLE_Hotel, 'title', array('Hotel #1')
		);
		*/
	}

	/**
	 * @test
	 * @see DataSet/Assertion/moveParentContentRecordToDifferentPageAndChangeSorting.csv
	 */
	public function moveParentContentToDifferentPageAndChangeSorting() {
		$this->actionService->moveRecord(self::TABLE_Content, self::VALUE_ContentIdLast, self::VALUE_PageIdTarget);
		$this->actionService->moveRecord(self::TABLE_Content, self::VALUE_ContentIdFirst, -self::VALUE_ContentIdLast);
		$this->assertAssertionDataSet('moveParentContentToDifferentPageNChangeSorting');

		$responseContent = $this->getFrontendResponse(self::VALUE_PageIdTarget, 0, self::VALUE_BackendUserId, self::VALUE_WorkspaceId)->getResponseContent();
		$this->assertResponseContentHasRecords($responseContent, self::TABLE_Content, 'header', array('Regular Element #2', 'Regular Element #1'));

		// @todo Cannot direct select workspace version due to frontend SQL query
		// SELECT * FROM tx_irretutorial_1ncsv_hotel
		// WHERE tx_irretutorial_1ncsv_hotel.uid=6 AND tx_irretutorial_1ncsv_hotel.pid IN (89)
		// AND tx_irretutorial_1ncsv_hotel.deleted=0 AND (tx_irretutorial_1ncsv_hotel.t3ver_wsid=0 OR tx_irretutorial_1ncsv_hotel.t3ver_wsid=1)
		// AND tx_irretutorial_1ncsv_hotel.pid<>-1 ORDER BY sorting
		/*
			$this->assertResponseContentStructureHasRecords(
				$responseContent, self::TABLE_Content . ':' . self::VALUE_ContentIdFirst, self::FIELD_ContentHotel,
				self::TABLE_Hotel, 'title', array('Hotel #1', 'Hotel #2')
			);
			$this->assertResponseContentStructureHasRecords(
				$responseContent, self::TABLE_Content . ':' . self::VALUE_ContentIdLast, self::FIELD_ContentHotel,
				self::TABLE_Hotel, 'title', array('Hotel #1')
			);
		*/
	}

	/**
	 * Page records
	 */

	/**
	 * @test
	 * @see DataSet/Assertion/modifyPageRecord.csv
	 */
	public function modifyPage() {
		$this->actionService->modifyRecord(self::TABLE_Page, self::VALUE_PageId, array('title' => 'Testing #1'));
		$this->assertAssertionDataSet('modifyPage');

		$responseContent = $this->getFrontendResponse(self::VALUE_PageId, 0, self::VALUE_BackendUserId, self::VALUE_WorkspaceId)->getResponseContent();
		$this->assertResponseContentHasRecords($responseContent, self::TABLE_Page, 'title', 'Testing #1');
		$this->assertResponseContentStructureHasRecords(
			$responseContent, self::TABLE_Content . ':' . self::VALUE_ContentIdFirst, self::FIELD_ContentHotel,
			self::TABLE_Hotel, 'title', array('Hotel #1', 'Hotel #2')
		);
	}

	/**
	 * @test
	 * @see DataSet/Assertion/deletePageRecord.csv
	 */
	public function deletePage() {
		$this->actionService->deleteRecord(self::TABLE_Page, self::VALUE_PageId);
		$this->assertAssertionDataSet('deletePage');

		$response = $this->getFrontendResponse(self::VALUE_PageId, 0, self::VALUE_BackendUserId, self::VALUE_WorkspaceId, FALSE);
		$this->assertContains('RuntimeException', $response->getError());
	}

	/**
	 * @test
	 * @see DataSet/Assertion/copyPageRecord.csv
	 */
	public function copyPage() {
		$newTableIds = $this->actionService->copyRecord(self::TABLE_Page, self::VALUE_PageId, self::VALUE_PageIdTarget);
		$this->assertAssertionDataSet('copyPage');

		$newPageId = $newTableIds[self::TABLE_Page][self::VALUE_PageId];
		$responseContent = $this->getFrontendResponse($newPageId, 0, self::VALUE_BackendUserId, self::VALUE_WorkspaceId)->getResponseContent();
		$this->assertResponseContentHasRecords($responseContent, self::TABLE_Hotel, 'title', array('Hotel #1', 'Hotel #2', 'Hotel #1'));
	}

	/**
	 * IRRE Child Records
	 */

	/**
	 * @test
	 * @see DataSet/Assertion/createParentContentRecordWithHotelAndOfferChildRecords.csv
	 */
	public function createParentContentWithHotelAndOfferChildren() {
		$newTableIds = $this->actionService->createNewRecords(
			self::VALUE_PageId,
			array(
				self::TABLE_Offer => array('title' => 'Offer #1'),
				self::TABLE_Hotel => array('title' => 'Hotel #1', self::FIELD_HotelOffer => '__previousUid'),
				self::TABLE_Content => array('header' => 'Testing #1', self::FIELD_ContentHotel => '__previousUid'),
			)
		);
		$this->assertAssertionDataSet('createParentContentNHotelNOfferChildren');

		$newContentId = $newTableIds[self::TABLE_Content][0];
		$responseContent = $this->getFrontendResponse(self::VALUE_PageId, 0, self::VALUE_BackendUserId, self::VALUE_WorkspaceId)->getResponseContent();
		$this->assertResponseContentHasRecords($responseContent, self::TABLE_Content, 'header', 'Testing #1');

		// @todo Shadow fields are not correct on the new placeholder
		/*
			$this->assertResponseContentStructureHasRecords(
				$responseContent, self::TABLE_Content . ':' . $newContentId, self::FIELD_ContentHotel,
				self::TABLE_Hotel, 'title', 'Hotel #1'
			);
		*/
	}

	/**
	 * @test
	 * @see DataSet/Assertion/createAndCopyParentContentRecordWithHotelAndOfferChildRecords.csv
	 */
	public function createAndCopyParentContentWithHotelAndOfferChildren() {
		// @todo Copying the new child records is broken in the Core
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
		$this->assertAssertionDataSet('createNCopyParentContentNHotelNOfferChildren');

		$copiedContentId = $copiedTableIds[self::TABLE_Content][$newContentId];
		$copiedHotelId = $copiedTableIds[self::TABLE_Hotel][$newHotelId];
		$responseContent = $this->getFrontendResponse(self::VALUE_PageId, 0, self::VALUE_BackendUserId, self::VALUE_WorkspaceId)->getResponseContent();
		$this->assertResponseContentHasRecords($responseContent, self::TABLE_Content, 'header', 'Testing #1 (copy 1)');

		// @todo Shadow fields are not correct on the new placeholder
		/*
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
		 */
	}

	/**
	 * @test
	 * @see DataSet/Assertion/createAndCopyParentContentRecordWithHotelAndOfferChildRecordsAndDiscardCopiedParentRecord.csv
	 */
	public function createAndCopyParentContentWithHotelAndOfferChildrenAndDiscardCopiedParent() {
		// @todo Copying the new child records is broken in the Core
		$newTableIds = $this->actionService->createNewRecords(
			self::VALUE_PageId,
			array(
				self::TABLE_Offer => array('title' => 'Offer #1'),
				self::TABLE_Hotel => array('title' => 'Hotel #1', self::FIELD_HotelOffer => '__previousUid'),
				self::TABLE_Content => array('header' => 'Testing #1', self::FIELD_ContentHotel => '__previousUid'),
			)
		);
		$newContentId = $newTableIds['tt_content'][0];
		$copiedTableIds = $this->actionService->copyRecord(self::TABLE_Content, $newContentId, self::VALUE_PageId);
		$copiedContentId = $copiedTableIds[self::TABLE_Content][$newContentId];
		$versionedCopiedContentId = $this->actionService->getDataHander()->getAutoVersionId(self::TABLE_Content, $copiedContentId);
		$this->actionService->clearWorkspaceRecord(self::TABLE_Content, $versionedCopiedContentId);
		$this->assertAssertionDataSet('createNCopyParentContentNHotelNOfferChildrenNDiscardCopiedParent');

		$responseContent = $this->getFrontendResponse(self::VALUE_PageId, 0, self::VALUE_BackendUserId, self::VALUE_WorkspaceId)->getResponseContent();
		$this->assertResponseContentDoesNotHaveRecords($responseContent, self::TABLE_Content, 'header', 'Testing #1 (copy 1)');
	}

	/**
	 * @test
	 * @see DataSet/Assertion/createAndLocalizeParentContentRecordWithHotelAndOfferChildRecords.csv
	 */
	public function createAndLocalizeParentContentWithHotelAndOfferChildren() {
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
		$this->assertAssertionDataSet('createNLocalizeParentContentNHotelNOfferChildren');

		$localizedContentId = $localizedTableIds[self::TABLE_Content][$newContentId];
		$localizedHotelId = $localizedTableIds[self::TABLE_Hotel][$newHotelId];
		$responseContent = $this->getFrontendResponse(self::VALUE_PageId, self::VALUE_LanguageId, self::VALUE_BackendUserId, self::VALUE_WorkspaceId)->getResponseContent();
		$this->assertResponseContentHasRecords($responseContent, self::TABLE_Content, 'header', '[Translate to Dansk:] Testing #1');

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
	 * @see DataSet/Assertion/createAndLocalizeParentContentRecordWithHotelAndOfferChildRecordsAndDiscardLocalizedParentRecord.csv
	 */
	public function createAndLocalizeParentContentWithHotelAndOfferChildrenAndDiscardLocalizedParent() {
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
		$localizedTableIds = $this->actionService->localizeRecord(self::TABLE_Content, $newContentId, self::VALUE_LanguageId);
		$localizedContentId = $localizedTableIds[self::TABLE_Content][$newContentId];
		$versionedLocalizedContentId = $this->actionService->getDataHander()->getAutoVersionId(self::TABLE_Content, $localizedContentId);
		$this->actionService->clearWorkspaceRecord(self::TABLE_Content, $versionedLocalizedContentId);
		$this->assertAssertionDataSet('createNLocalizeParentContentNHotelNOfferChildrenNDiscardLocalizedParent');

		$responseContent = $this->getFrontendResponse(self::VALUE_PageId, self::VALUE_LanguageId, self::VALUE_BackendUserId, self::VALUE_WorkspaceId)->getResponseContent();
		$this->assertResponseContentDoesNotHaveRecords($responseContent, self::TABLE_Content, 'header', '[Translate to Dansk:] Testing #1');
	}

	/**
	 * @test
	 * @see DataSet/Assertion/modifyOnlyHotelChildRecord.csv
	 */
	public function modifyOnlyHotelChild() {
		$this->actionService->modifyRecord(self::TABLE_Hotel, 4, array('title' => 'Testing #1'));
		$this->assertAssertionDataSet('modifyOnlyHotelChild');

		$responseContent = $this->getFrontendResponse(self::VALUE_PageId, 0, self::VALUE_BackendUserId, self::VALUE_WorkspaceId)->getResponseContent();
		$this->assertResponseContentStructureHasRecords(
			$responseContent, self::TABLE_Content . ':' . self::VALUE_ContentIdFirst, self::FIELD_ContentHotel,
			self::TABLE_Hotel, 'title', array('Hotel #1', 'Testing #1')
		);
	}

	/**
	 * @test
	 * @see DataSet/Assertion/modifyParentRecordAndChangeHotelChildRecordsSorting.csv
	 */
	public function modifyParentAndChangeHotelChildrenSorting() {
		$this->actionService->modifyRecord(self::TABLE_Content, self::VALUE_ContentIdFirst, array(self::FIELD_ContentHotel => '4,3'));
		$this->assertAssertionDataSet('modifyParentNChangeHotelChildrenSorting');

		$responseContent = $this->getFrontendResponse(self::VALUE_PageId, 0, self::VALUE_BackendUserId, self::VALUE_WorkspaceId)->getResponseContent();

		// @todo Cannot direct select workspace version due to frontend SQL query
		// SELECT * FROM tx_irretutorial_1ncsv_hotel
		// WHERE tx_irretutorial_1ncsv_hotel.uid=6 AND tx_irretutorial_1ncsv_hotel.pid IN (89)
		// AND tx_irretutorial_1ncsv_hotel.deleted=0 AND (tx_irretutorial_1ncsv_hotel.t3ver_wsid=0 OR tx_irretutorial_1ncsv_hotel.t3ver_wsid=1)
		// AND tx_irretutorial_1ncsv_hotel.pid<>-1 ORDER BY sorting
		/*
			$this->assertResponseContentStructureHasRecords(
				$responseContent, self::TABLE_Content . ':' . self::VALUE_ContentIdFirst, self::FIELD_ContentHotel,
				self::TABLE_Hotel, 'title', array('Hotel #2', 'Hotel #1')
			);
		*/
	}

	/**
	 * @test
	 * @see DataSet/Assertion/modifyParentRecordWithHotelChildRecord.csv
	 */
	public function modifyParentWithHotelChild() {
		$this->actionService->modifyRecords(
			self::VALUE_PageId,
			array(
				self::TABLE_Hotel => array('uid' => 4, 'title' => 'Testing #1'),
				self::TABLE_Content => array('uid' => self::VALUE_ContentIdFirst, self::FIELD_ContentHotel => '3,4'),
			)
		);
		$this->assertAssertionDataSet('modifyParentNHotelChild');

		$responseContent = $this->getFrontendResponse(self::VALUE_PageId, 0, self::VALUE_BackendUserId, self::VALUE_WorkspaceId)->getResponseContent();

		// @todo Cannot direct select workspace version due to frontend SQL query
		// SELECT * FROM tx_irretutorial_1ncsv_hotel
		// WHERE tx_irretutorial_1ncsv_hotel.uid=6 AND tx_irretutorial_1ncsv_hotel.pid IN (89)
		// AND tx_irretutorial_1ncsv_hotel.deleted=0 AND (tx_irretutorial_1ncsv_hotel.t3ver_wsid=0 OR tx_irretutorial_1ncsv_hotel.t3ver_wsid=1)
		// AND tx_irretutorial_1ncsv_hotel.pid<>-1 ORDER BY sorting
		/*
			$this->assertResponseContentStructureHasRecords(
				$responseContent, self::TABLE_Content . ':' . self::VALUE_ContentIdFirst, self::FIELD_ContentHotel,
				self::TABLE_Hotel, 'title', array('Hotel #1', 'Testing #1')
			);
		*/
	}

	/**
	 * @test
	 * @see DataSet/Assertion/modifyParentRecordWithHotelChildRecordAndDiscardModifiedParentRecord.csv
	 */
	public function modifyParentWithHotelChildAndDiscardModifiedParent() {
		$this->actionService->modifyRecords(
			self::VALUE_PageId,
			array(
				self::TABLE_Hotel => array('uid' => 4, 'title' => 'Testing #1'),
				self::TABLE_Content => array('uid' => self::VALUE_ContentIdFirst, self::FIELD_ContentHotel => '3,4'),
			)
		);
		$modifiedContentId = $this->actionService->getDataHander()->getAutoVersionId(self::TABLE_Content, self::VALUE_ContentIdFirst);
		$this->actionService->clearWorkspaceRecord(self::TABLE_Content, $modifiedContentId);
		$this->assertAssertionDataSet('modifyParentNHotelChildNDiscardModifiedParent');

		$responseContent = $this->getFrontendResponse(self::VALUE_PageId, 0, self::VALUE_BackendUserId, self::VALUE_WorkspaceId)->getResponseContent();
		$this->assertResponseContentHasRecords($responseContent, self::TABLE_Content, 'header', 'Regular Element #1');
		$this->assertResponseContentStructureHasRecords(
			$responseContent, self::TABLE_Content . ':' . self::VALUE_ContentIdFirst, self::FIELD_ContentHotel,
			self::TABLE_Hotel, 'title', array('Hotel #1', 'Testing #1')
			// @todo Discarding the parent record should discard the child records as well
			// self::TABLE_Hotel, 'title', array('Hotel #1', 'Hotel #2')
		);
		/*
			$this->assertResponseContentStructureDoesNotHaveRecords(
				$responseContent, self::TABLE_Content . ':' . self::VALUE_ContentIdFirst, self::FIELD_ContentHotel,
				self::TABLE_Hotel, 'title', 'Testing #1'
			);
		*/
	}

	/**
	 * @test
	 * @see DataSet/Assertion/modifyParentRecordWithHotelChildRecordAndDiscardAllModifiedRecords.csv
	 */
	public function modifyParentWithHotelChildAndDiscardAll() {
		$this->actionService->modifyRecords(
			self::VALUE_PageId,
			array(
				self::TABLE_Hotel => array('uid' => 4, 'title' => 'Testing #1'),
				self::TABLE_Content => array('uid' => self::VALUE_ContentIdFirst, self::FIELD_ContentHotel => '3,4'),
			)
		);
		$modifiedContentId = $this->actionService->getDataHander()->getAutoVersionId(self::TABLE_Content, self::VALUE_ContentIdFirst);
		$modifiedHotelId = $this->actionService->getDataHander()->getAutoVersionId(self::TABLE_Hotel, 4);
		$this->actionService->clearWorkspaceRecords(
				array(
					self::TABLE_Hotel => array($modifiedHotelId),
					self::TABLE_Content => array($modifiedContentId),
				)
		);
		$this->assertAssertionDataSet('modifyParentNHotelChildNDiscardAll');

		$responseContent = $this->getFrontendResponse(self::VALUE_PageId, 0, self::VALUE_BackendUserId, self::VALUE_WorkspaceId)->getResponseContent();
		$this->assertResponseContentHasRecords($responseContent, self::TABLE_Content, 'header', 'Regular Element #1');
		$this->assertResponseContentStructureHasRecords(
			$responseContent, self::TABLE_Content . ':' . self::VALUE_ContentIdFirst, self::FIELD_ContentHotel,
			self::TABLE_Hotel, 'title', array('Hotel #1', 'Hotel #2')
		);
	}

	/**
	 * @test
	 * @see DataSet/Assertion/modifyParentRecordAndAddHotelChildRecord.csv
	 */
	public function modifyParentAndAddHotelChild() {
		$this->actionService->modifyRecords(
			self::VALUE_PageId,
			array(
				self::TABLE_Hotel => array('uid' => '__NEW', 'title' => 'Hotel #2'),
				self::TABLE_Content => array('uid' => self::VALUE_ContentIdLast, self::FIELD_ContentHotel => '5,__previousUid'),
			)
		);
		$this->assertAssertionDataSet('modifyParentNAddHotelChild');

		$responseContent = $this->getFrontendResponse(self::VALUE_PageId, 0, self::VALUE_BackendUserId, self::VALUE_WorkspaceId)->getResponseContent();

		// @todo Child record cannot be selected since they do not point to the live record
		/*
			$this->assertResponseContentStructureHasRecords(
				$responseContent, self::TABLE_Content . ':' . self::VALUE_ContentIdLast, self::FIELD_ContentHotel,
				self::TABLE_Hotel, 'title', array('Hotel #1', 'Hotel #2')
			);
		*/
	}

	/**
	 * @test
	 * @see DataSet/Assertion/modifyParentRecordAndDeleteHotelChildRecord.csv
	 */
	public function modifyParentAndDeleteHotelChild() {
		$this->actionService->modifyRecord(
			self::TABLE_Content,
			self::VALUE_ContentIdFirst,
			array(self::FIELD_ContentHotel => '3'),
			array(self::TABLE_Hotel => array(4))
		);
		$this->assertAssertionDataSet('modifyParentNDeleteHotelChild');

		$responseContent = $this->getFrontendResponse(self::VALUE_PageId, 0, self::VALUE_BackendUserId, self::VALUE_WorkspaceId)->getResponseContent();
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
