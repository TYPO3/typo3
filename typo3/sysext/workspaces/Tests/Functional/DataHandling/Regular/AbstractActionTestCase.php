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
	const TABLE_Content = 'tt_content';

	/**
	 * @var array
	 */
	protected $coreExtensionsToLoad = array(
		'version',
		'workspaces',
	);

	/**
	 * @var string
	 */
	protected $dataSetDirectory = 'typo3/sysext/workspaces/Tests/Functional/DataHandling/Regular/DataSet/';

	public function setUp() {
		parent::setUp();
		$this->importScenarioDataSet('LiveDefaultPages');
		$this->importScenarioDataSet('LiveDefaultElements');

		$this->setUpFrontendRootPage(1, array('typo3/sysext/core/Tests/Functional/Fixtures/Frontend/JsonRenderer.ts'));
	}

	/**
	 * Content records
	 */

	/**
	 * @test
	 * @see DataSet/Assertion/createContentRecords.csv
	 */
	public function createContents() {
		// Creating record at the beginning of the page
		$this->actionService->createNewRecord(self::TABLE_Content, self::VALUE_PageId, array('header' => 'Testing #1'));
		// Creating record at the end of the page (after last one)
		$this->actionService->createNewRecord(self::TABLE_Content, -self::VALUE_ContentIdLast, array('header' => 'Testing #2'));
		$this->assertAssertionDataSet('createContents');

		$responseContent = $this->getFrontendResponse(self::VALUE_PageId, 0, self::VALUE_BackendUserId, self::VALUE_WorkspaceId)->getResponseContent();
		$this->assertResponseContentHasRecords($responseContent, self::TABLE_Content, 'header', array('Testing #1', 'Testing #2'));
	}

	/**
	 * @test
	 * @see DataSet/Assertion/createContentRecordAndDiscardCreatedContentRecord.csv
	 */
	public function createContentAndDiscardCreatedContent() {
		$newTableIds = $this->actionService->createNewRecord(self::TABLE_Content, self::VALUE_PageId, array('header' => 'Testing #1'));
		$newContentId = $newTableIds[self::TABLE_Content][0];
		$versionedNewContentId = $this->actionService->getDataHander()->getAutoVersionId(self::TABLE_Content, $newContentId);
		$this->actionService->clearWorkspaceRecord(self::TABLE_Content, $versionedNewContentId);
		$this->assertAssertionDataSet('createContentNDiscardCreatedContent');

		$responseContent = $this->getFrontendResponse(self::VALUE_PageId, 0, self::VALUE_BackendUserId, self::VALUE_WorkspaceId)->getResponseContent();
		$this->assertResponseContentDoesNotHaveRecords($responseContent, self::TABLE_Content, 'header', 'Testing #1');
	}

	/**
	 * @test
	 * @see DataSet/Assertion/createAndCopyContentRecordAndDiscardCopiedContentRecord.csv
	 */
	public function createAndCopyContentAndDiscardCopiedContent() {
		$newTableIds = $this->actionService->createNewRecord(self::TABLE_Content, self::VALUE_PageId, array('header' => 'Testing #1'));
		$newContentId = $newTableIds[self::TABLE_Content][0];
		$copiedTableIds = $this->actionService->copyRecord(self::TABLE_Content, $newContentId, self::VALUE_PageId);
		$copiedContentId = $copiedTableIds[self::TABLE_Content][$newContentId];
		$versionedCopiedContentId = $this->actionService->getDataHander()->getAutoVersionId(self::TABLE_Content, $copiedContentId);
		$this->actionService->clearWorkspaceRecord(self::TABLE_Content, $versionedCopiedContentId);
		$this->assertAssertionDataSet('createNCopyContentNDiscardCopiedContent');

		$responseContent = $this->getFrontendResponse(self::VALUE_PageId, 0, self::VALUE_BackendUserId, self::VALUE_WorkspaceId)->getResponseContent();
		$this->assertResponseContentHasRecords($responseContent, self::TABLE_Content, 'header', 'Testing #1');
		$this->assertResponseContentDoesNotHaveRecords($responseContent, self::TABLE_Content, 'header', 'Testing #1 (copy 1)');
	}

	/**
	 * @test
	 * @see DataSet/Assertion/modifyContentRecord.csv
	 */
	public function modifyContent() {
		$this->actionService->modifyRecord(self::TABLE_Content, self::VALUE_ContentIdLast, array('header' => 'Testing #1'));
		$this->assertAssertionDataSet('modifyContent');

		$responseContent = $this->getFrontendResponse(self::VALUE_PageId, 0, self::VALUE_BackendUserId, self::VALUE_WorkspaceId)->getResponseContent();
		$this->assertResponseContentHasRecords($responseContent, self::TABLE_Content, 'header', 'Testing #1');
	}

	/**
	 * @test
	 * @see DataSet/Assertion/deleteContentRecord.csv
	 */
	public function deleteContent() {
		$this->actionService->deleteRecord(self::TABLE_Content, self::VALUE_ContentIdLast);
		$this->assertAssertionDataSet('deleteContent');

		$responseContent = $this->getFrontendResponse(self::VALUE_PageId, 0, self::VALUE_BackendUserId, self::VALUE_WorkspaceId)->getResponseContent();
		$this->assertResponseContentHasRecords($responseContent, self::TABLE_Content, 'header', 'Regular Element #1');
		$this->assertResponseContentDoesNotHaveRecords($responseContent, self::TABLE_Content, 'header', 'Regular Element #2');
	}

	/**
	 * @test
	 * @see DataSet/Assertion/copyContentRecord.csv
	 */
	public function copyContent() {
		$this->actionService->copyRecord(self::TABLE_Content, self::VALUE_ContentIdLast, self::VALUE_PageId);
		$this->assertAssertionDataSet('copyContent');

		$responseContent = $this->getFrontendResponse(self::VALUE_PageId, 0, self::VALUE_BackendUserId, self::VALUE_WorkspaceId)->getResponseContent();
		$this->assertResponseContentHasRecords($responseContent, self::TABLE_Content, 'header', 'Regular Element #2 (copy 1)');
	}

	/**
	 * @test
	 * @see DataSet/Assertion/localizeContentRecord.csv
	 */
	public function localizeContent() {
		$this->actionService->localizeRecord(self::TABLE_Content, self::VALUE_ContentIdLast, self::VALUE_LanguageId);
		$this->assertAssertionDataSet('localizeContent');

		$responseContent = $this->getFrontendResponse(self::VALUE_PageId, self::VALUE_LanguageId, self::VALUE_BackendUserId, self::VALUE_WorkspaceId)->getResponseContent();
		$this->assertResponseContentHasRecords($responseContent, self::TABLE_Content, 'header', array('Regular Element #1', '[Translate to Dansk:] Regular Element #2'));
	}

	/**
	 * @test
	 * @see DataSet/Assertion/changeContentRecordSorting.csv
	 */
	public function changeContentSorting() {
		$this->actionService->moveRecord(self::TABLE_Content, self::VALUE_ContentIdFirst, -self::VALUE_ContentIdLast);
		$this->assertAssertionDataSet('changeContentSorting');

		$responseContent = $this->getFrontendResponse(self::VALUE_PageId, 0, self::VALUE_BackendUserId, self::VALUE_WorkspaceId)->getResponseContent();
		$this->assertResponseContentHasRecords($responseContent, self::TABLE_Content, 'header', array('Regular Element #1', 'Regular Element #2'));
	}

	/**
	 * @test
	 * @see DataSet/Assertion/moveContentRecordToDifferentPage.csv
	 */
	public function moveContentToDifferentPage() {
		$this->actionService->moveRecord(self::TABLE_Content, self::VALUE_ContentIdLast, self::VALUE_PageIdTarget);
		$this->assertAssertionDataSet('moveContentToDifferentPage');

		$responseContentSource = $this->getFrontendResponse(self::VALUE_PageId, 0, self::VALUE_BackendUserId, self::VALUE_WorkspaceId)->getResponseContent();
		$this->assertResponseContentHasRecords($responseContentSource, self::TABLE_Content, 'header', 'Regular Element #1');
		$responseContentTarget = $this->getFrontendResponse(self::VALUE_PageIdTarget, 0, self::VALUE_BackendUserId, self::VALUE_WorkspaceId)->getResponseContent();
		$this->assertResponseContentHasRecords($responseContentTarget, self::TABLE_Content, 'header', 'Regular Element #2');
	}

	/**
	 * @test
	 * @see DataSet/Assertion/moveContentRecordToDifferentPageAndChangeSorting.csv
	 */
	public function moveContentToDifferentPageAndChangeSorting() {
		$this->actionService->moveRecord(self::TABLE_Content, self::VALUE_ContentIdLast, self::VALUE_PageIdTarget);
		$this->actionService->moveRecord(self::TABLE_Content, self::VALUE_ContentIdFirst, -self::VALUE_ContentIdLast);
		$this->assertAssertionDataSet('moveContentToDifferentPageNChangeSorting');

		$responseContent = $this->getFrontendResponse(self::VALUE_PageIdTarget, 0, self::VALUE_BackendUserId, self::VALUE_WorkspaceId)->getResponseContent();
		$this->assertResponseContentHasRecords($responseContent, self::TABLE_Content, 'header', array('Regular Element #1', 'Regular Element #2'));
	}

	/**
	 * Page records
	 */

	/**
	 * @test
	 * @see DataSet/Assertion/createPageRecord.csv
	 */
	public function createPage() {
		$newTableIds = $this->actionService->createNewRecord(self::TABLE_Page, self::VALUE_PageId, array('title' => 'Testing #1', 'hidden' => 0));
		$this->assertAssertionDataSet('createPage');

		$newPageId = $newTableIds[self::TABLE_Page][0];
		$responseContent = $this->getFrontendResponse($newPageId, 0, self::VALUE_BackendUserId, self::VALUE_WorkspaceId)->getResponseContent();
		$this->assertResponseContentHasRecords($responseContent, self::TABLE_Page, 'title', 'Testing #1');
	}

	/**
	 * @test
	 * @see DataSet/Assertion/modifyPageRecord.csv
	 */
	public function modifyPage() {
		$this->actionService->modifyRecord(self::TABLE_Page, self::VALUE_PageId, array('title' => 'Testing #1'));
		$this->assertAssertionDataSet('modifyPage');

		$responseContent = $this->getFrontendResponse(self::VALUE_PageId, 0, self::VALUE_BackendUserId, self::VALUE_WorkspaceId)->getResponseContent();
		$this->assertResponseContentHasRecords($responseContent, self::TABLE_Page, 'title', 'Testing #1');
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
		$this->assertResponseContentHasRecords($responseContent, self::TABLE_Page, 'title', 'Relations');
	}

	/**
	 * @test
	 * @see DataSet/Assertion/localizePageRecord.csv
	 */
	public function localizePage() {
		$this->actionService->localizeRecord(self::TABLE_Page, self::VALUE_PageId, self::VALUE_LanguageId);
		$this->assertAssertionDataSet('localizePage');

		$responseContent = $this->getFrontendResponse(self::VALUE_PageId, self::VALUE_LanguageId, self::VALUE_BackendUserId, self::VALUE_WorkspaceId)->getResponseContent();
		$this->assertResponseContentHasRecords($responseContent, self::TABLE_Page, 'title', '[Translate to Dansk:] Relations');
	}

	/**
	 * @test
	 * @see DataSet/Assertion/changePageRecordSorting.csv
	 */
	public function changePageSorting() {
		$this->actionService->moveRecord(self::TABLE_Page, self::VALUE_PageId, -self::VALUE_PageIdTarget);
		$this->assertAssertionDataSet('changePageSorting');

		$responseContent = $this->getFrontendResponse(self::VALUE_PageId, 0, self::VALUE_BackendUserId, self::VALUE_WorkspaceId)->getResponseContent();
		$this->assertResponseContentHasRecords($responseContent, self::TABLE_Page, 'title', 'Relations');
		$this->assertResponseContentHasRecords($responseContent, self::TABLE_Content, 'header', array('Regular Element #1', 'Regular Element #2'));
	}

	/**
	 * @test
	 * @see DataSet/Assertion/movePageRecordToDifferentPage.csv
	 */
	public function movePageToDifferentPage() {
		$this->actionService->moveRecord(self::TABLE_Page, self::VALUE_PageId, self::VALUE_PageIdTarget);
		$this->assertAssertionDataSet('movePageToDifferentPage');

		$responseContent = $this->getFrontendResponse(self::VALUE_PageId, 0, self::VALUE_BackendUserId, self::VALUE_WorkspaceId)->getResponseContent();
		$this->assertResponseContentHasRecords($responseContent, self::TABLE_Page, 'title', 'Relations');
		$this->assertResponseContentHasRecords($responseContent, self::TABLE_Content, 'header', array('Regular Element #1', 'Regular Element #2'));
	}

	/**
	 * @test
	 * @see DataSet/Assertion/movePageRecordToDifferentPageAndChangeSorting.csv
	 */
	public function movePageToDifferentPageAndChangeSorting() {
		$this->actionService->moveRecord(self::TABLE_Page, self::VALUE_PageIdTarget, self::VALUE_PageIdWebsite);
		$this->actionService->moveRecord(self::TABLE_Page, self::VALUE_PageId, -self::VALUE_PageIdTarget);
		$this->assertAssertionDataSet('movePageToDifferentPageNChangeSorting');

		$responseContentPage = $this->getFrontendResponse(self::VALUE_PageId, 0, self::VALUE_BackendUserId, self::VALUE_WorkspaceId)->getResponseContent();
		$this->assertResponseContentHasRecords($responseContentPage, self::TABLE_Page, 'title', 'Relations');
		$this->assertResponseContentHasRecords($responseContentPage, self::TABLE_Content, 'header', array('Regular Element #1', 'Regular Element #2'));
		$responseContentWebsite = $this->getFrontendResponse(self::VALUE_PageIdWebsite, 0, self::VALUE_BackendUserId, self::VALUE_WorkspaceId)->getResponseContent();
		$this->assertResponseContentStructureHasRecords(
			$responseContentWebsite, self::TABLE_Page . ':' . self::VALUE_PageIdWebsite, '__pages',
			self::TABLE_Page, 'title', array('Target', 'Relations', 'DataHandlerTest')
		);
	}

	/**
	 * @test
	 * @see DataSet/Assertion/movePageRecordToDifferentPageAndCreatePageRecordAfterMovedPageRecord.csv
	 * @see http://forge.typo3.org/issues/33104
	 * @see http://forge.typo3.org/issues/55573
	 */
	public function movePageToDifferentPageAndCreatePageAfterMovedPage() {
		$this->actionService->moveRecord(self::TABLE_Page, self::VALUE_PageIdTarget, self::VALUE_PageIdWebsite);
		$this->actionService->createNewRecord(self::TABLE_Page, -self::VALUE_PageIdTarget, array('title' => 'Testing #1', 'hidden' => 0));
		$this->assertAssertionDataSet('movePageToDifferentPageNCreatePageAfterMovedPage');

		$responseContent = $this->getFrontendResponse(self::VALUE_PageIdWebsite, 0, self::VALUE_BackendUserId, self::VALUE_WorkspaceId)->getResponseContent();
		$this->assertResponseContentStructureHasRecords(
			$responseContent, self::TABLE_Page . ':' . self::VALUE_PageIdWebsite, '__pages',
			self::TABLE_Page, 'title', array('Target', 'Testing #1', 'DataHandlerTest')
		);
	}

}
