<?php
namespace TYPO3\CMS\Core\Tests\Functional\DataHandling\IRRE\CSV;

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
	const TABLE_Hotel = 'tx_irretutorial_1ncsv_hotel';
	const TABLE_Offer = 'tx_irretutorial_1ncsv_offer';

	const FIELD_ContentHotel = 'tx_irretutorial_1ncsv_hotels';
	const FIELD_HotelOffer = 'offers';

	/**
	 * @var string
	 */
	protected $scenarioDataSetDirectory = 'typo3/sysext/core/Tests/Functional/DataHandling/IRRE/CSV/DataSet/Scenario/';

	/**
	 * @var string
	 */
	protected $assertionDataSetDirectory = 'typo3/sysext/core/Tests/Functional/DataHandling/IRRE/CSV/DataSet/Assertion/';

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

		$responseContent = $this->getFrontendResponse(self::VALUE_PageId)->getResponseContent();
		$this->assertResponseContentHasRecords($responseContent, self::TABLE_Content, 'header', 'Testing #1');
	}

	/**
	 * @test
	 * @see DataSet/Assertion/modifyParentContentRecord.csv
	 */
	public function modifyParentContent() {
		$this->actionService->modifyRecord(self::TABLE_Content, self::VALUE_ContentIdLast, array('header' => 'Testing #1'));
		$this->assertAssertionDataSet('modifyParentContent');

		$responseContent = $this->getFrontendResponse(self::VALUE_PageId)->getResponseContent();
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
		$this->actionService->deleteRecord(self::TABLE_Content, self::VALUE_ContentIdLast);
		$this->assertAssertionDataSet('deleteParentContent');

		$responseContent = $this->getFrontendResponse(self::VALUE_PageId)->getResponseContent();
		$this->assertResponseContentDoesNotHaveRecords($responseContent, self::TABLE_Content, 'header', 'Regular Element #2');
	}

	/**
	 * @test
	 * @see DataSet/Assertion/copyParentContentRecord.csv
	 */
	public function copyParentContent() {
		$newTableIds = $this->actionService->copyRecord(self::TABLE_Content, self::VALUE_ContentIdLast, self::VALUE_PageId);
		$this->assertAssertionDataSet('copyParentContent');

		$newContentId = $newTableIds[self::TABLE_Content][self::VALUE_ContentIdLast];
		$responseContent = $this->getFrontendResponse(self::VALUE_PageId)->getResponseContent();
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
		$this->actionService->moveRecord(self::TABLE_Content, self::VALUE_ContentIdFirst, -self::VALUE_ContentIdLast);
		$this->assertAssertionDataSet('changeParentContentSorting');

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
	 * @see DataSet/Assertion/moveParentContentRecordToDifferentPage.csv
	 */
	public function moveParentContentToDifferentPage() {
		$this->actionService->moveRecord(self::TABLE_Content, self::VALUE_ContentIdLast, self::VALUE_PageIdTarget);
		$this->assertAssertionDataSet('moveParentContentToDifferentPage');

		$responseContent = $this->getFrontendResponse(self::VALUE_PageIdTarget)->getResponseContent();
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
		$this->actionService->moveRecord(self::TABLE_Content, self::VALUE_ContentIdLast, self::VALUE_PageIdTarget);
		$this->actionService->moveRecord(self::TABLE_Content, self::VALUE_ContentIdFirst, -self::VALUE_ContentIdLast);
		$this->assertAssertionDataSet('moveParentContentToDifferentPageNChangeSorting');

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
	 * @see DataSet/Assertion/modifyPageRecord.csv
	 */
	public function modifyPage() {
		$this->actionService->modifyRecord(self::TABLE_Page, self::VALUE_PageId, array('title' => 'Testing #1'));
		$this->assertAssertionDataSet('modifyPage');

		$responseContent = $this->getFrontendResponse(self::VALUE_PageId)->getResponseContent();
		$this->assertResponseContentHasRecords($responseContent, self::TABLE_Page, 'title', 'Testing #1');
	}

	/**
	 * @test
	 * @see DataSet/Assertion/deletePageRecord.csv
	 */
	public function deletePage() {
		$this->actionService->deleteRecord(self::TABLE_Page, self::VALUE_PageId);
		$this->assertAssertionDataSet('deletePage');

		$response = $this->getFrontendResponse(self::VALUE_PageId, 0, 0, 0, FALSE);
		$this->assertContains('PageNotFoundException', $response->getError());
	}

	/**
	 * @test
	 * @see DataSet/Assertion/copyPageRecord.csv
	 */
	public function copyPage() {
		$newTableIds = $this->actionService->copyRecord(self::TABLE_Page, self::VALUE_PageId, self::VALUE_PageIdTarget);
		$this->assertAssertionDataSet('copyPage');

		$newPageId = $newTableIds[self::TABLE_Page][self::VALUE_PageId];
		$responseContent = $this->getFrontendResponse($newPageId)->getResponseContent();
		$this->assertResponseContentHasRecords($responseContent, self::TABLE_Hotel, 'title', array('Hotel #1', 'Hotel #2', 'Hotel #1'));
	}

	/**
	 * @test
	 * @see DataSet/Assertion/copyPageRecord.csv
	 */
	public function copyPageWithHotelBeforeParentContent() {
		// Ensure hotels get processed first
		$GLOBALS['TCA'] = array_merge(
			array(self::TABLE_Hotel => $GLOBALS['TCA'][self::TABLE_Hotel]),
			$GLOBALS['TCA']
		);

		$newTableIds = $this->actionService->copyRecord(self::TABLE_Page, self::VALUE_PageId, self::VALUE_PageIdTarget);
		$this->assertAssertionDataSet('copyPageWHotelBeforeParentContent');

		$newPageId = $newTableIds[self::TABLE_Page][self::VALUE_PageId];
		$responseContent = $this->getFrontendResponse($newPageId)->getResponseContent();
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
				self::TABLE_Content => array('header' => 'Testing #1', self::FIELD_ContentHotel => '__nextUid'),
				self::TABLE_Hotel => array('title' => 'Hotel #1', self::FIELD_HotelOffer => '__nextUid'),
				self::TABLE_Offer => array('title' => 'Offer #1'),
			)
		);
		$this->assertAssertionDataSet('createParentContentNHotelNOfferChildren');

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
	 * @see DataSet/Assertion/createAndCopyParentContentRecordWithHotelAndOfferChildRecords.csv
	 */
	public function createAndCopyParentContentWithHotelAndOfferChildren() {
		$newTableIds = $this->actionService->createNewRecords(
			self::VALUE_PageId,
			array(
				self::TABLE_Content => array('header' => 'Testing #1', self::FIELD_ContentHotel => '__nextUid'),
				self::TABLE_Hotel => array('title' => 'Hotel #1', self::FIELD_HotelOffer => '__nextUid'),
				self::TABLE_Offer => array('title' => 'Offer #1'),
			)
		);
		$newContentId = $newTableIds[self::TABLE_Content][0];
		$newHotelId = $newTableIds[self::TABLE_Hotel][0];
		$copiedTableIds = $this->actionService->copyRecord(self::TABLE_Content, $newContentId, self::VALUE_PageId);
		$this->assertAssertionDataSet('createNCopyParentContentNHotelNOfferChildren');

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
	 * @see DataSet/Assertion/createAndLocalizeParentContentRecordWithHotelAndOfferChildRecords.csv
	 */
	public function createAndLocalizeParentContentWithHotelAndOfferChildren() {
		$newTableIds = $this->actionService->createNewRecords(
			self::VALUE_PageId,
			array(
				self::TABLE_Content => array('header' => 'Testing #1', self::FIELD_ContentHotel => '__nextUid'),
				self::TABLE_Hotel => array('title' => 'Hotel #1', self::FIELD_HotelOffer => '__nextUid'),
				self::TABLE_Offer => array('title' => 'Offer #1'),
			)
		);
		$newContentId = $newTableIds[self::TABLE_Content][0];
		$newHotelId = $newTableIds[self::TABLE_Hotel][0];
		$localizedTableIds = $this->actionService->localizeRecord(self::TABLE_Content, $newContentId, self::VALUE_LanguageId);
		$this->assertAssertionDataSet('createNLocalizeParentContentNHotelNOfferChildren');

		$localizedHotelId = $localizedTableIds[self::TABLE_Hotel][$newHotelId];
		$responseContent = $this->getFrontendResponse(self::VALUE_PageId, self::VALUE_LanguageId)->getResponseContent();
		// Content record gets overlaid, thus using newContentId
		$this->assertResponseContentStructureHasRecords(
			$responseContent, self::TABLE_Content . ':' . $newContentId, self::FIELD_ContentHotel,
			self::TABLE_Hotel, 'title', '[Translate to Dansk:] Hotel #1'
		);
		// Content record directly points to localized child, thus using localizedHotelId
		$this->assertResponseContentStructureHasRecords(
			$responseContent, self::TABLE_Hotel . ':' . $localizedHotelId, self::FIELD_HotelOffer,
			self::TABLE_Offer, 'title', '[Translate to Dansk:] Offer #1'
		);
	}

	/**
	 * @test
	 * @see DataSet/Assertion/modifyOnlyHotelChildRecord.csv
	 */
	public function modifyOnlyHotelChild() {
		$this->actionService->modifyRecord(self::TABLE_Hotel, 4, array('title' => 'Testing #1'));
		$this->assertAssertionDataSet('modifyOnlyHotelChild');

		$responseContent = $this->getFrontendResponse(self::VALUE_PageId)->getResponseContent();
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

		$responseContent = $this->getFrontendResponse(self::VALUE_PageId)->getResponseContent();
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
		$this->actionService->modifyRecords(
			self::VALUE_PageId,
			array(
				self::TABLE_Content => array('uid' => self::VALUE_ContentIdFirst, self::FIELD_ContentHotel => '3,4'),
				self::TABLE_Hotel => array('uid' => 4, 'title' => 'Testing #1'),
			)
		);
		$this->assertAssertionDataSet('modifyParentNHotelChild');

		$responseContent = $this->getFrontendResponse(self::VALUE_PageId)->getResponseContent();
		$this->assertResponseContentStructureHasRecords(
			$responseContent, self::TABLE_Content . ':' . self::VALUE_ContentIdFirst, self::FIELD_ContentHotel,
			self::TABLE_Hotel, 'title', array('Hotel #1', 'Testing #1')
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
				self::TABLE_Content => array('uid' => self::VALUE_ContentIdLast, self::FIELD_ContentHotel => '5,__nextUid'),
				self::TABLE_Hotel => array('uid' => '__NEW', 'title' => 'Hotel #2'),
			)
		);
		$this->assertAssertionDataSet('modifyParentNAddHotelChild');

		$responseContent = $this->getFrontendResponse(self::VALUE_PageId)->getResponseContent();
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
		$this->actionService->modifyRecord(
			self::TABLE_Content,
			self::VALUE_ContentIdFirst,
			array(self::FIELD_ContentHotel => '3'),
			array(self::TABLE_Hotel => array(4))
		);
		$this->assertAssertionDataSet('modifyParentNDeleteHotelChild');

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
