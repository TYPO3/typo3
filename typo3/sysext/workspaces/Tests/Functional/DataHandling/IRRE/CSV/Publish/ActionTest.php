<?php
namespace TYPO3\CMS\Workspaces\Tests\Functional\DataHandling\IRRE\CSV\Publish;

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

require_once dirname(dirname(__FILE__)) . '/AbstractActionTestCase.php';

/**
 * Functional test for the DataHandler
 */
class ActionTest extends \TYPO3\CMS\Workspaces\Tests\Functional\DataHandling\IRRE\CSV\AbstractActionTestCase {

	/**
	 * @var string
	 */
	protected $assertionDataSetDirectory = 'typo3/sysext/workspaces/Tests/Functional/DataHandling/IRRE/CSV/Publish/DataSet/';

	/**
	 * Parent content records
	 */

	public function setUp() {
		parent::setUp();
	}

	/**
	 * @test
	 * @see DataSet/Assertion/createParentContentRecord.csv
	 */
	public function createParentContent() {
		parent::createParentContent();
		$this->actionService->publishRecord(self::TABLE_Content, $this->recordIds['newContentId']);
		$this->assertAssertionDataSet('createParentContent');

		$responseContent = $this->getFrontendResponse(self::VALUE_PageId, 0)->getResponseContent();
		$this->assertResponseContentHasRecords($responseContent, self::TABLE_Content, 'header', 'Testing #1');
	}

	/**
	 * @test
	 * @see DataSet/Assertion/modifyParentContentRecord.csv
	 */
	public function modifyParentContent() {
		parent::modifyParentContent();
		$this->actionService->publishRecord(self::TABLE_Content, self::VALUE_ContentIdLast);
		$this->assertAssertionDataSet('modifyParentContent');

		$responseContent = $this->getFrontendResponse(self::VALUE_PageId, 0)->getResponseContent();
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
		parent::deleteParentContent();
		$this->actionService->publishRecord(self::TABLE_Content, self::VALUE_ContentIdLast);
		$this->assertAssertionDataSet('deleteParentContent');

		$responseContent = $this->getFrontendResponse(self::VALUE_PageId, 0)->getResponseContent();
		$this->assertResponseContentDoesNotHaveRecords($responseContent, self::TABLE_Content, 'header', 'Regular Element #2');
	}

	/**
	 * @test
	 * @see DataSet/Assertion/deleteParentContentRecordAndDiscardDeletedParentRecord.csv
	 */
	public function deleteParentContentAndDiscardDeletedParent() {
		parent::deleteParentContentAndDiscardDeletedParent();
		// Actually this is not required, since there's nothing to publish... but it's a test case!
		$this->actionService->publishRecord(self::TABLE_Content, self::VALUE_ContentIdLast, FALSE);
		$this->assertAssertionDataSet('deleteParentContentNDiscardDeletedParent');

		$responseContent = $this->getFrontendResponse(self::VALUE_PageId, 0)->getResponseContent();
		$this->assertResponseContentHasRecords($responseContent, self::TABLE_Content, 'header', 'Regular Element #2');
	}

	/**
	 * @test
	 * @see DataSet/Assertion/copyParentContentRecord.csv
	 */
	public function copyParentContent() {
		$this->markTestSkipped('Skipped due to core bugs...');
		parent::copyParentContent();
		$this->actionService->publishRecord(self::TABLE_Content, $this->recordIds['newContentId']);
		$this->assertAssertionDataSet('copyParentContent');

		$responseContent = $this->getFrontendResponse(self::VALUE_PageId, 0)->getResponseContent();
		$this->assertResponseContentStructureHasRecords(
			$responseContent, self::TABLE_Content . ':' . $this->recordIds['newContentId'], self::FIELD_ContentHotel,
			self::TABLE_Hotel, 'title', array('Hotel #1')
		);
	}

	/**
	 * @test
	 * @see DataSet/Assertion/localizeParentContentRecord.csv
	 */
	public function localizeParentContent() {
		$this->markTestSkipped('Skipped due to core bugs...');
		parent::localizeParentContent();
		$this->actionService->publishRecord(self::TABLE_Content, $this->recordIds['localizedContentId']);
		$this->assertAssertionDataSet('localizeParentContent');

		$responseContent = $this->getFrontendResponse(self::VALUE_PageId, self::VALUE_LanguageId)->getResponseContent();
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
		parent::changeParentContentSorting();
		$this->actionService->publishRecord(self::TABLE_Content, self::VALUE_ContentIdFirst);
		$this->assertAssertionDataSet('changeParentContentSorting');

		$responseContent = $this->getFrontendResponse(self::VALUE_PageId, 0)->getResponseContent();

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
		parent::moveParentContentToDifferentPage();
		$this->actionService->publishRecord(self::TABLE_Content, self::VALUE_ContentIdLast);
		$this->assertAssertionDataSet('moveParentContentToDifferentPage');

		$responseContent = $this->getFrontendResponse(self::VALUE_PageIdTarget, 0)->getResponseContent();
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
		parent::moveParentContentToDifferentPageAndChangeSorting();
		$this->actionService->publishRecords(
			array(
				self::TABLE_Content => array(self::VALUE_ContentIdFirst, self::VALUE_ContentIdLast),
			)
		);
		$this->assertAssertionDataSet('moveParentContentToDifferentPageNChangeSorting');

		$responseContent = $this->getFrontendResponse(self::VALUE_PageIdTarget, 0)->getResponseContent();
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
		parent::modifyPage();
		$this->actionService->publishRecord(self::TABLE_Page, self::VALUE_PageId);
		$this->assertAssertionDataSet('modifyPage');

		$responseContent = $this->getFrontendResponse(self::VALUE_PageId, 0)->getResponseContent();
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
		parent::deletePage();
		$this->actionService->publishRecord(self::TABLE_Page, self::VALUE_PageId);
		$this->assertAssertionDataSet('deletePage');

		$response = $this->getFrontendResponse(self::VALUE_PageId, 0, 0, 0, FALSE);
		$this->assertContains('PageNotFoundException', $response->getError());
	}

	/**
	 * @test
	 * @see DataSet/Assertion/copyPageRecord.csv
	 */
	public function copyPage() {
		$this->markTestSkipped('Skipped due to core bugs...');
		parent::copyPage();
		$this->actionService->publishRecord(self::TABLE_Page, $this->recordIds['newPageId']);
		$this->assertAssertionDataSet('copyPage');

		$responseContent = $this->getFrontendResponse($this->recordIds['newPageId'], 0)->getResponseContent();
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
		parent::createParentContentWithHotelAndOfferChildren();
		$this->actionService->publishRecord(self::TABLE_Content, $this->recordIds['newContentId']);
		$this->assertAssertionDataSet('createParentContentNHotelNOfferChildren');

		$responseContent = $this->getFrontendResponse(self::VALUE_PageId, 0)->getResponseContent();
		$this->assertResponseContentHasRecords($responseContent, self::TABLE_Content, 'header', 'Testing #1');

		// @todo Shadow fields are not correct on the new placeholder
		/*
			$this->assertResponseContentStructureHasRecords(
				$responseContent, self::TABLE_Content . ':' . $this->recordIds['newContentId'], self::FIELD_ContentHotel,
				self::TABLE_Hotel, 'title', 'Hotel #1'
			);
		*/
	}

	/**
	 * @test
	 * @see DataSet/Assertion/createAndCopyParentContentRecordWithHotelAndOfferChildRecords.csv
	 */
	public function createAndCopyParentContentWithHotelAndOfferChildren() {
		$this->markTestSkipped('Skipped due to core bugs...');
		parent::createAndCopyParentContentWithHotelAndOfferChildren();
		// @todo Copying the new child records is broken in the Core
		$this->actionService->publishRecord(self::TABLE_Content, $this->recordIds['newContentId']);
		$this->actionService->publishRecord(self::TABLE_Content, $this->recordIds['copiedContentId']);
		$this->assertAssertionDataSet('createNCopyParentContentNHotelNOfferChildren');

		$responseContent = $this->getFrontendResponse(self::VALUE_PageId, 0)->getResponseContent();
		$this->assertResponseContentHasRecords($responseContent, self::TABLE_Content, 'header', 'Testing #1 (copy 1)');

		// @todo Shadow fields are not correct on the new placeholder
		/*
			$this->assertResponseContentStructureHasRecords(
				$responseContent, self::TABLE_Content . ':' . $this->recordIds['newContentId'], self::FIELD_ContentHotel,
				self::TABLE_Hotel, 'title', 'Hotel #1'
			);
			$this->assertResponseContentStructureHasRecords(
				$responseContent, self::TABLE_Content . ':' . $this->recordIds['copiedContentId'], self::FIELD_ContentHotel,
				self::TABLE_Hotel, 'title', 'Hotel #1'
			);
			$this->assertResponseContentStructureHasRecords(
				$responseContent, self::TABLE_Hotel . ':' . $this->recordIds['copiedHotelId'], self::FIELD_HotelOffer,
				self::TABLE_Offer, 'title', 'Offer #1'
			);
		 */
	}

	/**
	 * @test
	 * @see DataSet/Assertion/createAndCopyParentContentRecordWithHotelAndOfferChildRecordsAndDiscardCopiedParentRecord.csv
	 */
	public function createAndCopyParentContentWithHotelAndOfferChildrenAndDiscardCopiedParent() {
		parent::createAndCopyParentContentWithHotelAndOfferChildrenAndDiscardCopiedParent();
		// @todo Copying the new child records is broken in the Core
		$this->actionService->publishRecord(self::TABLE_Content, $this->recordIds['newContentId']);
		// Actually this is not required, since there's nothing to publish... but it's a test case!
		$this->actionService->publishRecord(self::TABLE_Content, $this->recordIds['copiedContentId'], FALSE);
		$this->assertAssertionDataSet('createNCopyParentContentNHotelNOfferChildrenNDiscardCopiedParent');

		$responseContent = $this->getFrontendResponse(self::VALUE_PageId, 0)->getResponseContent();
		$this->assertResponseContentDoesNotHaveRecords($responseContent, self::TABLE_Content, 'header', 'Testing #1 (copy 1)');
	}

	/**
	 * @test
	 * @see DataSet/Assertion/createAndLocalizeParentContentRecordWithHotelAndOfferChildRecords.csv
	 */
	public function createAndLocalizeParentContentWithHotelAndOfferChildren() {
		$this->markTestSkipped('Skipped due to core bugs...');
		parent::createAndLocalizeParentContentWithHotelAndOfferChildren();
		// @todo Localizing the new child records is broken in the Core
		$this->actionService->publishRecord(self::TABLE_Content, $this->recordIds['newContentId']);
		$this->actionService->publishRecord(self::TABLE_Content, $this->recordIds['localizedContentId']);
		$this->assertAssertionDataSet('createNLocalizeParentContentNHotelNOfferChildren');

		$responseContent = $this->getFrontendResponse(self::VALUE_PageId, self::VALUE_LanguageId)->getResponseContent();
		$this->assertResponseContentHasRecords($responseContent, self::TABLE_Content, 'header', '[Translate to Dansk:] Testing #1');

		// @todo Does not work since children don't point to live-default record
		/*
			$this->assertResponseContentStructureHasRecords(
				$responseContent, self::TABLE_Content . ':' . $this->recordIds['localizedContentId'], self::FIELD_ContentHotel,
				self::TABLE_Hotel, 'title', '[Translate to Dansk:] Hotel #1'
			);
			$this->assertResponseContentStructureHasRecords(
				$responseContent, self::TABLE_Hotel . ':' . $this->recordIds['localizedHotelId'], self::FIELD_HotelOffer,
				self::TABLE_Offer, 'title', '[Translate to Dansk:] Offer #1'
			);
		*/
	}

	/**
	 * @test
	 * @see DataSet/Assertion/createAndLocalizeParentContentRecordWithHotelAndOfferChildRecordsAndDiscardLocalizedParentRecord.csv
	 */
	public function createAndLocalizeParentContentWithHotelAndOfferChildrenAndDiscardLocalizedParent() {
		parent::createAndLocalizeParentContentWithHotelAndOfferChildrenAndDiscardLocalizedParent();
		// @todo Localizing the new child records is broken in the Core
		$this->actionService->publishRecord(self::TABLE_Content, $this->recordIds['newContentId']);
		// Actually this is not required, since there's nothing to publish... but it's a test case!
		$this->actionService->publishRecord(self::TABLE_Content, $this->recordIds['localizedContentId'], FALSE);
		$this->assertAssertionDataSet('createNLocalizeParentContentNHotelNOfferChildrenNDiscardLocalizedParent');

		$responseContent = $this->getFrontendResponse(self::VALUE_PageId, self::VALUE_LanguageId)->getResponseContent();
		$this->assertResponseContentDoesNotHaveRecords($responseContent, self::TABLE_Content, 'header', '[Translate to Dansk:] Testing #1');
	}

	/**
	 * @test
	 * @see DataSet/Assertion/modifyOnlyHotelChildRecord.csv
	 */
	public function modifyOnlyHotelChild() {
		parent::modifyOnlyHotelChild();
		$this->actionService->publishRecord(self::TABLE_Hotel, 4);
		$this->assertAssertionDataSet('modifyOnlyHotelChild');

		$responseContent = $this->getFrontendResponse(self::VALUE_PageId, 0)->getResponseContent();
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
		parent::modifyParentAndChangeHotelChildrenSorting();
		$this->actionService->publishRecord(self::TABLE_Content, self::VALUE_ContentIdFirst);
		$this->assertAssertionDataSet('modifyParentNChangeHotelChildrenSorting');

		$responseContent = $this->getFrontendResponse(self::VALUE_PageId, 0)->getResponseContent();

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
		parent::modifyParentWithHotelChild();
		$this->actionService->publishRecord(self::TABLE_Content, self::VALUE_ContentIdFirst);
		$this->assertAssertionDataSet('modifyParentNHotelChild');

		$responseContent = $this->getFrontendResponse(self::VALUE_PageId, 0)->getResponseContent();

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
		$this->markTestSkipped('Skipped due to core bugs...');
		parent::modifyParentWithHotelChildAndDiscardModifiedParent();
		// Actually this is not required, since there's nothing to publish... but it's a test case!
		$this->actionService->publishRecord(self::TABLE_Content, self::VALUE_ContentIdFirst, FALSE);
		$this->assertAssertionDataSet('modifyParentNHotelChildNDiscardModifiedParent');

		$responseContent = $this->getFrontendResponse(self::VALUE_PageId, 0)->getResponseContent();
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
		parent::modifyParentWithHotelChildAndDiscardAll();
		// Actually this is not required, since there's nothing to publish... but it's a test case!
		$this->actionService->publishRecord(self::TABLE_Content, self::VALUE_ContentIdFirst, FALSE);
		$this->assertAssertionDataSet('modifyParentNHotelChildNDiscardAll');

		$responseContent = $this->getFrontendResponse(self::VALUE_PageId, 0)->getResponseContent();
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
		parent::modifyParentAndAddHotelChild();
		$this->actionService->publishRecord(self::TABLE_Content, self::VALUE_ContentIdLast);
		$this->assertAssertionDataSet('modifyParentNAddHotelChild');

		$responseContent = $this->getFrontendResponse(self::VALUE_PageId, 0)->getResponseContent();

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
		parent::modifyParentAndDeleteHotelChild();
		$this->actionService->publishRecord(self::TABLE_Content, self::VALUE_ContentIdFirst);
		$this->assertAssertionDataSet('modifyParentNDeleteHotelChild');

		$responseContent = $this->getFrontendResponse(self::VALUE_PageId, 0)->getResponseContent();
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
