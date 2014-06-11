<?php
namespace TYPO3\CMS\Workspaces\Tests\Functional\DataHandling\FAL\Publish;

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
class ActionTest extends \TYPO3\CMS\Workspaces\Tests\Functional\DataHandling\FAL\AbstractActionTestCase {

	/**
	 * @var string
	 */
	protected $assertionDataSetDirectory = 'typo3/sysext/workspaces/Tests/Functional/DataHandling/FAL/Publish/DataSet/';

	/**
	 * Content records
	 */

	/**
	 * @test
	 * @see DataSet/Assertion/modifyContentRecord.csv
	 */
	public function modifyContent() {
		parent::modifyContent();
		$this->actionService->publishRecord(self::TABLE_Content, self::VALUE_ContentIdLast);
		$this->assertAssertionDataSet('modifyContent');

		$responseContent = $this->getFrontendResponse(self::VALUE_PageId)->getResponseContent();
		$this->assertResponseContentHasRecords($responseContent, self::TABLE_Content, 'header', 'Testing #1');
		$this->assertResponseContentStructureHasRecords(
			$responseContent, self::TABLE_Content . ':' . self::VALUE_ContentIdLast, self::FIELD_ContentImage,
			self::TABLE_FileReference, 'title', array('This is Kasper', 'Taken at T3BOARD'), TRUE
		);
	}

	/**
	 * @test
	 * @see DataSet/Assertion/deleteContentRecord.csv
	 */
	public function deleteContent() {
		parent::deleteContent();
		$this->actionService->publishRecord(self::TABLE_Content, self::VALUE_ContentIdLast);
		$this->assertAssertionDataSet('deleteContent');

		$responseContent = $this->getFrontendResponse(self::VALUE_PageId)->getResponseContent();
		$this->assertResponseContentHasRecords($responseContent, self::TABLE_Content, 'header', 'Regular Element #1');
		$this->assertResponseContentDoesNotHaveRecords($responseContent, self::TABLE_Content, 'header', 'Regular Element #2');
	}

	/**
	 * @test
	 * @see DataSet/Assertion/copyContentRecord.csv
	 */
	public function copyContent() {
		parent::copyContent();
		$this->actionService->publishRecord(self::TABLE_Content, $this->recordIds['copiedContentId']);
		$this->assertAssertionDataSet('copyContent');

		$responseContent = $this->getFrontendResponse(self::VALUE_PageId)->getResponseContent();
		$this->assertResponseContentHasRecords($responseContent, self::TABLE_Content, 'header', 'Regular Element #2 (copy 1)');
		$this->assertResponseContentStructureHasRecords(
			$responseContent, self::TABLE_Content . ':' . $this->recordIds['copiedContentId'], self::FIELD_ContentImage,
			self::TABLE_FileReference, 'title', array('This is Kasper', 'Taken at T3BOARD'), TRUE
		);
	}

	/**
	 * @test
	 * @see DataSet/Assertion/localizeContentRecord.csv
	 */
	public function localizeContent() {
		parent::localizeContent();
		$this->actionService->publishRecord(self::TABLE_Content, $this->recordIds['localizedContentId']);
		$this->assertAssertionDataSet('localizeContent');

		$responseContent = $this->getFrontendResponse(self::VALUE_PageId, self::VALUE_LanguageId)->getResponseContent();
		$this->assertResponseContentHasRecords($responseContent, self::TABLE_Content, 'header', array('Regular Element #1', '[Translate to Dansk:] Regular Element #2'));

		// @todo Values in sys_file_reference are not copied during localization...
		/*
			$this->assertResponseContentStructureHasRecords(
				$responseContent, self::TABLE_Content . ':' . self::VALUE_ContentIdLast, self::FIELD_ContentImage,
				self::TABLE_FileReference, 'title', array('This is Kasper', 'Taken at T3BOARD'), TRUE
			);
		*/
	}

	/**
	 * @test
	 * @see DataSet/Assertion/changeContentRecordSorting.csv
	 */
	public function changeContentSorting() {
		parent::changeContentSorting();
		$this->actionService->publishRecord(self::TABLE_Content, self::VALUE_ContentIdFirst);
		$this->assertAssertionDataSet('changeContentSorting');

		$responseContent = $this->getFrontendResponse(self::VALUE_PageId)->getResponseContent();
		$this->assertResponseContentHasRecords($responseContent, self::TABLE_Content, 'header', array('Regular Element #1', 'Regular Element #2'));
		$this->assertResponseContentStructureHasRecords(
			$responseContent, self::TABLE_Content . ':' . self::VALUE_ContentIdFirst, self::FIELD_ContentImage,
			self::TABLE_FileReference, 'title', array('Kasper', 'T3BOARD')
		);
		$this->assertResponseContentStructureHasRecords(
			$responseContent, self::TABLE_Content . ':' . self::VALUE_ContentIdLast, self::FIELD_ContentImage,
			self::TABLE_FileReference, 'title', array('This is Kasper', 'Taken at T3BOARD'), TRUE
		);
	}

	/**
	 * @test
	 * @see DataSet/Assertion/moveContentRecordToDifferentPage.csv
	 */
	public function moveContentToDifferentPage() {
		parent::moveContentToDifferentPage();
		$this->actionService->publishRecord(self::TABLE_Content, self::VALUE_ContentIdLast);
		$this->assertAssertionDataSet('moveContentToDifferentPage');

		$responseContentSource = $this->getFrontendResponse(self::VALUE_PageId)->getResponseContent();
		$this->assertResponseContentHasRecords($responseContentSource, self::TABLE_Content, 'header', 'Regular Element #1');
		$this->assertResponseContentStructureHasRecords(
			$responseContentSource, self::TABLE_Content . ':' . self::VALUE_ContentIdFirst, self::FIELD_ContentImage,
			self::TABLE_FileReference, 'title', array('Kasper', 'T3BOARD'), TRUE
		);
		$responseContentTarget = $this->getFrontendResponse(self::VALUE_PageIdTarget)->getResponseContent();
		$this->assertResponseContentHasRecords($responseContentTarget, self::TABLE_Content, 'header', 'Regular Element #2');
		$this->assertResponseContentStructureHasRecords(
			$responseContentTarget, self::TABLE_Content . ':' . self::VALUE_ContentIdLast, self::FIELD_ContentImage,
			self::TABLE_FileReference, 'title', array('This is Kasper', 'Taken at T3BOARD'), TRUE
		);
	}

	/**
	 * @test
	 * @see DataSet/Assertion/moveContentRecordToDifferentPageAndChangeSorting.csv
	 */
	public function moveContentToDifferentPageAndChangeSorting() {
		parent::moveContentToDifferentPageAndChangeSorting();
		$this->actionService->publishRecords(
			array(
				self::TABLE_Content => array(self::VALUE_ContentIdFirst, self::VALUE_ContentIdLast),
			)
		);
		$this->assertAssertionDataSet('moveContentToDifferentPageNChangeSorting');

		$responseContent = $this->getFrontendResponse(self::VALUE_PageIdTarget)->getResponseContent();
		$this->assertResponseContentHasRecords($responseContent, self::TABLE_Content, 'header', array('Regular Element #1', 'Regular Element #2'));
		$this->assertResponseContentStructureHasRecords(
			$responseContent, self::TABLE_Content . ':' . self::VALUE_ContentIdFirst, self::FIELD_ContentImage,
			self::TABLE_FileReference, 'title', array('Kasper', 'T3BOARD'), TRUE
		);
		$this->assertResponseContentStructureHasRecords(
			$responseContent, self::TABLE_Content . ':' . self::VALUE_ContentIdLast, self::FIELD_ContentImage,
			self::TABLE_FileReference, 'title', array('This is Kasper', 'Taken at T3BOARD'), TRUE
		);
	}

	/**
	 * File references
	 */

	/**
	 * @test
	 * @see DataSets/createContentWFileReference.csv
	 */
	public function createContentWithFileReference() {
		parent::createContentWithFileReference();
		$this->actionService->publishRecord(self::TABLE_Content, $this->recordIds['newContentId']);
		$this->assertAssertionDataSet('createContentWFileReference');

		$responseContent = $this->getFrontendResponse(self::VALUE_PageId)->getResponseContent();
		$this->assertResponseContentHasRecords($responseContent, self::TABLE_Content, 'header', 'Testing #1');
		$this->assertResponseContentStructureHasRecords(
			$responseContent, self::TABLE_Content . ':' . $this->recordIds['newContentId'], self::FIELD_ContentImage,
			self::TABLE_FileReference, 'title', 'Image #1', TRUE
		);
	}

	/**
	 * @test
	 * @see DataSets/modifyContentWFileReference.csv
	 */
	public function modifyContentWithFileReference() {
		parent::modifyContentWithFileReference();
		$this->actionService->publishRecord(self::TABLE_Content, self::VALUE_ContentIdLast);
		$this->assertAssertionDataSet('modifyContentWFileReference');

		$responseContent = $this->getFrontendResponse(self::VALUE_PageId)->getResponseContent();
		$this->assertResponseContentHasRecords($responseContent, self::TABLE_Content, 'header', 'Testing #1');
		$this->assertResponseContentStructureHasRecords(
			$responseContent, self::TABLE_Content . ':' . self::VALUE_ContentIdLast, self::FIELD_ContentImage,
			self::TABLE_FileReference, 'title', array('Taken at T3BOARD', 'Image #1'), TRUE
		);
	}

	/**
	 * @test
	 * @see DataSets/modifyContentNAddFileReference.csv
	 */
	public function modifyContentAndAddFileReference() {
		parent::modifyContentAndAddFileReference();
		$this->actionService->publishRecord(self::TABLE_Content, self::VALUE_ContentIdLast);
		$this->assertAssertionDataSet('modifyContentNAddFileReference');

		$responseContent = $this->getFrontendResponse(self::VALUE_PageId)->getResponseContent();
		$this->assertResponseContentStructureHasRecords(
			$responseContent, self::TABLE_Content . ':' . self::VALUE_ContentIdLast, self::FIELD_ContentImage,
			self::TABLE_FileReference, 'title', array('Taken at T3BOARD', 'This is Kasper', 'Image #3'), TRUE
		);
	}

	/**
	 * @test
	 * @see DataSets/modifyContentNDeleteFileReference.csv
	 */
	public function modifyContentAndDeleteFileReference() {
		parent::modifyContentAndDeleteFileReference();
		$this->actionService->publishRecord(self::TABLE_Content, self::VALUE_ContentIdLast);
		$this->assertAssertionDataSet('modifyContentNDeleteFileReference');

		$responseContent = $this->getFrontendResponse(self::VALUE_PageId)->getResponseContent();
		$this->assertResponseContentStructureHasRecords(
			$responseContent, self::TABLE_Content . ':' . self::VALUE_ContentIdLast, self::FIELD_ContentImage,
			self::TABLE_FileReference, 'title', 'This is Kasper', TRUE
		);
		$this->assertResponseContentStructureDoesNotHaveRecords(
			$responseContent, self::TABLE_Content . ':' . self::VALUE_ContentIdLast, self::FIELD_ContentImage,
			self::TABLE_FileReference, 'title', 'Taken at T3BOARD'
		);
	}

	/**
	 * @test
	 * @see DataSets/modifyContentNDeleteAllFileReference.csv
	 */
	public function modifyContentAndDeleteAllFileReference() {
		parent::modifyContentAndDeleteAllFileReference();
		$this->actionService->publishRecord(self::TABLE_Content, self::VALUE_ContentIdLast);
		$this->assertAssertionDataSet('modifyContentNDeleteAllFileReference');

		$responseContent = $this->getFrontendResponse(self::VALUE_PageId)->getResponseContent();
		$this->assertResponseContentStructureDoesNotHaveRecords(
			$responseContent, self::TABLE_Content . ':' . self::VALUE_ContentIdLast, self::FIELD_ContentImage,
			self::TABLE_FileReference, 'title', array('Taken at T3BOARD', 'This is Kasper')
		);
	}

}
