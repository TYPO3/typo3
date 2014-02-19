<?php
namespace TYPO3\CMS\Workspaces\Tests\Functional\DataHandling\ManyToMany;

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
	const VALUE_ContentIdFirst = 297;
	const VALUE_ContentIdLast = 298;
	const VALUE_LanguageId = 1;
	const VALUE_CategoryIdFirst = 28;
	const VALUE_CategoryIdSecond = 29;
	const VALUE_WorkspaceId = 1;

	const TABLE_Content = 'tt_content';
	const TABLE_Category = 'sys_category';
	const TABLE_ContentCategory_ManyToMany = 'sys_category_record_mm';

	/**
	 * @var string
	 */
	protected $dataSetDirectory = 'typo3/sysext/workspaces/Tests/Functional/DataHandling/ManyToMany/DataSet/';

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
	 * MM Relations
	 */

	/**
	 * @test
	 */
	public function addCategoryRelation() {
		$this->actionService->modifyReferences(
			self::TABLE_Content, self::VALUE_ContentIdFirst, 'categories', array(self::VALUE_CategoryIdFirst, self::VALUE_CategoryIdSecond, 31)
		);
		$this->assertAssertionDataSet('addCategoryRelation');

		$responseContent = $this->getFrontendResponse(self::VALUE_PageId, 0, self::VALUE_BackendUserId, self::VALUE_WorkspaceId)->getResponseContent();
		$this->assertResponseContentStructureHasRecords(
			$responseContent, self::TABLE_Content . ':' . self::VALUE_ContentIdFirst, 'categories',
			self::TABLE_Category, 'title', array('Category A', 'Category B', 'Category A.A')
		);
	}

	/**
	 * @test
	 */
	public function deleteCategoryRelation() {
		$this->actionService->modifyReferences(
			self::TABLE_Content, self::VALUE_ContentIdFirst, 'categories', array(self::VALUE_CategoryIdFirst)
		);
		$this->assertAssertionDataSet('deleteCategoryRelation');

		$responseContent = $this->getFrontendResponse(self::VALUE_PageId, 0, self::VALUE_BackendUserId, self::VALUE_WorkspaceId)->getResponseContent();
		$this->assertResponseContentStructureHasRecords(
			$responseContent, self::TABLE_Content . ':' . self::VALUE_ContentIdFirst, 'categories',
			self::TABLE_Category, 'title', array('Category A')
		);
		$this->assertResponseContentStructureDoesNotHaveRecords(
			$responseContent, self::TABLE_Content . ':' . self::VALUE_ContentIdFirst, 'categories',
			self::TABLE_Category, 'title', array('Category B', 'Category C', 'Category A.A')
		);
	}

	/**
	 * @test
	 */
	public function changeCategoryRelationSorting() {
		$this->actionService->modifyReferences(
			self::TABLE_Content, self::VALUE_ContentIdFirst, 'categories', array(self::VALUE_CategoryIdSecond, self::VALUE_CategoryIdFirst)
		);
		$this->assertAssertionDataSet('changeCategoryRelationSorting');

		$responseContent = $this->getFrontendResponse(self::VALUE_PageId, 0, self::VALUE_BackendUserId, self::VALUE_WorkspaceId)->getResponseContent();
		$this->assertResponseContentStructureHasRecords(
			$responseContent, self::TABLE_Content . ':' . self::VALUE_ContentIdFirst, 'categories',
			self::TABLE_Category, 'title', array('Category A', 'Category B')
		);
	}

	/**
	 * @test
	 */
	public function createContentRecordAndAddCategoryRelation() {
		$newTableIds = $this->actionService->createNewRecord(
			self::TABLE_Content, self::VALUE_PageId, array('header' => 'Testing #1', 'categories' => self::VALUE_CategoryIdSecond)
		);
		$this->assertAssertionDataSet('createContentRecordAndAddCategoryRelation');

		$newContentId = $newTableIds[self::TABLE_Content][0];
		$responseContent = $this->getFrontendResponse(self::VALUE_PageId, 0, self::VALUE_BackendUserId, self::VALUE_WorkspaceId)->getResponseContent();
		$this->assertResponseContentHasRecords($responseContent, self::TABLE_Content, 'header', 'Testing #1');
		$this->assertResponseContentStructureHasRecords(
			$responseContent, self::TABLE_Content . ':' . $newContentId, 'categories',
			self::TABLE_Category, 'title', 'Category B'
		);
	}

	/**
	 * @test
	 */
	public function createCategoryRecordAndAddCategoryRelation() {
		$this->actionService->createNewRecord(
			self::TABLE_Category, 0, array('title' => 'Testing #1', 'items' => 'tt_content_' . self::VALUE_ContentIdFirst)
		);
		$this->assertAssertionDataSet('createCategoryRecordAndAddCategoryRelation');

		// @todo Does not work due to the core bug of not setting the reference field in the MM record
		/*
			$responseContent = $this->getFrontendResponse(self::VALUE_PageId, 0, self::VALUE_BackendUserId, self::VALUE_WorkspaceId)->getResponseContent();
			$this->assertResponseContentHasRecords($responseContent, self::TABLE_Category, 'title', 'Testing #1');
			$this->assertResponseContentStructureHasRecords(
				$responseContent, self::TABLE_Content . ':' . self::VALUE_ContentIdFirst, 'categories',
				self::TABLE_Category, 'title', 'Testing #1'
			);
		*/
	}

	/**
	 * @test
	 */
	public function createContentRecordAndCreateCategoryRelation() {
		$newTableIds = $this->actionService->createNewRecords(
			self::VALUE_PageId,
			array(
				self::TABLE_Category => array('title' => 'Testing #1'),
				self::TABLE_Content => array('header' => 'Testing #1', 'categories' => '__previousUid'),
			)
		);
		$this->assertAssertionDataSet('createContentRecordAndCreateCategoryRelation');

		$newContentId = $newTableIds[self::TABLE_Content][0];
		$responseContent = $this->getFrontendResponse(self::VALUE_PageId, 0, self::VALUE_BackendUserId, self::VALUE_WorkspaceId)->getResponseContent();
		$this->assertResponseContentHasRecords($responseContent, self::TABLE_Content, 'header', 'Testing #1');

		// @todo New category is not resolved in new content element due to core bug
		// The frontend query ignores pid=-1 and thus the specific workspace record in sys_category:33
		/*
			$this->assertResponseContentStructureHasRecords(
				$responseContent, self::TABLE_Content . ':' . $newContentId, 'categories',
				self::TABLE_Category, 'title', 'Testing #1'
			);
		*/
	}

	/**
	 * @test
	 */
	public function createCategoryRecordAndCreateCategoryRelation() {
		$this->markTestSkipped('The new content record cannot be referenced in the new category record');
		$this->actionService->createNewRecords(
			self::VALUE_PageId,
			array(
				self::TABLE_Content => array('header' => 'Testing #1',),
				self::TABLE_Category => array('title' => 'Testing #1', 'items' => 'tt_content___previousUid'),
			)
		);
		$this->assertAssertionDataSet('createCategoryRecordAndCreateCategoryRelation');
	}

	/**
	 * @test
	 */
	public function modifyCategoryRecordOfCategoryRelation() {
		$this->actionService->modifyRecord(self::TABLE_Category, self::VALUE_CategoryIdFirst, array('title' => 'Testing #1'));
		$this->assertAssertionDataSet('modifyCategoryRecordOfCategoryRelation');

		$responseContent = $this->getFrontendResponse(self::VALUE_PageId, 0, self::VALUE_BackendUserId, self::VALUE_WorkspaceId)->getResponseContent();
		$this->assertResponseContentStructureHasRecords(
			$responseContent, self::TABLE_Content . ':' . self::VALUE_ContentIdFirst, 'categories',
			self::TABLE_Category, 'title', array('Testing #1', 'Category B')
		);
	}

	/**
	 * @test
	 */
	public function modifyContentRecordOfCategoryRelation() {
		$this->actionService->modifyRecord(self::TABLE_Content, self::VALUE_ContentIdFirst, array('header' => 'Testing #1'));
		$this->assertAssertionDataSet('modifyContentRecordOfCategoryRelation');

		$responseContent = $this->getFrontendResponse(self::VALUE_PageId, 0, self::VALUE_BackendUserId, self::VALUE_WorkspaceId)->getResponseContent();
		$this->assertResponseContentHasRecords($responseContent, self::TABLE_Content, 'header', 'Testing #1');
	}

	/**
	 * @test
	 */
	public function modifyBothRecordsOfCategoryRelation() {
		$this->markTestSkipped('Using specific UIDs on both sides is not implemented yet');
		$this->actionService->modifyRecord(self::TABLE_Category, self::VALUE_CategoryIdFirst, array('title' => 'Testing #1'));
		$this->actionService->modifyRecord(self::TABLE_Content, self::VALUE_ContentIdFirst, array('header' => 'Testing #1'));
		$this->assertAssertionDataSet('modifyBothRecordsOfCategoryRelation');

		$responseContent = $this->getFrontendResponse(self::VALUE_PageId, 0, self::VALUE_BackendUserId, self::VALUE_WorkspaceId)->getResponseContent();
		$this->assertResponseContentStructureHasRecords(
			$responseContent, self::TABLE_Content . ':' . self::VALUE_ContentIdFirst, 'categories',
			self::TABLE_Category, 'title', array('Testing #1', 'Category B')
		);
		$this->assertResponseContentHasRecords($responseContent, self::TABLE_Content, 'header', 'Testing #1');
	}

	/**
	 * @test
	 */
	public function deleteContentRecordOfCategoryRelation() {
		$this->actionService->deleteRecord(self::TABLE_Content, self::VALUE_ContentIdLast);
		$this->assertAssertionDataSet('deleteContentRecordOfCategoryRelation');

		$responseContent = $this->getFrontendResponse(self::VALUE_PageId, 0, self::VALUE_BackendUserId, self::VALUE_WorkspaceId)->getResponseContent();
		$this->assertResponseContentDoesNotHaveRecords($responseContent, self::TABLE_Content, 'header', 'Testing #1');
	}

	/**
	 * @test
	 */
	public function deleteCategoryRecordOfCategoryRelation() {
		$this->actionService->deleteRecord(self::TABLE_Category, self::VALUE_CategoryIdFirst);
		$this->assertAssertionDataSet('deleteCategoryRecordOfCategoryRelation');

		$responseContent = $this->getFrontendResponse(self::VALUE_PageId, 0, self::VALUE_BackendUserId, self::VALUE_WorkspaceId)->getResponseContent();
		$this->assertResponseContentStructureDoesNotHaveRecords(
			$responseContent, self::TABLE_Content . ':' . self::VALUE_ContentIdFirst, 'categories',
			self::TABLE_Category, 'title', array('Category A')
		);
	}

	/**
	 * @test
	 */
	public function copyContentRecordOfCategoryRelation() {
		$newTableIds = $this->actionService->copyRecord(self::TABLE_Content, self::VALUE_ContentIdLast, self::VALUE_PageId);
		$this->assertAssertionDataSet('copyContentRecordOfCategoryRelation');

		$newContentId = $newTableIds[self::TABLE_Content][self::VALUE_ContentIdLast];
		$responseContent = $this->getFrontendResponse(self::VALUE_PageId, 0, self::VALUE_BackendUserId, self::VALUE_WorkspaceId)->getResponseContent();
		$this->assertResponseContentStructureHasRecords(
			$responseContent, self::TABLE_Content . ':' . $newContentId, 'categories',
			self::TABLE_Category, 'title', array('Category B', 'Category C')
		);
	}

	/**
	 * @test
	 */
	public function copyCategoryRecordOfCategoryRelation() {
		$this->actionService->copyRecord(self::TABLE_Category, self::VALUE_CategoryIdFirst, 0);
		$this->assertAssertionDataSet('copyCategoryRecordOfCategoryRelation');

		$responseContent = $this->getFrontendResponse(self::VALUE_PageId, 0, self::VALUE_BackendUserId, self::VALUE_WorkspaceId)->getResponseContent();
		$this->assertResponseContentStructureHasRecords(
			$responseContent, self::TABLE_Content . ':' . self::VALUE_ContentIdFirst, 'categories',
			self::TABLE_Category, 'title', 'Category A'
			// @todo Actually it should be twice "Category A" since the category got copied
			// The frontend query ignores pid=-1 and thus the specific workspace record in sys_category:33
			// SELECT sys_category.* FROM sys_category JOIN sys_category_record_mm ON sys_category_record_mm.uid_local = sys_category.uid WHERE sys_category.uid IN (33,28,29)
			// AND sys_category_record_mm.uid_foreign=297 AND (sys_category.sys_language_uid IN (0,-1))
			// AND sys_category.deleted=0 AND (sys_category.t3ver_wsid=0 OR sys_category.t3ver_wsid=1) AND sys_category.pid<>-1
			// ORDER BY sys_category_record_mm.sorting_foreign
			// self::TABLE_Category, 'title', array('Category A', 'Category A')
		);
	}

	/**
	 * @test
	 */
	public function localizeContentRecordOfCategoryRelation() {
		$this->actionService->localizeRecord(self::TABLE_Content, self::VALUE_ContentIdLast, self::VALUE_LanguageId);
		$this->assertAssertionDataSet('localizeContentRecordOfCategoryRelation');

		$responseContent = $this->getFrontendResponse(self::VALUE_PageId, self::VALUE_LanguageId, self::VALUE_BackendUserId, self::VALUE_WorkspaceId)->getResponseContent();
		$this->assertResponseContentStructureHasRecords(
			$responseContent, self::TABLE_Content . ':' . self::VALUE_ContentIdLast, 'categories',
			self::TABLE_Category, 'title', array('Category B', 'Category C')
		);
	}

	/**
	 * @test
	 */
	public function localizeCategoryRecordOfCategoryRelation() {
		$this->actionService->localizeRecord(self::TABLE_Category, self::VALUE_CategoryIdFirst, self::VALUE_LanguageId);
		$this->assertAssertionDataSet('localizeCategoryRecordOfCategoryRelation');

		$responseContent = $this->getFrontendResponse(self::VALUE_PageId, self::VALUE_LanguageId, self::VALUE_BackendUserId, self::VALUE_WorkspaceId)->getResponseContent();
		$this->assertResponseContentStructureHasRecords(
			$responseContent, self::TABLE_Content . ':' . self::VALUE_ContentIdFirst, 'categories',
			// @todo Category localization cannot be rendered - http://forge.typo3.org/issues/56059
			// self::TABLE_Category, 'title', array('[Translate to Dansk:] Category A', 'Category B')
			self::TABLE_Category, 'title', array('Category A', 'Category B')
		);
	}

	/**
	 * @test
	 */
	public function moveContentRecordOfCategoryRelationToDifferentPage() {
		$this->actionService->moveRecord(self::TABLE_Content, self::VALUE_ContentIdLast, self::VALUE_PageIdTarget);
		$this->assertAssertionDataSet('moveContentRecordOfCategoryRelationToDifferentPage');

		$responseContent = $this->getFrontendResponse(self::VALUE_PageIdTarget, 0, self::VALUE_BackendUserId, self::VALUE_WorkspaceId)->getResponseContent();
		$this->assertResponseContentStructureHasRecords(
			$responseContent, self::TABLE_Content . ':' . self::VALUE_ContentIdLast, 'categories',
			self::TABLE_Category, 'title', array('Category B', 'Category C')
		);
	}

}
