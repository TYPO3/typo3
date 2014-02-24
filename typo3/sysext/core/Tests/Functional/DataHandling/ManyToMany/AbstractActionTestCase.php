<?php
namespace TYPO3\CMS\Core\Tests\Functional\DataHandling\ManyToMany;

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

require_once dirname(dirname(__FILE__)) . '/AbstractDataHandlerActionTestCase.php';

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

	const TABLE_Content = 'tt_content';
	const TABLE_Category = 'sys_category';
	const TABLE_ContentCategory_ManyToMany = 'sys_category_record_mm';

	/**
	 * @var string
	 */
	protected $dataSetDirectory = 'typo3/sysext/core/Tests/Functional/DataHandling/ManyToMany/DataSet/';

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
	 * @see DataSet/Assertion/addCategoryRelation.csv
	 */
	public function addCategoryRelation() {
		$this->actionService->modifyReferences(
			self::TABLE_Content, self::VALUE_ContentIdFirst, 'categories', array(self::VALUE_CategoryIdFirst, self::VALUE_CategoryIdSecond, 31)
		);
		$this->assertAssertionDataSet('addCategoryRelation');

		$responseContent = $this->getFrontendResponse(self::VALUE_PageId)->getResponseContent();
		$this->assertResponseContentStructureHasRecords(
			$responseContent, self::TABLE_Content . ':' . self::VALUE_ContentIdFirst, 'categories',
			self::TABLE_Category, 'title', array('Category A', 'Category B', 'Category A.A')
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
		$this->assertAssertionDataSet('deleteCategoryRelation');

		$responseContent = $this->getFrontendResponse(self::VALUE_PageId)->getResponseContent();
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
	 * @see DataSet/Assertion/changeCategoryRelationSorting.csv
	 */
	public function changeCategoryRelationSorting() {
		$this->actionService->modifyReferences(
			self::TABLE_Content, self::VALUE_ContentIdFirst, 'categories', array(self::VALUE_CategoryIdSecond, self::VALUE_CategoryIdFirst)
		);
		$this->assertAssertionDataSet('changeCategoryRelationSorting');

		$responseContent = $this->getFrontendResponse(self::VALUE_PageId)->getResponseContent();
		$this->assertResponseContentStructureHasRecords(
			$responseContent, self::TABLE_Content . ':' . self::VALUE_ContentIdFirst, 'categories',
			self::TABLE_Category, 'title', array('Category A', 'Category B')
		);
	}

	/**
	 * @test
	 * @see DataSet/Assertion/modifyCategoryRecordOfCategoryRelation.csv
	 */
	public function modifyCategoryOfRelation() {
		$this->actionService->modifyRecord(self::TABLE_Category, self::VALUE_CategoryIdFirst, array('title' => 'Testing #1'));
		$this->assertAssertionDataSet('modifyCategoryOfRelation');

		$responseContent = $this->getFrontendResponse(self::VALUE_PageId)->getResponseContent();
		$this->assertResponseContentStructureHasRecords(
			$responseContent, self::TABLE_Content . ':' . self::VALUE_ContentIdFirst, 'categories',
			self::TABLE_Category, 'title', array('Testing #1', 'Category B')
		);
	}

	/**
	 * @test
	 * @see DataSet/Assertion/modifyContentRecordOfCategoryRelation.csv
	 */
	public function modifyContentOfRelation() {
		$this->actionService->modifyRecord(self::TABLE_Content, self::VALUE_ContentIdFirst, array('header' => 'Testing #1'));
		$this->assertAssertionDataSet('modifyContentOfRelation');

		$responseContent = $this->getFrontendResponse(self::VALUE_PageId)->getResponseContent();
		$this->assertResponseContentHasRecords($responseContent, self::TABLE_Content, 'header', 'Testing #1');
	}

	/**
	 * @test
	 * @see DataSet/Assertion/modifyBothRecordsOfCategoryRelation.csv
	 */
	public function modifyBothsOfRelation() {
		$this->actionService->modifyRecord(self::TABLE_Category, self::VALUE_CategoryIdFirst, array('title' => 'Testing #1'));
		$this->actionService->modifyRecord(self::TABLE_Content, self::VALUE_ContentIdFirst, array('header' => 'Testing #1'));
		$this->assertAssertionDataSet('modifyBothsOfRelation');

		$responseContent = $this->getFrontendResponse(self::VALUE_PageId)->getResponseContent();
		$this->assertResponseContentStructureHasRecords(
			$responseContent, self::TABLE_Content . ':' . self::VALUE_ContentIdFirst, 'categories',
			self::TABLE_Category, 'title', array('Testing #1', 'Category B')
		);
		$this->assertResponseContentHasRecords($responseContent, self::TABLE_Content, 'header', 'Testing #1');
	}

	/**
	 * @test
	 * @see DataSet/Assertion/deleteContentRecordOfCategoryRelation.csv
	 */
	public function deleteContentOfRelation() {
		$this->actionService->deleteRecord(self::TABLE_Content, self::VALUE_ContentIdLast);
		$this->assertAssertionDataSet('deleteContentOfRelation');

		$responseContent = $this->getFrontendResponse(self::VALUE_PageId)->getResponseContent();
		$this->assertResponseContentDoesNotHaveRecords($responseContent, self::TABLE_Content, 'header', 'Testing #1');
	}

	/**
	 * @test
	 * @see DataSet/Assertion/deleteCategoryRecordOfCategoryRelation.csv
	 */
	public function deleteCategoryOfRelation() {
		$this->actionService->deleteRecord(self::TABLE_Category, self::VALUE_CategoryIdFirst);
		$this->assertAssertionDataSet('deleteCategoryOfRelation');

		$responseContent = $this->getFrontendResponse(self::VALUE_PageId)->getResponseContent();
		$this->assertResponseContentStructureDoesNotHaveRecords(
			$responseContent, self::TABLE_Content . ':' . self::VALUE_ContentIdFirst, 'categories',
			self::TABLE_Category, 'title', array('Category A')
		);
	}

	/**
	 * @test
	 * @see DataSet/Assertion/copyContentRecordOfCategoryRelation.csv
	 */
	public function copyContentOfRelation() {
		$newTableIds = $this->actionService->copyRecord(self::TABLE_Content, self::VALUE_ContentIdLast, self::VALUE_PageId);
		$this->assertAssertionDataSet('copyContentOfRelation');

		$newContentId = $newTableIds[self::TABLE_Content][self::VALUE_ContentIdLast];
		$responseContent = $this->getFrontendResponse(self::VALUE_PageId)->getResponseContent();
		$this->assertResponseContentStructureHasRecords(
			$responseContent, self::TABLE_Content . ':' . $newContentId, 'categories',
			self::TABLE_Category, 'title', array('Category B', 'Category C')
		);
	}

	/**
	 * @test
	 * @see DataSet/Assertion/copyCategoryRecordOfCategoryRelation.csv
	 */
	public function copyCategoryOfRelation() {
		$this->actionService->copyRecord(self::TABLE_Category, self::VALUE_CategoryIdFirst, 0);
		$this->assertAssertionDataSet('copyCategoryOfRelation');

		$responseContent = $this->getFrontendResponse(self::VALUE_PageId)->getResponseContent();
		$this->assertResponseContentStructureHasRecords(
			$responseContent, self::TABLE_Content . ':' . self::VALUE_ContentIdFirst, 'categories',
			self::TABLE_Category, 'title', array('Category A', 'Category A (copy 1)')
		);
	}

	/**
	 * @test
	 * @see DataSet/Assertion/localizeContentRecordOfCategoryRelation.csv
	 */
	public function localizeContentOfRelation() {
		$this->actionService->localizeRecord(self::TABLE_Content, self::VALUE_ContentIdLast, self::VALUE_LanguageId);
		$this->assertAssertionDataSet('localizeContentOfRelation');

		$responseContent = $this->getFrontendResponse(self::VALUE_PageId, self::VALUE_LanguageId)->getResponseContent();
		$this->assertResponseContentStructureHasRecords(
			$responseContent, self::TABLE_Content . ':' . self::VALUE_ContentIdLast, 'categories',
			self::TABLE_Category, 'title', array('Category B', 'Category C')
		);
	}

	/**
	 * @test
	 * @see DataSet/Assertion/localizeCategoryRecordOfCategoryRelation.csv
	 */
	public function localizeCategoryOfRelation() {
		$this->actionService->localizeRecord(self::TABLE_Category, self::VALUE_CategoryIdFirst, self::VALUE_LanguageId);
		$this->assertAssertionDataSet('localizeCategoryOfRelation');

		$responseContent = $this->getFrontendResponse(self::VALUE_PageId, self::VALUE_LanguageId)->getResponseContent();
		$this->assertResponseContentStructureHasRecords(
			$responseContent, self::TABLE_Content . ':' . self::VALUE_ContentIdFirst, 'categories',
			self::TABLE_Category, 'title', array('[Translate to Dansk:] Category A', 'Category B')
		);
	}

	/**
	 * @test
	 * @see DataSet/Assertion/moveContentRecordOfCategoryRelationToDifferentPage.csv
	 */
	public function moveContentOfRelationToDifferentPage() {
		$this->actionService->moveRecord(self::TABLE_Content, self::VALUE_ContentIdLast, self::VALUE_PageIdTarget);
		$this->assertAssertionDataSet('moveContentOfRelationToDifferentPage');

		$responseContent = $this->getFrontendResponse(self::VALUE_PageIdTarget)->getResponseContent();
		$this->assertResponseContentStructureHasRecords(
			$responseContent, self::TABLE_Content . ':' . self::VALUE_ContentIdLast, 'categories',
			self::TABLE_Category, 'title', array('Category B', 'Category C')
		);
	}

}
