<?php
namespace TYPO3\CMS\Workspaces\Tests\Functional\DataHandling\FAL\Modify;

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
	protected $assertionDataSetDirectory = 'typo3/sysext/workspaces/Tests/Functional/DataHandling/FAL/Modify/DataSet/';

	/**
	 * Content records
	 */

	/**
	 * @test
	 * @see DataSet/Assertion/modifyContentRecord.csv
	 */
	public function modifyContent() {
		parent::modifyContent();
		$this->assertAssertionDataSet('modifyContent');

		$responseSections = $this->getFrontendResponse(self::VALUE_PageId, 0, self::VALUE_BackendUserId, self::VALUE_WorkspaceId)->getResponseSections();
		$this->assertThat($responseSections, $this->getRequestSectionHasRecordConstraint()
			->setTable(self::TABLE_Content)->setField('header')->setValues('Testing #1'));
		$this->assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
			->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdLast)->setRecordField(self::FIELD_ContentImage)
			->setTable(self::TABLE_FileReference)->setField('title')->setValues('This is Kasper', 'Taken at T3BOARD')->setStrict(TRUE));
	}

	/**
	 * @test
	 * @see DataSet/Assertion/deleteContentRecord.csv
	 */
	public function deleteContent() {
		parent::deleteContent();
		$this->assertAssertionDataSet('deleteContent');

		$responseSections = $this->getFrontendResponse(self::VALUE_PageId, 0, self::VALUE_BackendUserId, self::VALUE_WorkspaceId)->getResponseSections();
		$this->assertThat($responseSections, $this->getRequestSectionHasRecordConstraint()
			->setTable(self::TABLE_Content)->setField('header')->setValues('Regular Element #1'));
		$this->assertThat($responseSections, $this->getRequestSectionDoesNotHaveRecordConstraint()
			->setTable(self::TABLE_Content)->setField('header')->setValues('Regular Element #2'));
	}

	/**
	 * @test
	 * @see DataSet/Assertion/copyContentRecord.csv
	 */
	public function copyContent() {
		parent::copyContent();
		$this->assertAssertionDataSet('copyContent');

		$responseSections = $this->getFrontendResponse(self::VALUE_PageId, 0, self::VALUE_BackendUserId, self::VALUE_WorkspaceId)->getResponseSections();
		$this->assertThat($responseSections, $this->getRequestSectionHasRecordConstraint()
			->setTable(self::TABLE_Content)->setField('header')->setValues('Regular Element #2 (copy 1)'));
		$this->assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
			->setRecordIdentifier(self::TABLE_Content . ':' . $this->recordIds['copiedContentId'])->setRecordField(self::FIELD_ContentImage)
			->setTable(self::TABLE_FileReference)->setField('title')->setValues('This is Kasper', 'Taken at T3BOARD')->setStrict(TRUE));
	}

	/**
	 * @test
	 * @see DataSet/Assertion/localizeContentRecord.csv
	 */
	public function localizeContent() {
		parent::localizeContent();
		$this->assertAssertionDataSet('localizeContent');

		$responseSections = $this->getFrontendResponse(self::VALUE_PageId, self::VALUE_LanguageId, self::VALUE_BackendUserId, self::VALUE_WorkspaceId)->getResponseSections();
		$this->assertThat($responseSections, $this->getRequestSectionHasRecordConstraint()
			->setTable(self::TABLE_Content)->setField('header')->setValues('Regular Element #1', '[Translate to Dansk:] Regular Element #2'));

		// @todo Values in sys_file_reference are not copied during localization...
		/*
			$this->assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
			->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdLast)->setRecordField(self::FIELD_ContentImage)
			->setTable(self::TABLE_FileReference)->setField('title')->setValues('This is Kasper', 'Taken at T3BOARD')->setStrict(TRUE));
		*/
	}

	/**
	 * @test
	 * @see DataSet/Assertion/changeContentRecordSorting.csv
	 */
	public function changeContentSorting() {
		parent::changeContentSorting();
		$this->assertAssertionDataSet('changeContentSorting');

		$responseSections = $this->getFrontendResponse(self::VALUE_PageId, 0, self::VALUE_BackendUserId, self::VALUE_WorkspaceId)->getResponseSections();
		$this->assertThat($responseSections, $this->getRequestSectionHasRecordConstraint()
			->setTable(self::TABLE_Content)->setField('header')->setValues('Regular Element #1', 'Regular Element #2'));
		$this->assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
			->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdFirst)->setRecordField(self::FIELD_ContentImage)
			->setTable(self::TABLE_FileReference)->setField('title')->setValues('Kasper', 'T3BOARD'));
		$this->assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
			->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdLast)->setRecordField(self::FIELD_ContentImage)
			->setTable(self::TABLE_FileReference)->setField('title')->setValues('This is Kasper', 'Taken at T3BOARD')->setStrict(TRUE));
	}

	/**
	 * @test
	 * @see DataSet/Assertion/moveContentRecordToDifferentPage.csv
	 */
	public function moveContentToDifferentPage() {
		parent::moveContentToDifferentPage();
		$this->assertAssertionDataSet('moveContentToDifferentPage');

		$responseSectionsSource = $this->getFrontendResponse(self::VALUE_PageId, 0, self::VALUE_BackendUserId, self::VALUE_WorkspaceId)->getResponseSections();
		$this->assertThat($responseSectionsSource, $this->getRequestSectionHasRecordConstraint()
			->setTable(self::TABLE_Content)->setField('header')->setValues('Regular Element #1'));
		$this->assertThat($responseSectionsSource, $this->getRequestSectionStructureHasRecordConstraint()
			->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdFirst)->setRecordField(self::FIELD_ContentImage)
			->setTable(self::TABLE_FileReference)->setField('title')->setValues('Kasper', 'T3BOARD')->setStrict(TRUE));

		$responseSectionsTarget = $this->getFrontendResponse(self::VALUE_PageIdTarget, 0, self::VALUE_BackendUserId, self::VALUE_WorkspaceId)->getResponseSections();
		$this->assertThat($responseSectionsTarget, $this->getRequestSectionHasRecordConstraint()
			->setTable(self::TABLE_Content)->setField('header')->setValues('Regular Element #2'));
		$this->assertThat($responseSectionsTarget, $this->getRequestSectionStructureHasRecordConstraint()
			->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdLast)->setRecordField(self::FIELD_ContentImage)
			->setTable(self::TABLE_FileReference)->setField('title')->setValues('This is Kasper', 'Taken at T3BOARD')->setStrict(TRUE));
	}

	/**
	 * @test
	 * @see DataSet/Assertion/moveContentRecordToDifferentPageAndChangeSorting.csv
	 */
	public function moveContentToDifferentPageAndChangeSorting() {
		parent::moveContentToDifferentPageAndChangeSorting();
		$this->assertAssertionDataSet('moveContentToDifferentPageNChangeSorting');

		$responseSections = $this->getFrontendResponse(self::VALUE_PageIdTarget, 0, self::VALUE_BackendUserId, self::VALUE_WorkspaceId)->getResponseSections();
		$this->assertThat($responseSections, $this->getRequestSectionHasRecordConstraint()
			->setTable(self::TABLE_Content)->setField('header')->setValues('Regular Element #1', 'Regular Element #2'));
		$this->assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
			->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdFirst)->setRecordField(self::FIELD_ContentImage)
			->setTable(self::TABLE_FileReference)->setField('title')->setValues('Kasper', 'T3BOARD')->setStrict(TRUE));
		$this->assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
			->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdLast)->setRecordField(self::FIELD_ContentImage)
			->setTable(self::TABLE_FileReference)->setField('title')->setValues('This is Kasper', 'Taken at T3BOARD')->setStrict(TRUE));
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
		$this->assertAssertionDataSet('createContentWFileReference');

		$responseSections = $this->getFrontendResponse(self::VALUE_PageId, 0, self::VALUE_BackendUserId, self::VALUE_WorkspaceId)->getResponseSections();
		$this->assertThat($responseSections, $this->getRequestSectionHasRecordConstraint()
			->setTable(self::TABLE_Content)->setField('header')->setValues('Testing #1'));
		$this->assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
			->setRecordIdentifier(self::TABLE_Content . ':' . $this->recordIds['newContentId'])->setRecordField(self::FIELD_ContentImage)
			->setTable(self::TABLE_FileReference)->setField('title')->setValues('Image #1')->setStrict(TRUE));
	}

	/**
	 * @test
	 * @see DataSets/modifyContentWFileReference.csv
	 */
	public function modifyContentWithFileReference() {
		parent::modifyContentWithFileReference();
		$this->assertAssertionDataSet('modifyContentWFileReference');

		$responseSections = $this->getFrontendResponse(self::VALUE_PageId, 0, self::VALUE_BackendUserId, self::VALUE_WorkspaceId)->getResponseSections();
		$this->assertThat($responseSections, $this->getRequestSectionHasRecordConstraint()
			->setTable(self::TABLE_Content)->setField('header')->setValues('Testing #1'));
		$this->assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
			->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdLast)->setRecordField(self::FIELD_ContentImage)
			->setTable(self::TABLE_FileReference)->setField('title')->setValues('Taken at T3BOARD', 'Image #1')->setStrict(TRUE));
	}

	/**
	 * @test
	 * @see DataSets/modifyContentNAddFileReference.csv
	 */
	public function modifyContentAndAddFileReference() {
		parent::modifyContentAndAddFileReference();
		$this->assertAssertionDataSet('modifyContentNAddFileReference');

		$responseSections = $this->getFrontendResponse(self::VALUE_PageId, 0, self::VALUE_BackendUserId, self::VALUE_WorkspaceId)->getResponseSections();
		$this->assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
			->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdLast)->setRecordField(self::FIELD_ContentImage)
			->setTable(self::TABLE_FileReference)->setField('title')->setValues('Taken at T3BOARD', 'This is Kasper', 'Image #3')->setStrict(TRUE));
	}

	/**
	 * @test
	 * @see DataSets/modifyContentNDeleteFileReference.csv
	 */
	public function modifyContentAndDeleteFileReference() {
		parent::modifyContentAndDeleteFileReference();
		$this->assertAssertionDataSet('modifyContentNDeleteFileReference');

		$responseSections = $this->getFrontendResponse(self::VALUE_PageId, 0, self::VALUE_BackendUserId, self::VALUE_WorkspaceId)->getResponseSections();
		$this->assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
			->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdLast)->setRecordField(self::FIELD_ContentImage)
			->setTable(self::TABLE_FileReference)->setField('title')->setValues('This is Kasper')->setStrict(TRUE));
		$this->assertThat($responseSections, $this->getRequestSectionStructureDoesNotHaveRecordConstraint()
			->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdLast)->setRecordField(self::FIELD_ContentImage)
			->setTable(self::TABLE_FileReference)->setField('title')->setValues('Taken at T3BOARD'));
	}

	/**
	 * @test
	 * @see DataSets/modifyContentNDeleteAllFileReference.csv
	 */
	public function modifyContentAndDeleteAllFileReference() {
		parent::modifyContentAndDeleteAllFileReference();
		$this->assertAssertionDataSet('modifyContentNDeleteAllFileReference');

		$responseSections = $this->getFrontendResponse(self::VALUE_PageId, 0, self::VALUE_BackendUserId, self::VALUE_WorkspaceId)->getResponseSections();
		$this->assertThat($responseSections, $this->getRequestSectionStructureDoesNotHaveRecordConstraint()
			->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdLast)->setRecordField(self::FIELD_ContentImage)
			->setTable(self::TABLE_FileReference)->setField('title')->setValues('Taken at T3BOARD', 'This is Kasper'));
	}

}
