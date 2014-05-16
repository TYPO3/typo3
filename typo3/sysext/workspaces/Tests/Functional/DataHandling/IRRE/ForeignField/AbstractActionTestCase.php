<?php
namespace TYPO3\CMS\Workspaces\Tests\Functional\DataHandling\IRRE\ForeignField;

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
	const VALUE_HotelIdFirst = 3;
	const VALUE_HotelIdSecond = 4;
	const VALUE_HotelIdThird = 5;
	const VALUE_LanguageId = 1;
	const VALUE_WorkspaceId = 1;

	const TABLE_Page = 'pages';
	const TABLE_Content = 'tt_content';
	const TABLE_Hotel = 'tx_irretutorial_1nff_hotel';
	const TABLE_Offer = 'tx_irretutorial_1nff_offer';

	const FIELD_ContentHotel = 'tx_irretutorial_1nff_hotels';
	const FIELD_HotelOffer = 'offers';

	/**
	 * @var string
	 */
	protected $scenarioDataSetDirectory = 'typo3/sysext/workspaces/Tests/Functional/DataHandling/IRRE/ForeignField/DataSet/';

	/**
	 * @var array
	 */
	protected $coreExtensionsToLoad = array(
		'fluid',
		'version',
		'workspaces',
	);

	public function setUp() {
		parent::setUp();
		$this->importScenarioDataSet('LiveDefaultPages');
		$this->importScenarioDataSet('LiveDefaultElements');
		$this->importScenarioDataSet('ReferenceIndex');

		$this->setUpFrontendRootPage(1, array('typo3/sysext/core/Tests/Functional/Fixtures/Frontend/JsonRenderer.ts'));
		$this->backendUser->workspace = self::VALUE_WorkspaceId;
	}

	/**
	 * Parent content records
	 */

	/**
	 * @see DataSet/Assertion/createParentContentRecord.csv
	 */
	public function createParentContent() {
		$newTableIds = $this->actionService->createNewRecord(self::TABLE_Content, self::VALUE_PageId, array('header' => 'Testing #1'));
		$this->recordIds['newContentId'] = $newTableIds[self::TABLE_Content][0];
	}

	/**
	 * @see DataSet/Assertion/modifyParentContentRecord.csv
	 */
	public function modifyParentContent() {
		$this->actionService->modifyRecord(self::TABLE_Content, self::VALUE_ContentIdLast, array('header' => 'Testing #1'));
	}

	/**
	 * @see DataSet/Assertion/deleteParentContentRecord.csv
	 */
	public function deleteParentContent() {
		$this->actionService->deleteRecord(self::TABLE_Content, self::VALUE_ContentIdLast);
	}

	/**
	 * @see DataSet/Assertion/deleteParentContentRecordAndDiscardDeletedParentRecord.csv
	 */
	public function deleteParentContentAndDiscardDeletedParent() {
		$newTableIds = $this->actionService->deleteRecord(self::TABLE_Content, self::VALUE_ContentIdLast);
		$versionedDeletedContentId = $newTableIds[self::TABLE_Content][self::VALUE_ContentIdLast];
		$this->actionService->clearWorkspaceRecord(self::TABLE_Content, $versionedDeletedContentId);
	}

	/**
	 * @see DataSet/Assertion/copyParentContentRecord.csv
	 */
	public function copyParentContent() {
		$newTableIds = $this->actionService->copyRecord(self::TABLE_Content, self::VALUE_ContentIdLast, self::VALUE_PageId);
		$this->recordIds['newContentId'] = $newTableIds[self::TABLE_Content][self::VALUE_ContentIdLast];
	}

	/**
	 * @see DataSet/copyParentContentToDifferentPage.csv
	 */
	public function copyParentContentToDifferentPage() {
		$newTableIds = $this->actionService->copyRecord(self::TABLE_Content, self::VALUE_ContentIdLast, self::VALUE_PageIdTarget);
		$this->recordIds['newContentId'] = $newTableIds[self::TABLE_Content][self::VALUE_ContentIdLast];
	}

	/**
	 * @see DataSet/localizeParentContentKeep.csv
	 */
	public function localizeParentContentInKeepMode() {
		$GLOBALS['TCA'][self::TABLE_Content]['columns'][self::FIELD_ContentHotel]['config']['behaviour']['localizationMode'] = 'keep';
		$GLOBALS['TCA'][self::TABLE_Content]['columns'][self::FIELD_ContentHotel]['config']['behaviour']['localizeChildrenAtParentLocalization'] = FALSE;
		$GLOBALS['TCA'][self::TABLE_Hotel]['columns'][self::FIELD_HotelOffer]['config']['behaviour']['localizeChildrenAtParentLocalization'] = FALSE;
		$newTableIds = $this->actionService->localizeRecord(self::TABLE_Content, self::VALUE_ContentIdLast, self::VALUE_LanguageId);
		$this->recordIds['localizedContentId'] = $newTableIds[self::TABLE_Content][self::VALUE_ContentIdLast];
	}

	/**
	 * @see DataSet/localizeParentContentWAllChildrenKeep.csv
	 */
	public function localizeParentContentWithAllChildrenInKeepMode() {
		$GLOBALS['TCA'][self::TABLE_Content]['columns'][self::FIELD_ContentHotel]['config']['behaviour']['localizationMode'] = 'keep';
		$GLOBALS['TCA'][self::TABLE_Content]['columns'][self::FIELD_ContentHotel]['config']['behaviour']['localizeChildrenAtParentLocalization'] = TRUE;
		$GLOBALS['TCA'][self::TABLE_Hotel]['columns'][self::FIELD_HotelOffer]['config']['behaviour']['localizeChildrenAtParentLocalization'] = TRUE;
		$newTableIds = $this->actionService->localizeRecord(self::TABLE_Content, self::VALUE_ContentIdLast, self::VALUE_LanguageId);
		$this->recordIds['localizedContentId'] = $newTableIds[self::TABLE_Content][self::VALUE_ContentIdLast];
	}

	/**
	 * @see DataSet/localizeParentContentSelect.csv
	 */
	public function localizeParentContentInSelectMode() {
		$GLOBALS['TCA'][self::TABLE_Content]['columns'][self::FIELD_ContentHotel]['config']['behaviour']['localizationMode'] = 'select';
		$GLOBALS['TCA'][self::TABLE_Content]['columns'][self::FIELD_ContentHotel]['config']['behaviour']['localizeChildrenAtParentLocalization'] = FALSE;
		$GLOBALS['TCA'][self::TABLE_Hotel]['columns'][self::FIELD_HotelOffer]['config']['behaviour']['localizeChildrenAtParentLocalization'] = FALSE;
		$newTableIds = $this->actionService->localizeRecord(self::TABLE_Content, self::VALUE_ContentIdLast, self::VALUE_LanguageId);
		$this->recordIds['localizedContentId'] = $newTableIds[self::TABLE_Content][self::VALUE_ContentIdLast];
	}

	/**
	 * @see DataSet/localizeParentContentWAllChildrenSelect.csv
	 */
	public function localizeParentContentWithAllChildrenInSelectMode() {
		$GLOBALS['TCA'][self::TABLE_Content]['columns'][self::FIELD_ContentHotel]['config']['behaviour']['localizationMode'] = 'select';
		$GLOBALS['TCA'][self::TABLE_Content]['columns'][self::FIELD_ContentHotel]['config']['behaviour']['localizeChildrenAtParentLocalization'] = TRUE;
		$GLOBALS['TCA'][self::TABLE_Hotel]['columns'][self::FIELD_HotelOffer]['config']['behaviour']['localizeChildrenAtParentLocalization'] = TRUE;
		$newTableIds = $this->actionService->localizeRecord(self::TABLE_Content, self::VALUE_ContentIdLast, self::VALUE_LanguageId);
		$this->recordIds['localizedContentId'] = $newTableIds[self::TABLE_Content][self::VALUE_ContentIdLast];
	}

	/**
	 * @see DataSet/Assertion/changeParentContentRecordSorting.csv
	 */
	public function changeParentContentSorting() {
		$this->actionService->moveRecord(self::TABLE_Content, self::VALUE_ContentIdFirst, -self::VALUE_ContentIdLast);
	}

	/**
	 * @see DataSet/Assertion/moveParentContentRecordToDifferentPage.csv
	 */
	public function moveParentContentToDifferentPage() {
		$this->actionService->moveRecord(self::TABLE_Content, self::VALUE_ContentIdLast, self::VALUE_PageIdTarget);
	}

	/**
	 * @see DataSet/Assertion/moveParentContentRecordToDifferentPageAndChangeSorting.csv
	 */
	public function moveParentContentToDifferentPageAndChangeSorting() {
		$this->actionService->moveRecord(self::TABLE_Content, self::VALUE_ContentIdLast, self::VALUE_PageIdTarget);
		$this->actionService->moveRecord(self::TABLE_Content, self::VALUE_ContentIdFirst, -self::VALUE_ContentIdLast);
	}

	/**
	 * Page records
	 */

	/**
	 * @see DataSet/Assertion/modifyPageRecord.csv
	 */
	public function modifyPage() {
		$this->actionService->modifyRecord(self::TABLE_Page, self::VALUE_PageId, array('title' => 'Testing #1'));
	}

	/**
	 * @see DataSet/Assertion/deletePageRecord.csv
	 */
	public function deletePage() {
		$this->actionService->deleteRecord(self::TABLE_Page, self::VALUE_PageId);
	}

	/**
	 * @see DataSet/Assertion/copyPageRecord.csv
	 */
	public function copyPage() {
		$newTableIds = $this->actionService->copyRecord(self::TABLE_Page, self::VALUE_PageId, self::VALUE_PageIdTarget);
		$this->recordIds['newPageId'] = $newTableIds[self::TABLE_Page][self::VALUE_PageId];
		$this->recordIds['newContentIdFirst'] = $newTableIds[self::TABLE_Content][self::VALUE_ContentIdFirst];
		$this->recordIds['newContentIdLast'] = $newTableIds[self::TABLE_Content][self::VALUE_ContentIdLast];
	}

	/**
	 * @test
	 * @see DataSet/Assertion/copyPageWHotelBeforeParentContent.csv
	 */
	public function copyPageWithHotelBeforeParentContent() {
		// Ensure hotels get processed first
		$GLOBALS['TCA'] = array_merge(
			array(self::TABLE_Hotel => $GLOBALS['TCA'][self::TABLE_Hotel]),
			$GLOBALS['TCA']
		);

		$newTableIds = $this->actionService->copyRecord(self::TABLE_Page, self::VALUE_PageId, self::VALUE_PageIdTarget);
		$this->recordIds['newPageId'] = $newTableIds[self::TABLE_Page][self::VALUE_PageId];
		$this->recordIds['newContentIdFirst'] = $newTableIds[self::TABLE_Content][self::VALUE_ContentIdFirst];
		$this->recordIds['newContentIdLast'] = $newTableIds[self::TABLE_Content][self::VALUE_ContentIdLast];
	}

	/**
	 * IRRE Child Records
	 */

	/**
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
		$this->recordIds['newContentId'] = $newTableIds[self::TABLE_Content][0];
	}

	/**
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
		$this->recordIds['newContentId'] = $newTableIds[self::TABLE_Content][0];
		$this->recordIds['newHotelId'] = $newTableIds[self::TABLE_Hotel][0];
		$copiedTableIds = $this->actionService->copyRecord(self::TABLE_Content, $this->recordIds['newContentId'], self::VALUE_PageId);
		$this->recordIds['copiedContentId'] = $copiedTableIds[self::TABLE_Content][$this->recordIds['newContentId']];
		$this->recordIds['copiedHotelId'] = $copiedTableIds[self::TABLE_Hotel][$this->recordIds['newHotelId']];
	}

	/**
	 * @see DataSet/Assertion/createAndCopyParentContentRecordWithHotelAndOfferChildRecordsAndDiscardCopiedParentRecord.csv
	 */
	public function createAndCopyParentContentWithHotelAndOfferChildrenAndDiscardCopiedParent() {
		$newTableIds = $this->actionService->createNewRecords(
			self::VALUE_PageId,
			array(
				self::TABLE_Content => array('header' => 'Testing #1', self::FIELD_ContentHotel => '__nextUid'),
				self::TABLE_Hotel => array('title' => 'Hotel #1', self::FIELD_HotelOffer => '__nextUid'),
				self::TABLE_Offer => array('title' => 'Offer #1'),
			)
		);
		$this->recordIds['newContentId'] = $newTableIds['tt_content'][0];
		$copiedTableIds = $this->actionService->copyRecord(self::TABLE_Content, $this->recordIds['newContentId'], self::VALUE_PageId);
		$this->recordIds['copiedContentId'] = $copiedTableIds[self::TABLE_Content][$this->recordIds['newContentId']];
		$versionedCopiedContentId = $this->actionService->getDataHander()->getAutoVersionId(self::TABLE_Content, $this->recordIds['copiedContentId']);
		$this->actionService->clearWorkspaceRecord(self::TABLE_Content, $versionedCopiedContentId);
	}

	/**
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
		$this->recordIds['newContentId'] = $newTableIds[self::TABLE_Content][0];
		$this->recordIds['newHotelId'] = $newTableIds[self::TABLE_Hotel][0];
		$localizedTableIds = $this->actionService->localizeRecord(self::TABLE_Content, $this->recordIds['newContentId'], self::VALUE_LanguageId);
		$this->recordIds['localizedContentId'] = $localizedTableIds[self::TABLE_Content][$this->recordIds['newContentId']];
	}

	/**
	 * @see DataSet/Assertion/createNLocalizeParentContentNHotelNOfferChildrenNDiscardCreatedParent.csv
	 */
	public function createAndLocalizeParentContentWithHotelAndOfferChildrenAndDiscardCreatedParent() {
		$newTableIds = $this->actionService->createNewRecords(
			self::VALUE_PageId,
			array(
				self::TABLE_Content => array('header' => 'Testing #1', self::FIELD_ContentHotel => '__nextUid'),
				self::TABLE_Hotel => array('title' => 'Hotel #1', self::FIELD_HotelOffer => '__nextUid'),
				self::TABLE_Offer => array('title' => 'Offer #1'),
			)
		);
		$this->recordIds['newContentId'] = $newTableIds[self::TABLE_Content][0];
		$this->recordIds['versionedNewContentId'] = $this->actionService->getDataHander()->getAutoVersionId(self::TABLE_Content, $this->recordIds['newContentId']);
		$localizedTableIds = $this->actionService->localizeRecord(self::TABLE_Content, $this->recordIds['newContentId'], self::VALUE_LanguageId);
		$this->recordIds['localizedContentId'] = $localizedTableIds[self::TABLE_Content][$this->recordIds['newContentId']];
		$this->actionService->clearWorkspaceRecord(self::TABLE_Content, $this->recordIds['versionedNewContentId']);
	}

	/**
	 * @see DataSet/Assertion/createAndLocalizeParentContentRecordWithHotelAndOfferChildRecordsAndDiscardLocalizedParentRecord.csv
	 */
	public function createAndLocalizeParentContentWithHotelAndOfferChildrenAndDiscardLocalizedParent() {
		$newTableIds = $this->actionService->createNewRecords(
			self::VALUE_PageId,
			array(
				self::TABLE_Content => array('header' => 'Testing #1', self::FIELD_ContentHotel => '__nextUid'),
				self::TABLE_Hotel => array('title' => 'Hotel #1', self::FIELD_HotelOffer => '__nextUid'),
				self::TABLE_Offer => array('title' => 'Offer #1'),
			)
		);
		$this->recordIds['newContentId'] = $newTableIds[self::TABLE_Content][0];
		$localizedTableIds = $this->actionService->localizeRecord(self::TABLE_Content, $this->recordIds['newContentId'], self::VALUE_LanguageId);
		$this->recordIds['localizedContentId'] = $localizedTableIds[self::TABLE_Content][$this->recordIds['newContentId']];
		$versionedLocalizedContentId = $this->actionService->getDataHander()->getAutoVersionId(self::TABLE_Content, $this->recordIds['localizedContentId']);
		$this->actionService->clearWorkspaceRecord(self::TABLE_Content, $versionedLocalizedContentId);
	}

	/**
	 * @see DataSet/Assertion/modifyOnlyHotelChildRecord.csv
	 */
	public function modifyOnlyHotelChild() {
		$this->actionService->modifyRecord(self::TABLE_Hotel, 4, array('title' => 'Testing #1'));
	}

	/**
	 * @see DataSet/Assertion/modifyParentRecordAndChangeHotelChildRecordsSorting.csv
	 */
	public function modifyParentAndChangeHotelChildrenSorting() {
		$this->actionService->modifyRecord(self::TABLE_Content, self::VALUE_ContentIdFirst, array(self::FIELD_ContentHotel => '4,3'));
	}

	/**
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
	}

	/**
	 * @see DataSet/Assertion/modifyParentRecordWithHotelChildRecordAndDiscardModifiedParentRecord.csv
	 */
	public function modifyParentWithHotelChildAndDiscardModifiedParent() {
		$this->actionService->modifyRecords(
			self::VALUE_PageId,
			array(
				self::TABLE_Content => array('uid' => self::VALUE_ContentIdFirst, self::FIELD_ContentHotel => '3,4'),
				self::TABLE_Hotel => array('uid' => 4, 'title' => 'Testing #1'),
			)
		);
		$modifiedContentId = $this->actionService->getDataHander()->getAutoVersionId(self::TABLE_Content, self::VALUE_ContentIdFirst);
		$this->actionService->clearWorkspaceRecord(self::TABLE_Content, $modifiedContentId);
	}

	/**
	 * @see DataSet/Assertion/modifyParentRecordWithHotelChildRecordAndDiscardAllModifiedRecords.csv
	 */
	public function modifyParentWithHotelChildAndDiscardAll() {
		$this->actionService->modifyRecords(
			self::VALUE_PageId,
			array(
				self::TABLE_Content => array('uid' => self::VALUE_ContentIdFirst, self::FIELD_ContentHotel => '3,4'),
				self::TABLE_Hotel => array('uid' => 4, 'title' => 'Testing #1'),
			)
		);
		$modifiedContentId = $this->actionService->getDataHander()->getAutoVersionId(self::TABLE_Content, self::VALUE_ContentIdFirst);
		$modifiedHotelId = $this->actionService->getDataHander()->getAutoVersionId(self::TABLE_Hotel, 4);
		$this->actionService->clearWorkspaceRecords(
				array(
					self::TABLE_Content => array($modifiedContentId),
					self::TABLE_Hotel => array($modifiedHotelId),
				)
		);
	}

	/**
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
	}

	/**
	 * @see DataSet/Assertion/modifyParentRecordAndDeleteHotelChildRecord.csv
	 */
	public function modifyParentAndDeleteHotelChild() {
		$this->actionService->modifyRecord(
			self::TABLE_Content,
			self::VALUE_ContentIdFirst,
			array(self::FIELD_ContentHotel => '3'),
			array(self::TABLE_Hotel => array(4))
		);
	}

	/**
	 * @see DataSet/modifyNDiscardNModifyParentWHotelChild.csv
	 */
	public function modifyAndDiscardAndModifyParentWithHotelChild() {
		$this->actionService->modifyRecords(
			self::VALUE_PageId,
			array(
				self::TABLE_Content => array('uid' => self::VALUE_ContentIdFirst, 'header' => 'Testing #1', self::FIELD_ContentHotel => '3,4'),
				self::TABLE_Hotel => array('uid' => 4, 'title' => 'Testing #1'),
			)
		);
		$this->recordIds['versionedContentId'] = $this->actionService->getDataHander()->getAutoVersionId(self::TABLE_Content, self::VALUE_ContentIdFirst);
		$this->recordIds['versionedHotelIdFirst'] = $this->actionService->getDataHander()->getAutoVersionId(self::TABLE_Hotel, 3);
		$this->recordIds['versionedHotelIdSecond'] = $this->actionService->getDataHander()->getAutoVersionId(self::TABLE_Hotel, 4);
		$this->actionService->clearWorkspaceRecords(
			array(
				self::TABLE_Content => array($this->recordIds['versionedContentId']),
				self::TABLE_Hotel => array($this->recordIds['versionedHotelIdSecond']),
			)
		);
		$this->actionService->modifyRecords(
			self::VALUE_PageId,
			array(
				self::TABLE_Content => array('uid' => self::VALUE_ContentIdFirst, 'header' => 'Testing #2', self::FIELD_ContentHotel => '3,4'),
				self::TABLE_Hotel => array('uid' => 4, 'title' => 'Testing #2'),
			)
		);
	}

}
