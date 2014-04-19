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
	const VALUE_CategoryIdLast = 31;
	const VALUE_WorkspaceId = 1;

	const TABLE_Page = 'pages';
	const TABLE_Content = 'tt_content';
	const TABLE_Category = 'sys_category';
	const TABLE_ContentCategory_ManyToMany = 'sys_category_record_mm';

	/**
	 * @var string
	 */
	protected $scenarioDataSetDirectory = 'typo3/sysext/workspaces/Tests/Functional/DataHandling/ManyToMany/DataSet/';

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
	 * MM Relations
	 */

	/**
	 * @test
	 * @see DataSet/Assertion/addCategoryRelation.csv
	 */
	public function addCategoryRelation() {
		$this->actionService->modifyReferences(
			self::TABLE_Content, self::VALUE_ContentIdFirst, 'categories', array(self::VALUE_CategoryIdFirst, self::VALUE_CategoryIdSecond, self::VALUE_CategoryIdLast)
		);
	}

	/**
	 * @test
	 * @see DataSet/Assertion/deleteCategoryRelation.csv
	 */
	public function deleteCategoryRelation() {
		$this->actionService->modifyReferences(
			self::TABLE_Content, self::VALUE_ContentIdFirst, 'categories', array(self::VALUE_CategoryIdFirst)
		);
	}

	/**
	 * @test
	 * @see DataSet/Assertion/changeCategoryRelationSorting.csv
	 */
	public function changeCategoryRelationSorting() {
		$this->actionService->modifyReferences(
			self::TABLE_Content, self::VALUE_ContentIdFirst, 'categories', array(self::VALUE_CategoryIdSecond, self::VALUE_CategoryIdFirst)
		);
	}

	/**
	 * @test
	 * @see DataSet/Assertion/createContentRecordAndAddCategoryRelation.csv
	 */
	public function createContentAndAddRelation() {
		$newTableIds = $this->actionService->createNewRecord(
			self::TABLE_Content, self::VALUE_PageId, array('header' => 'Testing #1', 'categories' => self::VALUE_CategoryIdSecond)
		);
		$this->recordIds['newContentId'] = $newTableIds[self::TABLE_Content][0];
	}

	/**
	 * @test
	 * @see DataSet/Assertion/createCategoryRecordAndAddCategoryRelation.csv
	 */
	public function createCategoryAndAddRelation() {
		$newTableIds = $this->actionService->createNewRecord(
			self::TABLE_Category, 0, array('title' => 'Testing #1', 'items' => 'tt_content_' . self::VALUE_ContentIdFirst)
		);
		$this->recordIds['newCategoryId'] = $newTableIds[self::TABLE_Category][0];
	}

	/**
	 * @test
	 * @see DataSet/Assertion/createContentRecordAndCreateCategoryRelation.csv
	 */
	public function createContentAndCreateRelation() {
		$newTableIds = $this->actionService->createNewRecords(
			self::VALUE_PageId,
			array(
				self::TABLE_Category => array('pid' => 0, 'title' => 'Testing #1'),
				self::TABLE_Content => array('header' => 'Testing #1', 'categories' => '__previousUid'),
			)
		);
		$this->recordIds['newCategoryId'] = $newTableIds[self::TABLE_Category][0];
		$this->recordIds['newContentId'] = $newTableIds[self::TABLE_Content][0];
	}

	/**
	 * @test
	 * @see DataSet/Assertion/createCategoryRecordAndCreateCategoryRelation.csv
	 */
	public function createCategoryAndCreateRelation() {
		$newTableIds = $this->actionService->createNewRecords(
			self::VALUE_PageId,
			array(
				self::TABLE_Content => array('header' => 'Testing #1',),
				self::TABLE_Category => array('pid' => 0, 'title' => 'Testing #1', 'items' => 'tt_content___previousUid'),
			)
		);
		$this->recordIds['newContentId'] = $newTableIds[self::TABLE_Content][0];
		$this->recordIds['newCategoryId'] = $newTableIds[self::TABLE_Category][0];
	}

	/**
	 * @see DataSet/Assertion/modifyCategoryRecordOfCategoryRelation.csv
	 */
	public function modifyCategoryOfRelation() {
		$this->actionService->modifyRecord(self::TABLE_Category, self::VALUE_CategoryIdFirst, array('title' => 'Testing #1'));
	}

	/**
	 * @see DataSet/Assertion/modifyContentRecordOfCategoryRelation.csv
	 */
	public function modifyContentOfRelation() {
		$this->actionService->modifyRecord(self::TABLE_Content, self::VALUE_ContentIdFirst, array('header' => 'Testing #1'));
	}

	/**
	 * @see DataSet/Assertion/modifyBothRecordsOfCategoryRelation.csv
	 */
	public function modifyBothsOfRelation() {
		$this->actionService->modifyRecord(self::TABLE_Category, self::VALUE_CategoryIdFirst, array('title' => 'Testing #1'));
		$this->actionService->modifyRecord(self::TABLE_Content, self::VALUE_ContentIdFirst, array('header' => 'Testing #1'));
	}

	/**
	 * @see DataSet/Assertion/deleteContentRecordOfCategoryRelation.csv
	 */
	public function deleteContentOfRelation() {
		$this->actionService->deleteRecord(self::TABLE_Content, self::VALUE_ContentIdLast);
	}

	/**
	 * @see DataSet/Assertion/deleteCategoryRecordOfCategoryRelation.csv
	 */
	public function deleteCategoryOfRelation() {
		$this->actionService->deleteRecord(self::TABLE_Category, self::VALUE_CategoryIdFirst);
	}

	/**
	 * @see DataSet/Assertion/copyContentRecordOfCategoryRelation.csv
	 */
	public function copyContentOfRelation() {
		$newTableIds = $this->actionService->copyRecord(self::TABLE_Content, self::VALUE_ContentIdLast, self::VALUE_PageId);
		$this->recordIds['newContentId'] = $newTableIds[self::TABLE_Content][self::VALUE_ContentIdLast];
	}

	/**
	 * @see DataSet/Assertion/copyCategoryRecordOfCategoryRelation.csv
	 */
	public function copyCategoryOfRelation() {
		$newTableIds = $this->actionService->copyRecord(self::TABLE_Category, self::VALUE_CategoryIdFirst, 0);
		$this->recordIds['newCategoryId'] = $newTableIds[self::TABLE_Category][self::VALUE_CategoryIdFirst];
	}

	/**
	 * @see DataSet/Assertion/localizeContentRecordOfCategoryRelation.csv
	 */
	public function localizeContentOfRelation() {
		$localizedTableIds = $this->actionService->localizeRecord(self::TABLE_Content, self::VALUE_ContentIdLast, self::VALUE_LanguageId);
		$this->recordIds['localizedContentId'] = $localizedTableIds[self::TABLE_Content][self::VALUE_ContentIdLast];
	}

	/**
	 * @see DataSet/Assertion/localizeCategoryRecordOfCategoryRelation.csv
	 */
	public function localizeCategoryOfRelation() {
		$localizedTableIds = $this->actionService->localizeRecord(self::TABLE_Category, self::VALUE_CategoryIdFirst, self::VALUE_LanguageId);
		$this->recordIds['localizedCategoryId'] = $localizedTableIds[self::TABLE_Category][self::VALUE_CategoryIdFirst];
	}

	/**
	 * @see DataSet/Assertion/moveContentRecordOfCategoryRelationToDifferentPage.csv
	 */
	public function moveContentOfRelationToDifferentPage() {
		$this->actionService->moveRecord(self::TABLE_Content, self::VALUE_ContentIdLast, self::VALUE_PageIdTarget);
	}

	/**
	 * @see DataSet/Assertion/copyPage.csv
	 */
	public function copyPage() {
		$newTableIds = $this->actionService->copyRecord(self::TABLE_Page, self::VALUE_PageId, self::VALUE_PageIdTarget);
		$this->recordIds['newPageId'] = $newTableIds[self::TABLE_Page][self::VALUE_PageId];
		$this->recordIds['newContentIdFirst'] = $newTableIds[self::TABLE_Content][self::VALUE_ContentIdFirst];
		$this->recordIds['newContentIdLast'] = $newTableIds[self::TABLE_Content][self::VALUE_ContentIdLast];
	}

}
