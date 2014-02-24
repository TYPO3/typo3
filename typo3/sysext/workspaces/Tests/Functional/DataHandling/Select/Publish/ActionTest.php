<?php
namespace TYPO3\CMS\Workspaces\Tests\Functional\DataHandling\Select\Publish;

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

require_once dirname(dirname(__FILE__)) . '/AbstractActionTestCase.php';

/**
 * Functional test for the DataHandler
 */
class ActionTest extends \TYPO3\CMS\Workspaces\Tests\Functional\DataHandling\Select\AbstractActionTestCase {

	/**
	 * @var string
	 */
	protected $assertionDataSetDirectory = 'typo3/sysext/workspaces/Tests/Functional/DataHandling/Select/Publish/DataSet/';

	/**
	 * Relations
	 */

	/**
	 * @test
	 * @see DataSet/addElementRelation.csv
	 */
	public function addElementRelation() {
		parent::addElementRelation();
		$this->actionService->publishRecord(self::TABLE_Content, self::VALUE_ContentIdFirst);
		$this->assertAssertionDataSet('addElementRelation');

		$responseContent = $this->getFrontendResponse(self::VALUE_PageId)->getResponseContent();
		$this->assertResponseContentStructureHasRecords(
			$responseContent, self::TABLE_Content . ':' . self::VALUE_ContentIdFirst, self::FIELD_ContentElement,
			self::TABLE_Element, 'title', array('Element #1', 'Element #2', 'Element #3')
		);
	}

	/**
	 * @test
	 * @see DataSet/deleteElementRelation.csv
	 */
	public function deleteElementRelation() {
		parent::deleteElementRelation();
		$this->actionService->publishRecord(self::TABLE_Content, self::VALUE_ContentIdFirst);
		$this->assertAssertionDataSet('deleteElementRelation');

		$responseContent = $this->getFrontendResponse(self::VALUE_PageId)->getResponseContent();
		$this->assertResponseContentStructureHasRecords(
			$responseContent, self::TABLE_Content . ':' . self::VALUE_ContentIdFirst, self::FIELD_ContentElement,
			self::TABLE_Element, 'title', array('Element #1')
		);
		$this->assertResponseContentStructureDoesNotHaveRecords(
			$responseContent, self::TABLE_Content . ':' . self::VALUE_ContentIdFirst, self::FIELD_ContentElement,
			self::TABLE_Element, 'title', array('Element #2', 'Element #3')
		);
	}

	/**
	 * @test
	 * @see DataSet/changeElementSorting.csv
	 */
	public function changeElementSorting() {
		parent::changeElementSorting();
		$this->actionService->publishRecord(self::TABLE_Element, self::VALUE_ElementIdFirst);
		$this->assertAssertionDataSet('changeElementSorting');

		$responseContent = $this->getFrontendResponse(self::VALUE_PageId)->getResponseContent();
		$this->assertResponseContentStructureHasRecords(
			$responseContent, self::TABLE_Content . ':' . self::VALUE_ContentIdFirst, self::FIELD_ContentElement,
			self::TABLE_Element, 'title', array('Element #1', 'Element #2')
		);
	}

	/**
	 * @test
	 * @see DataSet/changeElementRelationSorting.csv
	 */
	public function changeElementRelationSorting() {
		parent::changeElementRelationSorting();
		$this->actionService->publishRecord(self::TABLE_Content, self::VALUE_ContentIdFirst);
		$this->assertAssertionDataSet('changeElementRelationSorting');

		$responseContent = $this->getFrontendResponse(self::VALUE_PageId)->getResponseContent();
		$this->assertResponseContentStructureHasRecords(
			$responseContent, self::TABLE_Content . ':' . self::VALUE_ContentIdFirst, self::FIELD_ContentElement,
			self::TABLE_Element, 'title', array('Element #1', 'Element #2')
		);
	}

	/**
	 * @test
	 * @see DataSet/createContentNAddRelation.csv
	 */
	public function createContentAndAddElementRelation() {
		parent::createContentAndAddElementRelation();
		$this->actionService->publishRecord(self::TABLE_Content, $this->recordIds['newContentId']);
		$this->assertAssertionDataSet('createContentNAddRelation');

		$responseContent = $this->getFrontendResponse(self::VALUE_PageId)->getResponseContent();
		$this->assertResponseContentHasRecords($responseContent, self::TABLE_Content, 'header', 'Testing #1');
		$this->assertResponseContentStructureHasRecords(
			$responseContent, self::TABLE_Content . ':' . $this->recordIds['newContentId'], self::FIELD_ContentElement,
			self::TABLE_Element, 'title', 'Element #1'
		);
	}

	/**
	 * @test
	 * @see DataSet/createContentNCreateRelation.csv
	 */
	public function createContentAndCreateElementRelation() {
		parent::createContentAndCreateElementRelation();
		$this->actionService->publishRecords(
			array(
				self::TABLE_Content => array($this->recordIds['newContentId']),
				self::TABLE_Element => array($this->recordIds['newElementId']),
			)
		);
		$this->assertAssertionDataSet('createContentNCreateRelation');

		$responseContent = $this->getFrontendResponse(self::VALUE_PageId)->getResponseContent();
		$this->assertResponseContentHasRecords($responseContent, self::TABLE_Content, 'header', 'Testing #1');
		$this->assertResponseContentStructureHasRecords(
			$responseContent, self::TABLE_Content . ':' . $this->recordIds['newContentId'], self::FIELD_ContentElement,
			self::TABLE_Element, 'title', 'Testing #1'
		);
	}

	/**
	 * @test
	 * @see DataSet/modifyElementOfRelation.csv
	 */
	public function modifyElementOfRelation() {
		parent::modifyElementOfRelation();
		$this->actionService->publishRecord(self::TABLE_Element, self::VALUE_ElementIdFirst);
		$this->assertAssertionDataSet('modifyElementOfRelation');

		$responseContent = $this->getFrontendResponse(self::VALUE_PageId)->getResponseContent();
		$this->assertResponseContentStructureHasRecords(
			$responseContent, self::TABLE_Content . ':' . self::VALUE_ContentIdFirst, self::FIELD_ContentElement,
			self::TABLE_Element, 'title', array('Testing #1', 'Element #2')
		);
	}

	/**
	 * @test
	 * @see DataSet/modifyContentOfRelation.csv
	 */
	public function modifyContentOfRelation() {
		parent::modifyContentOfRelation();
		$this->actionService->publishRecord(self::TABLE_Content, self::VALUE_ContentIdFirst);
		$this->assertAssertionDataSet('modifyContentOfRelation');

		$responseContent = $this->getFrontendResponse(self::VALUE_PageId)->getResponseContent();
		$this->assertResponseContentHasRecords($responseContent, self::TABLE_Content, 'header', 'Testing #1');
	}

	/**
	 * @test
	 * @see DataSet/modifyBothSidesOfRelation.csv
	 */
	public function modifyBothSidesOfRelation() {
		parent::modifyBothSidesOfRelation();
		$this->actionService->publishRecords(
			array(
				self::TABLE_Content => array(self::VALUE_ContentIdFirst),
				self::TABLE_Element => array(self::VALUE_ElementIdFirst),
			)
		);
		$this->assertAssertionDataSet('modifyBothSidesOfRelation');

		$responseContent = $this->getFrontendResponse(self::VALUE_PageId)->getResponseContent();
		$this->assertResponseContentStructureHasRecords(
			$responseContent, self::TABLE_Content . ':' . self::VALUE_ContentIdFirst, self::FIELD_ContentElement,
			self::TABLE_Element, 'title', array('Testing #1', 'Element #2')
		);
		$this->assertResponseContentHasRecords($responseContent, self::TABLE_Content, 'header', 'Testing #1');
	}

	/**
	 * @test
	 * @see DataSet/deleteContentOfRelation.csv
	 */
	public function deleteContentOfRelation() {
		parent::deleteContentOfRelation();
		$this->actionService->publishRecord(self::TABLE_Content, self::VALUE_ContentIdLast);
		$this->assertAssertionDataSet('deleteContentOfRelation');

		$responseContent = $this->getFrontendResponse(self::VALUE_PageId)->getResponseContent();
		$this->assertResponseContentDoesNotHaveRecords($responseContent, self::TABLE_Content, 'header', 'Testing #1');
	}

	/**
	 * @test
	 * @see DataSet/deleteElementOfRelation.csv
	 */
	public function deleteElementOfRelation() {
		parent::deleteElementOfRelation();
		$this->actionService->publishRecord(self::TABLE_Element, self::VALUE_ElementIdFirst);
		$this->assertAssertionDataSet('deleteElementOfRelation');

		$responseContent = $this->getFrontendResponse(self::VALUE_PageId)->getResponseContent();
		$this->assertResponseContentStructureDoesNotHaveRecords(
			$responseContent, self::TABLE_Content . ':' . self::VALUE_ContentIdFirst, self::FIELD_ContentElement,
			self::TABLE_Element, 'title', array('Element #1')
		);
	}

	/**
	 * @test
	 * @see DataSet/copyContentOfRelation.csv
	 */
	public function copyContentOfRelation() {
		parent::copyContentOfRelation();
		$this->actionService->publishRecord(self::TABLE_Content, $this->recordIds['copiedContentId']);
		$this->assertAssertionDataSet('copyContentOfRelation');

		$responseContent = $this->getFrontendResponse(self::VALUE_PageId)->getResponseContent();
		// Referenced elements are not copied with the "parent", which is expected and correct
		$this->assertResponseContentStructureHasRecords(
			$responseContent, self::TABLE_Content . ':' . $this->recordIds['copiedContentId'], self::FIELD_ContentElement,
			self::TABLE_Element, 'title', array('Element #2', 'Element #3')
		);
	}

	/**
	 * @test
	 * @see DataSet/copyElementOfRelation.csv
	 */
	public function copyElementOfRelation() {
		parent::copyElementOfRelation();
		$this->actionService->publishRecord(self::TABLE_Element, $this->recordIds['copiedElementId']);
		$this->assertAssertionDataSet('copyElementOfRelation');

		$responseContent = $this->getFrontendResponse(self::VALUE_PageId)->getResponseContent();
		$this->assertResponseContentStructureHasRecords(
			$responseContent, self::TABLE_Content . ':' . self::VALUE_ContentIdFirst, self::FIELD_ContentElement,
			self::TABLE_Element, 'title', array('Element #1')
		);
		// Referenced elements are not updated at the "parent", which is expected and correct
		$this->assertResponseContentStructureDoesNotHaveRecords(
			$responseContent, self::TABLE_Content . ':' . self::VALUE_ContentIdFirst, self::FIELD_ContentElement,
			self::TABLE_Element, 'title', array('Element #1 (copy 1)')
		);
	}

	/**
	 * @test
	 * @see DataSet/localizeContentOfRelation.csv
	 */
	public function localizeContentOfRelation() {
		parent::localizeContentOfRelation();
		$this->actionService->publishRecord(self::TABLE_Content, $this->recordIds['localizedContentId']);
		$this->assertAssertionDataSet('localizeContentOfRelation');

		$responseContent = $this->getFrontendResponse(self::VALUE_PageId, self::VALUE_LanguageId)->getResponseContent();
		$this->assertResponseContentStructureHasRecords(
			$responseContent, self::TABLE_Content . ':' . self::VALUE_ContentIdLast, self::FIELD_ContentElement,
			self::TABLE_Element, 'title', array('Element #2', 'Element #3')
		);
	}

	/**
	 * @test
	 * @see DataSet/localizeElementOfRelation.csv
	 */
	public function localizeElementOfRelation() {
		parent::localizeElementOfRelation();
		$this->actionService->publishRecord(self::TABLE_Element, $this->recordIds['localizedElementId']);
		$this->assertAssertionDataSet('localizeElementOfRelation');

		$responseContent = $this->getFrontendResponse(self::VALUE_PageId, self::VALUE_LanguageId)->getResponseContent();
		$this->assertResponseContentStructureHasRecords(
			$responseContent, self::TABLE_Content . ':' . self::VALUE_ContentIdFirst, self::FIELD_ContentElement,
			self::TABLE_Element, 'title', array('[Translate to Dansk:] Element #1', 'Element #2')
		);
	}

	/**
	 * @test
	 * @see DataSet/moveContentOfRelationToDifferentPage.csv
	 */
	public function moveContentOfRelationToDifferentPage() {
		parent::moveContentOfRelationToDifferentPage();
		$this->actionService->publishRecord(self::TABLE_Content, self::VALUE_ContentIdLast);
		$this->assertAssertionDataSet('moveContentOfRelationToDifferentPage');

		$responseContent = $this->getFrontendResponse(self::VALUE_PageIdTarget)->getResponseContent();
		$this->assertResponseContentStructureHasRecords(
			$responseContent, self::TABLE_Content . ':' . self::VALUE_ContentIdLast, self::FIELD_ContentElement,
			self::TABLE_Element, 'title', array('Element #2', 'Element #3')
		);
	}

}
