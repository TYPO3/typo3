<?php
namespace TYPO3\CMS\Workspaces\Tests\Functional\DataHandling\Regular;

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

require_once __DIR__ . '/../../../../../core/Tests/Functional/DataHandling/AbstractDataHandlerActionTestCase.php';

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
	const TABLE_PageOverlay = 'pages_language_overlay';
	const TABLE_Content = 'tt_content';

	/**
	 * @var array
	 */
	protected $coreExtensionsToLoad = array(
		'fluid',
		'version',
		'workspaces',
	);

	/**
	 * @var string
	 */
	protected $scenarioDataSetDirectory = 'typo3/sysext/workspaces/Tests/Functional/DataHandling/Regular/DataSet/';

	public function setUp() {
		parent::setUp();
		$this->importScenarioDataSet('LiveDefaultPages');
		$this->importScenarioDataSet('LiveDefaultElements');
		$this->importScenarioDataSet('ReferenceIndex');

		$this->setUpFrontendRootPage(1, array('typo3/sysext/core/Tests/Functional/Fixtures/Frontend/JsonRenderer.ts'));
		$this->backendUser->workspace = self::VALUE_WorkspaceId;
	}

	/**
	 * Content records
	 */

	/**
	 * @see DataSet/Assertion/createContentRecords.csv
	 */
	public function createContents() {
		// Creating record at the beginning of the page
		$newTableIds = $this->actionService->createNewRecord(self::TABLE_Content, self::VALUE_PageId, array('header' => 'Testing #1'));
		$this->recordIds['newContentIdFirst'] = $newTableIds[self::TABLE_Content][0];
		// Creating record at the end of the page (after last one)
		$newTableIds = $this->actionService->createNewRecord(self::TABLE_Content, -self::VALUE_ContentIdLast, array('header' => 'Testing #2'));
		$this->recordIds['newContentIdLast'] = $newTableIds[self::TABLE_Content][0];
	}

	/**
	 * @see DataSet/Assertion/createContentRecordAndDiscardCreatedContentRecord.csv
	 */
	public function createContentAndDiscardCreatedContent() {
		$newTableIds = $this->actionService->createNewRecord(self::TABLE_Content, self::VALUE_PageId, array('header' => 'Testing #1'));
		$this->recordIds['newContentId'] = $newTableIds[self::TABLE_Content][0];
		$versionedNewContentId = $this->actionService->getDataHander()->getAutoVersionId(self::TABLE_Content, $this->recordIds['newContentId']);
		$this->actionService->clearWorkspaceRecord(self::TABLE_Content, $versionedNewContentId);
	}

	/**
	 * @see DataSet/Assertion/createAndCopyContentRecordAndDiscardCopiedContentRecord.csv
	 */
	public function createAndCopyContentAndDiscardCopiedContent() {
		$newTableIds = $this->actionService->createNewRecord(self::TABLE_Content, self::VALUE_PageId, array('header' => 'Testing #1'));
		$this->recordIds['newContentId'] = $newTableIds[self::TABLE_Content][0];
		$copiedTableIds = $this->actionService->copyRecord(self::TABLE_Content, $this->recordIds['newContentId'], self::VALUE_PageId);
		$this->recordIds['copiedContentId'] = $copiedTableIds[self::TABLE_Content][$this->recordIds['newContentId']];
		$versionedCopiedContentId = $this->actionService->getDataHander()->getAutoVersionId(self::TABLE_Content, $this->recordIds['copiedContentId']);
		$this->actionService->clearWorkspaceRecord(self::TABLE_Content, $versionedCopiedContentId);
	}

	/**
	 * @see DataSet/Assertion/modifyContentRecord.csv
	 */
	public function modifyContent() {
		$this->actionService->modifyRecord(self::TABLE_Content, self::VALUE_ContentIdLast, array('header' => 'Testing #1'));
	}

	/**
	 * @see DataSet/Assertion/deleteContentRecord.csv
	 */
	public function deleteContent() {
		$this->actionService->deleteRecord(self::TABLE_Content, self::VALUE_ContentIdLast);
	}

	/**
	 * @see DataSet/Assertion/copyContentRecord.csv
	 */
	public function copyContent() {
		$copiedTableIds = $this->actionService->copyRecord(self::TABLE_Content, self::VALUE_ContentIdLast, self::VALUE_PageId);
		$this->recordIds['copiedContentId'] = $copiedTableIds[self::TABLE_Content][self::VALUE_ContentIdLast];
	}

	/**
	 * @see DataSet/Assertion/localizeContentRecord.csv
	 */
	public function localizeContent() {
		$localizedTableIds = $this->actionService->localizeRecord(self::TABLE_Content, self::VALUE_ContentIdLast, self::VALUE_LanguageId);
		$this->recordIds['localizedContentId'] = $localizedTableIds[self::TABLE_Content][self::VALUE_ContentIdLast];
	}

	/**
	 * @see DataSet/Assertion/changeContentRecordSorting.csv
	 */
	public function changeContentSorting() {
		$this->actionService->moveRecord(self::TABLE_Content, self::VALUE_ContentIdFirst, -self::VALUE_ContentIdLast);
	}

	/**
	 * @see DataSet/Assertion/moveContentRecordToDifferentPage.csv
	 */
	public function moveContentToDifferentPage() {
		$this->actionService->moveRecord(self::TABLE_Content, self::VALUE_ContentIdLast, self::VALUE_PageIdTarget);
	}

	/**
	 * @see DataSet/Assertion/moveContentRecordToDifferentPageAndChangeSorting.csv
	 */
	public function moveContentToDifferentPageAndChangeSorting() {
		$this->actionService->moveRecord(self::TABLE_Content, self::VALUE_ContentIdLast, self::VALUE_PageIdTarget);
		$this->actionService->moveRecord(self::TABLE_Content, self::VALUE_ContentIdFirst, -self::VALUE_ContentIdLast);
	}

	/**
	 * Page records
	 */

	/**
	 * @see DataSet/Assertion/createPageRecord.csv
	 */
	public function createPage() {
		$newTableIds = $this->actionService->createNewRecord(self::TABLE_Page, self::VALUE_PageId, array('title' => 'Testing #1', 'hidden' => 0));
		$this->recordIds['newPageId'] = $newTableIds[self::TABLE_Page][0];
	}

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
	 * @see DataSet/Assertion/localizePageRecord.csv
	 */
	public function localizePage() {
		$localizedTableIds = $this->actionService->localizeRecord(self::TABLE_Page, self::VALUE_PageId, self::VALUE_LanguageId);
		$this->recordIds['localizedPageOverlayId'] = $localizedTableIds[self::TABLE_PageOverlay][self::VALUE_PageId];
	}

	/**
	 * @see DataSet/Assertion/changePageRecordSorting.csv
	 */
	public function changePageSorting() {
		$this->actionService->moveRecord(self::TABLE_Page, self::VALUE_PageId, -self::VALUE_PageIdTarget);
	}

	/**
	 * @see DataSet/Assertion/movePageRecordToDifferentPage.csv
	 */
	public function movePageToDifferentPage() {
		$this->actionService->moveRecord(self::TABLE_Page, self::VALUE_PageId, self::VALUE_PageIdTarget);
	}

	/**
	 * @see DataSet/Assertion/movePageRecordToDifferentPageAndChangeSorting.csv
	 */
	public function movePageToDifferentPageAndChangeSorting() {
		$this->actionService->moveRecord(self::TABLE_Page, self::VALUE_PageIdTarget, self::VALUE_PageIdWebsite);
		$this->actionService->moveRecord(self::TABLE_Page, self::VALUE_PageId, -self::VALUE_PageIdTarget);
	}

	/**
	 * @see DataSet/Assertion/movePageRecordToDifferentPageAndCreatePageRecordAfterMovedPageRecord.csv
	 * @see http://forge.typo3.org/issues/33104
	 * @see http://forge.typo3.org/issues/55573
	 */
	public function movePageToDifferentPageAndCreatePageAfterMovedPage() {
		$this->actionService->moveRecord(self::TABLE_Page, self::VALUE_PageIdTarget, self::VALUE_PageIdWebsite);
		$newTableIds = $this->actionService->createNewRecord(self::TABLE_Page, -self::VALUE_PageIdTarget, array('title' => 'Testing #1', 'hidden' => 0));
		$this->recordIds['newPageId'] = $newTableIds[self::TABLE_Page][0];
	}

}
