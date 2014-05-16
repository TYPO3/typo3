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
		$this->assertResponseContentStructureHasRecords(
			$responseContent, self::TABLE_Content . ':' . self::VALUE_ContentIdLast, self::FIELD_ContentHotel,
			self::TABLE_Hotel, 'title', array('Hotel #1')
		);
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
	 * @see DataSet/copyParentContentToDifferentPage.csv
	 */
	public function copyParentContentToDifferentPage() {
		parent::copyParentContentToDifferentPage();
		$this->actionService->publishRecord(self::TABLE_Content, $this->recordIds['newContentId']);
		$this->assertAssertionDataSet('copyParentContentToDifferentPage');

		$responseContent = $this->getFrontendResponse(self::VALUE_PageIdTarget, 0)->getResponseContent();
		$this->assertResponseContentStructureHasRecords(
			$responseContent, self::TABLE_Content . ':' . $this->recordIds['newContentId'], self::FIELD_ContentHotel,
			self::TABLE_Hotel, 'title', array('Hotel #1')
		);
	}

	/**
	 * @test
	 * @see DataSet/localizeParentContentKeep.csv
	 */
	public function localizeParentContentInKeepMode() {
		parent::localizeParentContentInKeepMode();
		$this->actionService->publishRecord(self::TABLE_Content, $this->recordIds['localizedContentId']);
		$this->assertAssertionDataSet('localizeParentContentKeep');

		$responseContent = $this->getFrontendResponse(self::VALUE_PageId, self::VALUE_LanguageId)->getResponseContent();
		$this->assertResponseContentStructureDoesNotHaveRecords(
			$responseContent, self::TABLE_Content . ':' . self::VALUE_ContentIdLast, self::FIELD_ContentHotel,
			self::TABLE_Hotel, 'title', array('[Translate to Dansk:] Hotel #1')
		);
	}

	/**
	 * @test
	 * @see DataSet/localizeParentContentWAllChildrenKeep.csv
	 */
	public function localizeParentContentWithAllChildrenInKeepMode() {
		parent::localizeParentContentWithAllChildrenInKeepMode();
		$this->actionService->publishRecord(self::TABLE_Content, $this->recordIds['localizedContentId']);
		$this->assertAssertionDataSet('localizeParentContentWAllChildrenKeep');

		$responseContent = $this->getFrontendResponse(self::VALUE_PageId, self::VALUE_LanguageId)->getResponseContent();
		$this->assertResponseContentStructureDoesNotHaveRecords(
			$responseContent, self::TABLE_Content . ':' . self::VALUE_ContentIdLast, self::FIELD_ContentHotel,
			self::TABLE_Hotel, 'title', array('[Translate to Dansk:] Hotel #1')
		);
	}

	/**
	 * @test
	 * @see DataSet/localizeParentContentSelect.csv
	 */
	public function localizeParentContentInSelectMode() {
		parent::localizeParentContentInSelectMode();
		$this->actionService->publishRecord(self::TABLE_Content, $this->recordIds['localizedContentId']);
		$this->assertAssertionDataSet('localizeParentContentSelect');

		$responseContent = $this->getFrontendResponse(self::VALUE_PageId, self::VALUE_LanguageId)->getResponseContent();
		$this->assertResponseContentStructureDoesNotHaveRecords(
			$responseContent, self::TABLE_Content . ':' . self::VALUE_ContentIdLast, self::FIELD_ContentHotel,
			self::TABLE_Hotel, 'title', array('[Translate to Dansk:] Hotel #1')
		);
	}

	/**
	 * @test
	 * @see DataSet/localizeParentContentWAllChildrenSelect.csv
	 */
	public function localizeParentContentWithAllChildrenInSelectMode() {
		parent::localizeParentContentWithAllChildrenInSelectMode();
		$this->actionService->publishRecord(self::TABLE_Content, $this->recordIds['localizedContentId']);
		$this->assertAssertionDataSet('localizeParentContentWAllChildrenSelect');

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
	 * @see DataSet/Assertion/moveParentContentRecordToDifferentPage.csv
	 */
	public function moveParentContentToDifferentPage() {
		parent::moveParentContentToDifferentPage();
		$this->actionService->publishRecord(self::TABLE_Content, self::VALUE_ContentIdLast);
		$this->assertAssertionDataSet('moveParentContentToDifferentPage');

		$responseContent = $this->getFrontendResponse(self::VALUE_PageIdTarget, 0)->getResponseContent();
		$this->assertResponseContentHasRecords($responseContent, self::TABLE_Content, 'header', 'Regular Element #2');
		$this->assertResponseContentStructureHasRecords(
			$responseContent, self::TABLE_Content . ':' . self::VALUE_ContentIdLast, self::FIELD_ContentHotel,
			self::TABLE_Hotel, 'title', array('Hotel #1')
		);
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
		parent::copyPage();
		$this->actionService->publishRecords(
			array(
				self::TABLE_Page => array($this->recordIds['newPageId']),
				self::TABLE_Content => array($this->recordIds['newContentIdFirst'], $this->recordIds['newContentIdLast']),
			)
		);
		$this->assertAssertionDataSet('copyPage');

		$responseContent = $this->getFrontendResponse($this->recordIds['newPageId'], 0)->getResponseContent();
		$this->assertResponseContentHasRecords($responseContent, self::TABLE_Hotel, 'title', array('Hotel #1', 'Hotel #2', 'Hotel #1'));
	}

	/**
	 * @test
	 * @see DataSet/Assertion/copyPageWHotelBeforeParentContent.csv
	 */
	public function copyPageWithHotelBeforeParentContent() {
		parent::copyPageWithHotelBeforeParentContent();
		$this->actionService->publishRecords(
			array(
				self::TABLE_Page => array($this->recordIds['newPageId']),
				self::TABLE_Content => array($this->recordIds['newContentIdFirst'], $this->recordIds['newContentIdLast']),
			)
		);
		$this->assertAssertionDataSet('copyPageWHotelBeforeParentContent');

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
		$this->assertResponseContentStructureHasRecords(
			$responseContent, self::TABLE_Content . ':' . $this->recordIds['newContentId'], self::FIELD_ContentHotel,
			self::TABLE_Hotel, 'title', 'Hotel #1'
		);
	}

	/**
	 * @test
	 * @see DataSet/Assertion/createAndCopyParentContentRecordWithHotelAndOfferChildRecords.csv
	 */
	public function createAndCopyParentContentWithHotelAndOfferChildren() {
		parent::createAndCopyParentContentWithHotelAndOfferChildren();
		$this->actionService->publishRecord(self::TABLE_Content, $this->recordIds['newContentId']);
		$this->actionService->publishRecord(self::TABLE_Content, $this->recordIds['copiedContentId']);
		$this->assertAssertionDataSet('createNCopyParentContentNHotelNOfferChildren');

		$responseContent = $this->getFrontendResponse(self::VALUE_PageId, 0)->getResponseContent();
		$this->assertResponseContentHasRecords($responseContent, self::TABLE_Content, 'header', 'Testing #1 (copy 1)');
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
	}

	/**
	 * @test
	 * @see DataSet/Assertion/createAndCopyParentContentRecordWithHotelAndOfferChildRecordsAndDiscardCopiedParentRecord.csv
	 */
	public function createAndCopyParentContentWithHotelAndOfferChildrenAndDiscardCopiedParent() {
		parent::createAndCopyParentContentWithHotelAndOfferChildrenAndDiscardCopiedParent();
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
		parent::createAndLocalizeParentContentWithHotelAndOfferChildren();
		$this->actionService->publishRecord(self::TABLE_Content, $this->recordIds['newContentId']);
		$this->actionService->publishRecord(self::TABLE_Content, $this->recordIds['localizedContentId']);
		$this->assertAssertionDataSet('createNLocalizeParentContentNHotelNOfferChildren');

		$responseContent = $this->getFrontendResponse(self::VALUE_PageId, self::VALUE_LanguageId)->getResponseContent();
		$this->assertResponseContentHasRecords($responseContent, self::TABLE_Content, 'header', '[Translate to Dansk:] Testing #1');
		$this->assertResponseContentStructureHasRecords(
			$responseContent, self::TABLE_Content . ':' . $this->recordIds['newContentId'], self::FIELD_ContentHotel,
			self::TABLE_Hotel, 'title', '[Translate to Dansk:] Hotel #1'
		);
		$this->assertResponseContentStructureHasRecords(
			$responseContent, self::TABLE_Hotel . ':' . $this->recordIds['localizedHotelId'], self::FIELD_HotelOffer,
			self::TABLE_Offer, 'title', '[Translate to Dansk:] Offer #1'
		);
	}

	/**
	 * @test
	 * @see DataSet/Assertion/createNLocalizeParentContentNHotelNOfferChildrenNDiscardCreatedParent.csv
	 */
	public function createAndLocalizeParentContentWithHotelAndOfferChildrenAndDiscardCreatedParent() {
		parent::createAndLocalizeParentContentWithHotelAndOfferChildrenAndDiscardCreatedParent();
		// Actually this is not required, since there's nothing to publish... but it's a test case!
		$this->actionService->publishRecord(self::TABLE_Content, $this->recordIds['newContentId'], FALSE);
		$this->assertAssertionDataSet('createNLocalizeParentContentNHotelNOfferChildrenNDiscardCreatedParent');

		$responseContent = $this->getFrontendResponse(self::VALUE_PageId, self::VALUE_LanguageId)->getResponseContent();
		$this->assertResponseContentDoesNotHaveRecords($responseContent, self::TABLE_Content, 'header', array('Testing #1', '[Translate to Dansk:] Testing #1'));
	}

	/**
	 * @test
	 * @see DataSet/Assertion/createAndLocalizeParentContentRecordWithHotelAndOfferChildRecordsAndDiscardLocalizedParentRecord.csv
	 */
	public function createAndLocalizeParentContentWithHotelAndOfferChildrenAndDiscardLocalizedParent() {
		parent::createAndLocalizeParentContentWithHotelAndOfferChildrenAndDiscardLocalizedParent();
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
		$this->assertResponseContentStructureHasRecords(
			$responseContent, self::TABLE_Content . ':' . self::VALUE_ContentIdFirst, self::FIELD_ContentHotel,
			self::TABLE_Hotel, 'title', array('Hotel #2', 'Hotel #1')
		);
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
		$this->assertResponseContentStructureHasRecords(
			$responseContent, self::TABLE_Content . ':' . self::VALUE_ContentIdFirst, self::FIELD_ContentHotel,
			self::TABLE_Hotel, 'title', array('Hotel #1', 'Testing #1')
		);
	}

	/**
	 * @test
	 * @see DataSet/Assertion/modifyParentRecordWithHotelChildRecordAndDiscardModifiedParentRecord.csv
	 */
	public function modifyParentWithHotelChildAndDiscardModifiedParent() {
		parent::modifyParentWithHotelChildAndDiscardModifiedParent();
		// Actually this is not required, since there's nothing to publish... but it's a test case!
		$this->actionService->publishRecord(self::TABLE_Content, self::VALUE_ContentIdFirst, FALSE);
		$this->assertAssertionDataSet('modifyParentNHotelChildNDiscardModifiedParent');

		$responseContent = $this->getFrontendResponse(self::VALUE_PageId, 0)->getResponseContent();
		$this->assertResponseContentHasRecords($responseContent, self::TABLE_Content, 'header', 'Regular Element #1');
		// Discarding the parent shall not discard the child records
		// Since the discarded parent does not need to be published, version children are not published as well
		$this->assertResponseContentStructureHasRecords(
			$responseContent, self::TABLE_Content . ':' . self::VALUE_ContentIdFirst, self::FIELD_ContentHotel,
			self::TABLE_Hotel, 'title', array('Hotel #1', 'Hotel #2')
		);
		$this->assertResponseContentStructureDoesNotHaveRecords(
			$responseContent, self::TABLE_Content . ':' . self::VALUE_ContentIdFirst, self::FIELD_ContentHotel,
			self::TABLE_Hotel, 'title', 'Testing #1'
		);
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
		$this->assertResponseContentStructureHasRecords(
			$responseContent, self::TABLE_Content . ':' . self::VALUE_ContentIdLast, self::FIELD_ContentHotel,
			self::TABLE_Hotel, 'title', array('Hotel #1', 'Hotel #2')
		);
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

	/**
	 * @test
	 * @see DataSet/modifyNDiscardNModifyParentWHotelChild.csv
	 */
	public function modifyAndDiscardAndModifyParentWithHotelChild() {
		parent::modifyAndDiscardAndModifyParentWithHotelChild();
		$this->actionService->publishRecords(
			array(
				self::TABLE_Content => array(self::VALUE_ContentIdFirst),
				self::TABLE_Hotel => array(3, 4),
			)
		);
		$this->assertAssertionDataSet('modifyNDiscardNModifyParentWHotelChild');

		$responseContent = $this->getFrontendResponse(self::VALUE_PageId, 0, self::VALUE_BackendUserId, self::VALUE_WorkspaceId)->getResponseContent();
		$this->assertResponseContentDoesNotHaveRecords($responseContent, self::TABLE_Content, 'header', 'Testing #1');
		$this->assertResponseContentDoesNotHaveRecords($responseContent, self::TABLE_Hotel, 'header', 'Testing #1');
		$this->assertResponseContentHasRecords($responseContent, self::TABLE_Content, 'header', 'Testing #2');
		$this->assertResponseContentHasRecords($responseContent, self::TABLE_Hotel, 'title', 'Testing #2');
	}

}
