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
	 */
	public function createContentRecords() {
		// Creating record at the beginning of the page
		$this->actionService->createNewRecord(self::TABLE_Content, self::VALUE_PageId, array('header' => 'Testing #1'));
		// Creating record at the end of the page (after last one)
		$this->actionService->createNewRecord(self::TABLE_Content, -self::VALUE_ContentIdLast, array('header' => 'Testing #2'));
		$this->assertAssertionDataSet('createContentRecords');

		$responseContent = $this->getFrontendResponse(self::VALUE_PageId, 0, self::VALUE_BackendUserId, self::VALUE_WorkspaceId)->getResponseContent();
		$this->assertResponseContentHasRecords($responseContent, self::TABLE_Content, 'header', array('Testing #1', 'Testing #2'));
	}

	/**
	 * @test
	 */
	public function modifyContentRecord() {
		$this->actionService->modifyRecord(self::TABLE_Content, self::VALUE_ContentIdLast, array('header' => 'Testing #1'));
		$this->assertAssertionDataSet('modifyContentRecord');

		$responseContent = $this->getFrontendResponse(self::VALUE_PageId, 0, self::VALUE_BackendUserId, self::VALUE_WorkspaceId)->getResponseContent();
		$this->assertResponseContentHasRecords($responseContent, self::TABLE_Content, 'header', 'Testing #1');
	}

	/**
	 * @test
	 */
	public function deleteContentRecord() {
		$this->actionService->deleteRecord(self::TABLE_Content, self::VALUE_ContentIdLast);
		$this->assertAssertionDataSet('deleteContentRecord');

		$responseContent = $this->getFrontendResponse(self::VALUE_PageId, 0, self::VALUE_BackendUserId, self::VALUE_WorkspaceId)->getResponseContent();
		$this->assertResponseContentHasRecords($responseContent, self::TABLE_Content, 'header', 'Regular Element #1');
		$this->assertResponseContentDoesNotHaveRecords($responseContent, self::TABLE_Content, 'header', 'Regular Element #2');
	}

	/**
	 * @test
	 */
	public function copyContentRecord() {
		$this->actionService->copyRecord(self::TABLE_Content, self::VALUE_ContentIdLast, self::VALUE_PageId);
		$this->assertAssertionDataSet('copyContentRecord');

		$responseContent = $this->getFrontendResponse(self::VALUE_PageId, 0, self::VALUE_BackendUserId, self::VALUE_WorkspaceId)->getResponseContent();
		$this->assertResponseContentHasRecords($responseContent, self::TABLE_Content, 'header', 'Regular Element #2 (copy 1)');
	}

	/**
	 * @test
	 */
	public function localizeContentRecord() {
		$this->actionService->localizeRecord(self::TABLE_Content, self::VALUE_ContentIdLast, self::VALUE_LanguageId);
		$this->assertAssertionDataSet('localizeContentRecord');

		$responseContent = $this->getFrontendResponse(self::VALUE_PageId, self::VALUE_LanguageId, self::VALUE_BackendUserId, self::VALUE_WorkspaceId)->getResponseContent();
		$this->assertResponseContentHasRecords($responseContent, self::TABLE_Content, 'header', array('Regular Element #1', '[Translate to Dansk:] Regular Element #2'));
	}

	/**
	 * @test
	 */
	public function changeContentRecordSorting() {
		$this->actionService->moveRecord(self::TABLE_Content, self::VALUE_ContentIdFirst, -self::VALUE_ContentIdLast);
		$this->assertAssertionDataSet('changeContentRecordSorting');

		$responseContent = $this->getFrontendResponse(self::VALUE_PageId, 0, self::VALUE_BackendUserId, self::VALUE_WorkspaceId)->getResponseContent();
		$this->assertResponseContentHasRecords($responseContent, self::TABLE_Content, 'header', array('Regular Element #1', 'Regular Element #2'));
	}

	/**
	 * @test
	 */
	public function moveContentRecordToDifferentPage() {
		$this->actionService->moveRecord(self::TABLE_Content, self::VALUE_ContentIdLast, self::VALUE_PageIdTarget);
		$this->assertAssertionDataSet('moveContentRecordToDifferentPage');

		$responseContentSource = $this->getFrontendResponse(self::VALUE_PageId, 0, self::VALUE_BackendUserId, self::VALUE_WorkspaceId)->getResponseContent();
		$this->assertResponseContentHasRecords($responseContentSource, self::TABLE_Content, 'header', 'Regular Element #1');
		$responseContentTarget = $this->getFrontendResponse(self::VALUE_PageIdTarget, 0, self::VALUE_BackendUserId, self::VALUE_WorkspaceId)->getResponseContent();
		$this->assertResponseContentHasRecords($responseContentTarget, self::TABLE_Content, 'header', 'Regular Element #2');
	}

	/**
	 * @test
	 */
	public function moveContentRecordToDifferentPageAndChangeSorting() {
		$this->markTestSkipped('Something seems to be wrong here...');
		$this->actionService->moveRecord(self::TABLE_Content, self::VALUE_ContentIdLast, self::VALUE_PageIdTarget);
		$this->actionService->moveRecord(self::TABLE_Content, self::VALUE_ContentIdFirst, -self::VALUE_ContentIdLast);
		$this->assertAssertionDataSet('moveContentRecordToDifferentPageAndChangeSorting');

		$responseContent = $this->getFrontendResponse(self::VALUE_PageIdTarget, 0, self::VALUE_BackendUserId, self::VALUE_WorkspaceId)->getResponseContent();
		$this->assertResponseContentHasRecords($responseContent, self::TABLE_Content, 'header', array('Regular Element #1', 'Regular Element #2'));
	}

	/**
	 * Page records
	 */

	/**
	 * @test
	 */
	public function createPageRecord() {
		$newTableIds = $this->actionService->createNewRecord(self::TABLE_Page, self::VALUE_PageId, array('title' => 'Testing #1'));
		$this->assertAssertionDataSet('createPageRecord');

		$newPageId = $newTableIds[self::TABLE_Page][0];
		$responseContent = $this->getFrontendResponse($newPageId, 0, self::VALUE_BackendUserId, self::VALUE_WorkspaceId)->getResponseContent();
		$this->assertResponseContentHasRecords($responseContent, self::TABLE_Page, 'title', 'Testing #1');
	}

	/**
	 * @test
	 */
	public function modifyPageRecord() {
		$this->actionService->modifyRecord(self::TABLE_Page, self::VALUE_PageId, array('title' => 'Testing #1'));
		$this->assertAssertionDataSet('modifyPageRecord');

		$responseContent = $this->getFrontendResponse(self::VALUE_PageId, 0, self::VALUE_BackendUserId, self::VALUE_WorkspaceId)->getResponseContent();
		$this->assertResponseContentHasRecords($responseContent, self::TABLE_Page, 'title', 'Testing #1');
	}

	/**
	 * @test
	 */
	public function deletePageRecord() {
		$this->actionService->deleteRecord(self::TABLE_Page, self::VALUE_PageId);
		$this->assertAssertionDataSet('deletePageRecord');

		$response = $this->getFrontendResponse(self::VALUE_PageId, 0, self::VALUE_BackendUserId, self::VALUE_WorkspaceId, FALSE);
		$this->assertContains('RuntimeException', $response->getError());
	}

	/**
	 * @test
	 */
	public function copyPageRecord() {
		$newTableIds = $this->actionService->copyRecord(self::TABLE_Page, self::VALUE_PageId, self::VALUE_PageIdTarget);
		$this->assertAssertionDataSet('copyPageRecord');

		$newPageId = $newTableIds[self::TABLE_Page][self::VALUE_PageId];
		$responseContent = $this->getFrontendResponse($newPageId, 0, self::VALUE_BackendUserId, self::VALUE_WorkspaceId)->getResponseContent();
		$this->assertResponseContentHasRecords($responseContent, self::TABLE_Page, 'title', 'Relations');
	}

	/**
	 * @test
	 */
	public function localizePageRecord() {
		$this->actionService->localizeRecord(self::TABLE_Page, self::VALUE_PageId, self::VALUE_LanguageId);
		$this->assertAssertionDataSet('localizePageRecord');

		$responseContent = $this->getFrontendResponse(self::VALUE_PageId, self::VALUE_LanguageId, self::VALUE_BackendUserId, self::VALUE_WorkspaceId)->getResponseContent();
		$this->assertResponseContentHasRecords($responseContent, self::TABLE_Page, 'title', '[Translate to Dansk:] Relations');
	}

	/**
	 * @test
	 */
	public function changePageRecordSorting() {
		$this->actionService->moveRecord(self::TABLE_Page, self::VALUE_PageId, -self::VALUE_PageIdTarget);
		$this->assertAssertionDataSet('changePageRecordSorting');

		$responseContent = $this->getFrontendResponse(self::VALUE_PageId, 0, self::VALUE_BackendUserId, self::VALUE_WorkspaceId)->getResponseContent();
		$this->assertResponseContentHasRecords($responseContent, self::TABLE_Page, 'title', 'Relations');
		$this->assertResponseContentHasRecords($responseContent, self::TABLE_Content, 'header', array('Regular Element #1', 'Regular Element #2'));
	}

	/**
	 * @test
	 */
	public function movePageRecordToDifferentPage() {
		$this->actionService->moveRecord(self::TABLE_Page, self::VALUE_PageId, self::VALUE_PageIdTarget);
		$this->assertAssertionDataSet('movePageRecordToDifferentPage');

		$responseContent = $this->getFrontendResponse(self::VALUE_PageId, 0, self::VALUE_BackendUserId, self::VALUE_WorkspaceId)->getResponseContent();
		$this->assertResponseContentHasRecords($responseContent, self::TABLE_Page, 'title', 'Relations');
		$this->assertResponseContentHasRecords($responseContent, self::TABLE_Content, 'header', array('Regular Element #1', 'Regular Element #2'));
	}

	/**
	 * @test
	 */
	public function movePageRecordToDifferentPageAndChangeSorting() {
		$this->actionService->moveRecord(self::TABLE_Page, self::VALUE_PageIdTarget, self::VALUE_PageIdWebsite);
		$this->actionService->moveRecord(self::TABLE_Page, self::VALUE_PageId, -self::VALUE_PageIdTarget);
		$this->assertAssertionDataSet('movePageRecordToDifferentPageAndChangeSorting');

		$responseContent = $this->getFrontendResponse(self::VALUE_PageId, 0, self::VALUE_BackendUserId, self::VALUE_WorkspaceId)->getResponseContent();
		$this->assertResponseContentHasRecords($responseContent, self::TABLE_Page, 'title', 'Relations');
		$this->assertResponseContentHasRecords($responseContent, self::TABLE_Content, 'header', array('Regular Element #1', 'Regular Element #2'));
	}

}
