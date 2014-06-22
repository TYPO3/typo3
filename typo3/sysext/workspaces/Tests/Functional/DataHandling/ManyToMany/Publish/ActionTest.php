<?php
namespace TYPO3\CMS\Workspaces\Tests\Functional\DataHandling\ManyToMany\Publish;

/**
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

require_once dirname(dirname(__FILE__)) . '/AbstractActionTestCase.php';

/**
 * Functional test for the DataHandler
 */
class ActionTest extends \TYPO3\CMS\Workspaces\Tests\Functional\DataHandling\ManyToMany\AbstractActionTestCase {

	/**
	 * @var string
	 */
	protected $assertionDataSetDirectory = 'typo3/sysext/workspaces/Tests/Functional/DataHandling/ManyToMany/Publish/DataSet/';

	/**
	 * MM Relations
	 */

	/**
	 * @test
	 * @see DataSet/Assertion/addCategoryRelation.csv
	 */
	public function addCategoryRelation() {
		parent::addCategoryRelation();
		$this->actionService->publishRecord(self::TABLE_Content, self::VALUE_ContentIdFirst);
		$this->assertAssertionDataSet('addCategoryRelation');

		$responseSections = $this->getFrontendResponse(self::VALUE_PageId, 0)->getResponseSections();
		$this->assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
			->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdFirst)->setRecordField('categories')
			->setTable(self::TABLE_Category)->setField('title')->setValues('Category A', 'Category B', 'Category A.A'));
	}

	/**
	 * @test
	 * @see DataSet/Assertion/deleteCategoryRelation.csv
	 */
	public function deleteCategoryRelation() {
		parent::deleteCategoryRelation();
		$this->actionService->publishRecord(self::TABLE_Content, self::VALUE_ContentIdFirst);
		$this->assertAssertionDataSet('deleteCategoryRelation');

		$responseSections = $this->getFrontendResponse(self::VALUE_PageId, 0)->getResponseSections();
		$this->assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
			->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdFirst)->setRecordField('categories')
			->setTable(self::TABLE_Category)->setField('title')->setValues('Category A'));
		$this->assertThat($responseSections, $this->getRequestSectionStructureDoesNotHaveRecordConstraint()
			->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdFirst)->setRecordField('categories')
			->setTable(self::TABLE_Category)->setField('title')->setValues('Category B', 'Category C', 'Category A.A'));
	}

	/**
	 * @test
	 * @see DataSet/Assertion/changeCategoryRelationSorting.csv
	 */
	public function changeCategoryRelationSorting() {
		parent::changeCategoryRelationSorting();
		$this->actionService->publishRecord(self::TABLE_Content, self::VALUE_ContentIdFirst);
		$this->assertAssertionDataSet('changeCategoryRelationSorting');

		$responseSections = $this->getFrontendResponse(self::VALUE_PageId, 0)->getResponseSections();
		$this->assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
			->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdFirst)->setRecordField('categories')
			->setTable(self::TABLE_Category)->setField('title')->setValues('Category A', 'Category B'));
	}

	/**
	 * @test
	 * @see DataSet/Assertion/createContentRecordAndAddCategoryRelation.csv
	 */
	public function createContentAndAddRelation() {
		parent::createContentAndAddRelation();
		$this->actionService->publishRecord(self::TABLE_Content, $this->recordIds['newContentId']);
		$this->assertAssertionDataSet('createContentNAddRelation');

		$responseSections = $this->getFrontendResponse(self::VALUE_PageId, 0)->getResponseSections();
		$this->assertThat($responseSections, $this->getRequestSectionHasRecordConstraint()
			->setTable(self::TABLE_Content)->setField('header')->setValues('Testing #1'));
		$this->assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
			->setRecordIdentifier(self::TABLE_Content . ':' . $this->recordIds['newContentId'])->setRecordField('categories')
			->setTable(self::TABLE_Category)->setField('title')->setValues('Category B'));
	}

	/**
	 * @test
	 * @see DataSet/Assertion/createCategoryRecordAndAddCategoryRelation.csv
	 */
	public function createCategoryAndAddRelation() {
		parent::createCategoryAndAddRelation();
		$this->actionService->publishRecord(self::TABLE_Category, $this->recordIds['newCategoryId']);
		$this->assertAssertionDataSet('createCategoryNAddRelation');

		$responseSections = $this->getFrontendResponse(self::VALUE_PageId, 0)->getResponseSections();
		$this->assertThat($responseSections, $this->getRequestSectionHasRecordConstraint()
			->setTable(self::TABLE_Category)->setField('title')->setValues('Testing #1'));
		$this->assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
			->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdFirst)->setRecordField('categories')
			->setTable(self::TABLE_Category)->setField('title')->setValues('Testing #1'));
	}

	/**
	 * @test
	 * @see DataSet/Assertion/createContentRecordAndCreateCategoryRelation.csv
	 */
	public function createContentAndCreateRelation() {
		parent::createContentAndCreateRelation();
		$this->actionService->publishRecords(
			array(
				self::TABLE_Category => array($this->recordIds['newCategoryId']),
				self::TABLE_Content => array($this->recordIds['newContentId']),
			)
		);
		$this->assertAssertionDataSet('createContentNCreateRelation');

		$responseSections = $this->getFrontendResponse(self::VALUE_PageId, 0)->getResponseSections();
		$this->assertThat($responseSections, $this->getRequestSectionHasRecordConstraint()
			->setTable(self::TABLE_Content)->setField('header')->setValues('Testing #1'));
		$this->assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
			->setRecordIdentifier(self::TABLE_Content . ':' . $this->recordIds['newContentId'])->setRecordField('categories')
			->setTable(self::TABLE_Category)->setField('title')->setValues('Testing #1'));
	}

	/**
	 * @test
	 * @see DataSet/Assertion/createCategoryRecordAndCreateCategoryRelation.csv
	 */
	public function createCategoryAndCreateRelation() {
		parent::createCategoryAndCreateRelation();
		$this->actionService->publishRecords(
			array(
				self::TABLE_Content => array($this->recordIds['newContentId']),
				self::TABLE_Category => array($this->recordIds['newCategoryId']),
			)
		);
		$this->actionService->publishWorkspace(self::VALUE_WorkspaceId);
		$this->assertAssertionDataSet('createCategoryNCreateRelation');
	}

	/**
	 * @test
	 * @see DataSet/Assertion/modifyCategoryRecordOfCategoryRelation.csv
	 */
	public function modifyCategoryOfRelation() {
		parent::modifyCategoryOfRelation();
		$this->actionService->publishRecord(self::TABLE_Category, self::VALUE_CategoryIdFirst);
		$this->assertAssertionDataSet('modifyCategoryOfRelation');

		$responseSections = $this->getFrontendResponse(self::VALUE_PageId, 0)->getResponseSections();
		$this->assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
			->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdFirst)->setRecordField('categories')
			->setTable(self::TABLE_Category)->setField('title')->setValues('Testing #1', 'Category B'));
	}

	/**
	 * @test
	 * @see DataSet/Assertion/modifyContentRecordOfCategoryRelation.csv
	 */
	public function modifyContentOfRelation() {
		parent::modifyContentOfRelation();
		$this->actionService->publishRecord(self::TABLE_Content, self::VALUE_ContentIdFirst);
		$this->assertAssertionDataSet('modifyContentOfRelation');

		$responseSections = $this->getFrontendResponse(self::VALUE_PageId, 0)->getResponseSections();
		$this->assertThat($responseSections, $this->getRequestSectionHasRecordConstraint()
			->setTable(self::TABLE_Content)->setField('header')->setValues('Testing #1'));
	}

	/**
	 * @test
	 * @see DataSet/Assertion/modifyBothRecordsOfCategoryRelation.csv
	 */
	public function modifyBothsOfRelation() {
		parent::modifyBothsOfRelation();
		$this->actionService->publishRecords(
			array(
				self::TABLE_Content => array(self::VALUE_ContentIdFirst),
				self::TABLE_Category => array(self::VALUE_CategoryIdFirst),
			)
		);
		$this->assertAssertionDataSet('modifyBothsOfRelation');

		$responseSections = $this->getFrontendResponse(self::VALUE_PageId, 0)->getResponseSections();
		$this->assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
			->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdFirst)->setRecordField('categories')
			->setTable(self::TABLE_Category)->setField('title')->setValues('Testing #1', 'Category B'));
		$this->assertThat($responseSections, $this->getRequestSectionHasRecordConstraint()
			->setTable(self::TABLE_Content)->setField('header')->setValues('Testing #1'));
	}

	/**
	 * @test
	 * @see DataSet/Assertion/deleteContentRecordOfCategoryRelation.csv
	 */
	public function deleteContentOfRelation() {
		parent::deleteContentOfRelation();
		$this->actionService->publishRecord(self::TABLE_Content, self::VALUE_ContentIdLast);
		$this->assertAssertionDataSet('deleteContentOfRelation');

		$responseSections = $this->getFrontendResponse(self::VALUE_PageId, 0)->getResponseSections();
		$this->assertThat($responseSections, $this->getRequestSectionDoesNotHaveRecordConstraint()
			->setTable(self::TABLE_Content)->setField('header')->setValues('Testing #1'));
	}

	/**
	 * @test
	 * @see DataSet/Assertion/deleteCategoryRecordOfCategoryRelation.csv
	 */
	public function deleteCategoryOfRelation() {
		parent::deleteCategoryOfRelation();
		$this->actionService->publishRecord(self::TABLE_Category, self::VALUE_CategoryIdFirst);
		$this->assertAssertionDataSet('deleteCategoryOfRelation');

		$responseSections = $this->getFrontendResponse(self::VALUE_PageId, 0)->getResponseSections();
		$this->assertThat($responseSections, $this->getRequestSectionStructureDoesNotHaveRecordConstraint()
			->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdFirst)->setRecordField('categories')
			->setTable(self::TABLE_Category)->setField('title')->setValues('Category A'));
	}

	/**
	 * @test
	 * @see DataSet/Assertion/copyContentRecordOfCategoryRelation.csv
	 */
	public function copyContentOfRelation() {
		parent::copyContentOfRelation();
		$this->actionService->publishRecord(self::TABLE_Content, $this->recordIds['newContentId']);
		$this->assertAssertionDataSet('copyContentOfRelation');

		$responseSections = $this->getFrontendResponse(self::VALUE_PageId, 0)->getResponseSections();
		$this->assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
			->setRecordIdentifier(self::TABLE_Content . ':' . $this->recordIds['newContentId'])->setRecordField('categories')
			->setTable(self::TABLE_Category)->setField('title')->setValues('Category B', 'Category C'));
	}

	/**
	 * @test
	 * @see DataSet/Assertion/copyCategoryRecordOfCategoryRelation.csv
	 */
	public function copyCategoryOfRelation() {
		parent::copyCategoryOfRelation();
		$this->actionService->publishRecord(self::TABLE_Category, $this->recordIds['newCategoryId']);
		$this->assertAssertionDataSet('copyCategoryOfRelation');

		$responseSections = $this->getFrontendResponse(self::VALUE_PageId, 0)->getResponseSections();
		$this->assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
			->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdFirst)->setRecordField('categories')
			->setTable(self::TABLE_Category)->setField('title')->setValues('Category A', 'Category A (copy 1)'));
	}

	/**
	 * @test
	 * @see DataSet/Assertion/localizeContentRecordOfCategoryRelation.csv
	 */
	public function localizeContentOfRelation() {
		parent::localizeContentOfRelation();
		$this->actionService->publishRecord(self::TABLE_Content, $this->recordIds['localizedContentId']);
		$this->assertAssertionDataSet('localizeContentOfRelation');

		$responseSections = $this->getFrontendResponse(self::VALUE_PageId, self::VALUE_LanguageId)->getResponseSections();
		$this->assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
			->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdLast)->setRecordField('categories')
			->setTable(self::TABLE_Category)->setField('title')->setValues('Category B', 'Category C'));
	}

	/**
	 * @test
	 * @see DataSet/Assertion/localizeCategoryRecordOfCategoryRelation.csv
	 */
	public function localizeCategoryOfRelation() {
		parent::localizeCategoryOfRelation();
		$this->actionService->publishRecord(self::TABLE_Category, $this->recordIds['localizedCategoryId']);
		$this->assertAssertionDataSet('localizeCategoryOfRelation');

		$responseSections = $this->getFrontendResponse(self::VALUE_PageId, self::VALUE_LanguageId)->getResponseSections();
		$this->assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
			->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdFirst)->setRecordField('categories')
			->setTable(self::TABLE_Category)->setField('title')->setValues('[Translate to Dansk:] Category A', 'Category B'));
	}

	/**
	 * @test
	 * @see DataSet/Assertion/moveContentRecordOfCategoryRelationToDifferentPage.csv
	 */
	public function moveContentOfRelationToDifferentPage() {
		parent::moveContentOfRelationToDifferentPage();
		$this->actionService->publishRecord(self::TABLE_Content, self::VALUE_ContentIdLast);
		$this->assertAssertionDataSet('moveContentOfRelationToDifferentPage');

		$responseSections = $this->getFrontendResponse(self::VALUE_PageIdTarget, 0)->getResponseSections();
		$this->assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
			->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdLast)->setRecordField('categories')
			->setTable(self::TABLE_Category)->setField('title')->setValues('Category B', 'Category C'));
	}

	/**
	 * @test
	 * @see DataSet/Assertion/copyPage.csv
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

		$responseSections = $this->getFrontendResponse($this->recordIds['newPageId'])->getResponseSections();
		$this->assertThat($responseSections, $this->getRequestSectionHasRecordConstraint()
			->setTable(self::TABLE_Page)->setField('title')->setValues('Relations'));
		$this->assertThat($responseSections, $this->getRequestSectionHasRecordConstraint()
			->setTable(self::TABLE_Content)->setField('header')->setValues('Regular Element #1', 'Regular Element #2'));
		$this->assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
			->setRecordIdentifier(self::TABLE_Content . ':' . $this->recordIds['newContentIdFirst'])->setRecordField('categories')
			->setTable(self::TABLE_Category)->setField('title')->setValues('Category A', 'Category B'));
		$this->assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
			->setRecordIdentifier(self::TABLE_Content . ':' . $this->recordIds['newContentIdLast'])->setRecordField('categories')
			->setTable(self::TABLE_Category)->setField('title')->setValues('Category B', 'Category C'));
	}

}
