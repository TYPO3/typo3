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
	}

	/**
	 * @test
	 */
	public function deleteCategoryRelation() {
		$this->actionService->modifyReferences(
			self::TABLE_Content, self::VALUE_ContentIdFirst, 'categories', array(self::VALUE_CategoryIdFirst)
		);
		$this->assertAssertionDataSet('deleteCategoryRelation');
	}

	/**
	 * @test
	 */
	public function changeCategoryRelationSorting() {
		$this->actionService->modifyReferences(
			self::TABLE_Content, self::VALUE_ContentIdFirst, 'categories', array(self::VALUE_CategoryIdSecond, self::VALUE_CategoryIdFirst)
		);
		$this->assertAssertionDataSet('changeCategoryRelationSorting');
	}

	/**
	 * @test
	 */
	public function modifyCategoryRecordOfCategoryRelation() {
		$this->actionService->modifyRecord(self::TABLE_Category, self::VALUE_CategoryIdFirst, array('title' => 'Testing #1'));
		$this->assertAssertionDataSet('modifyCategoryRecordOfCategoryRelation');
	}

	/**
	 * @test
	 */
	public function modifyContentRecordOfCategoryRelation() {
		$this->actionService->modifyRecord(self::TABLE_Content, self::VALUE_ContentIdFirst, array('header' => 'Testing #1'));
		$this->assertAssertionDataSet('modifyContentRecordOfCategoryRelation');
	}

	/**
	 * @test
	 */
	public function modifyBothRecordsOfCategoryRelation() {
		$this->actionService->modifyRecord(self::TABLE_Category, self::VALUE_CategoryIdFirst, array('title' => 'Testing #1'));
		$this->actionService->modifyRecord(self::TABLE_Content, self::VALUE_ContentIdFirst, array('header' => 'Testing #1'));
		$this->assertAssertionDataSet('modifyBothRecordsOfCategoryRelation');
	}

	/**
	 * @test
	 */
	public function deleteContentRecordOfCategoryRelation() {
		$this->actionService->deleteRecord(self::TABLE_Content, self::VALUE_ContentIdLast);
		$this->assertAssertionDataSet('deleteContentRecordOfCategoryRelation');
	}

	/**
	 * @test
	 */
	public function deleteCategoryRecordOfCategoryRelation() {
		$this->actionService->deleteRecord(self::TABLE_Category, self::VALUE_CategoryIdFirst);
		$this->assertAssertionDataSet('deleteCategoryRecordOfCategoryRelation');
	}

	/**
	 * @test
	 */
	public function copyContentRecordOfCategoryRelation() {
		$this->actionService->copyRecord(self::TABLE_Content, self::VALUE_ContentIdLast, self::VALUE_PageId);
		$this->assertAssertionDataSet('copyContentRecordOfCategoryRelation');
	}

	/**
	 * @test
	 */
	public function copyCategoryRecordOfCategoryRelation() {
		$this->actionService->copyRecord(self::TABLE_Category, self::VALUE_CategoryIdFirst, 0);
		$this->assertAssertionDataSet('copyCategoryRecordOfCategoryRelation');
	}

	/**
	 * @test
	 */
	public function localizeContentRecordOfCategoryRelation() {
		$this->actionService->localizeRecord(self::TABLE_Content, self::VALUE_ContentIdLast, self::VALUE_LanguageId);
		$this->assertAssertionDataSet('localizeContentRecordOfCategoryRelation');
	}

	/**
	 * @test
	 */
	public function localizeCategoryRecordOfCategoryRelation() {
		$this->actionService->localizeRecord(self::TABLE_Category, self::VALUE_CategoryIdFirst, self::VALUE_LanguageId);
		$this->assertAssertionDataSet('localizeCategoryRecordOfCategoryRelation');
	}

	/**
	 * @test
	 */
	public function moveContentRecordOfCategoryRelationToDifferentPage() {
		$this->actionService->moveRecord(self::TABLE_Content, self::VALUE_ContentIdLast, self::VALUE_PageIdTarget);
		$this->assertAssertionDataSet('moveContentRecordOfCategoryRelationToDifferentPage');
	}

}
