<?php
namespace TYPO3\CMS\Workspaces\Tests\Functional\DataHandling\FAL\PublishAll;

/*
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

/**
 * Functional test for the DataHandler
 */
class ActionTest extends \TYPO3\CMS\Workspaces\Tests\Functional\DataHandling\FAL\AbstractActionTestCase
{
    /**
     * @var string
     */
    protected $assertionDataSetDirectory = 'typo3/sysext/workspaces/Tests/Functional/DataHandling/FAL/PublishAll/DataSet/';

    /**
     * Content records
     */

    /**
     * @test
     * @see DataSet/modifyContentRecord.csv
     */
    public function modifyContent()
    {
        parent::modifyContent();
        $this->actionService->publishWorkspace(self::VALUE_WorkspaceId);
        $this->assertAssertionDataSet('modifyContent');

        $responseSections = $this->getFrontendResponse(self::VALUE_PageId)->getResponseSections();
        $this->assertThat($responseSections, $this->getRequestSectionHasRecordConstraint()
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Testing #1'));
        $this->assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdLast)->setRecordField(self::FIELD_ContentImage)
            ->setTable(self::TABLE_FileReference)->setField('title')->setValues('This is Kasper', 'Taken at T3BOARD')->setStrict(true));
    }

    /**
     * @test
     * @see DataSet/deleteContentRecord.csv
     */
    public function deleteContent()
    {
        parent::deleteContent();
        $this->actionService->publishWorkspace(self::VALUE_WorkspaceId);
        $this->assertAssertionDataSet('deleteContent');

        $responseSections = $this->getFrontendResponse(self::VALUE_PageId)->getResponseSections();
        $this->assertThat($responseSections, $this->getRequestSectionHasRecordConstraint()
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Regular Element #1'));
        $this->assertThat($responseSections, $this->getRequestSectionDoesNotHaveRecordConstraint()
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Regular Element #2'));
    }

    /**
     * @test
     * @see DataSet/copyContentRecord.csv
     */
    public function copyContent()
    {
        parent::copyContent();
        $this->actionService->publishWorkspace(self::VALUE_WorkspaceId);
        $this->assertAssertionDataSet('copyContent');

        $responseSections = $this->getFrontendResponse(self::VALUE_PageId)->getResponseSections();
        $this->assertThat($responseSections, $this->getRequestSectionHasRecordConstraint()
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Regular Element #2 (copy 1)'));
        $this->assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':' . $this->recordIds['copiedContentId'])->setRecordField(self::FIELD_ContentImage)
            ->setTable(self::TABLE_FileReference)->setField('title')->setValues('This is Kasper', 'Taken at T3BOARD')->setStrict(true));
    }

    /**
     * @test
     * @see DataSet/localizeContentRecord.csv
     */
    public function localizeContent()
    {
        parent::localizeContent();
        $this->actionService->publishWorkspace(self::VALUE_WorkspaceId);
        $this->assertAssertionDataSet('localizeContent');

        $responseSections = $this->getFrontendResponse(self::VALUE_PageId, self::VALUE_LanguageId)->getResponseSections();
        $this->assertThat($responseSections, $this->getRequestSectionHasRecordConstraint()
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Regular Element #1', '[Translate to Dansk:] Regular Element #2'));

        $this->assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdLast)->setRecordField(self::FIELD_ContentImage)
            ->setTable(self::TABLE_FileReference)->setField('title')->setValues('[Translate to Dansk:] This is Kasper', '[Translate to Dansk:] Taken at T3BOARD')->setStrict(true));
    }

    /**
     * @test
     * @see DataSet/changeContentRecordSorting.csv
     */
    public function changeContentSorting()
    {
        parent::changeContentSorting();
        $this->actionService->publishWorkspace(self::VALUE_WorkspaceId);
        $this->assertAssertionDataSet('changeContentSorting');

        $responseSections = $this->getFrontendResponse(self::VALUE_PageId)->getResponseSections();
        $this->assertThat($responseSections, $this->getRequestSectionHasRecordConstraint()
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Regular Element #1', 'Regular Element #2'));
        $this->assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdFirst)->setRecordField(self::FIELD_ContentImage)
            ->setTable(self::TABLE_FileReference)->setField('title')->setValues('Kasper', 'T3BOARD'));
        $this->assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdLast)->setRecordField(self::FIELD_ContentImage)
            ->setTable(self::TABLE_FileReference)->setField('title')->setValues('This is Kasper', 'Taken at T3BOARD')->setStrict(true));
    }

    /**
     * @test
     * @see DataSet/moveContentRecordToDifferentPage.csv
     */
    public function moveContentToDifferentPage()
    {
        parent::moveContentToDifferentPage();
        $this->actionService->publishWorkspace(self::VALUE_WorkspaceId);
        $this->assertAssertionDataSet('moveContentToDifferentPage');

        $responseSectionsSource = $this->getFrontendResponse(self::VALUE_PageId)->getResponseSections();
        $this->assertThat($responseSectionsSource, $this->getRequestSectionHasRecordConstraint()
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Regular Element #1'));
        $this->assertThat($responseSectionsSource, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdFirst)->setRecordField(self::FIELD_ContentImage)
            ->setTable(self::TABLE_FileReference)->setField('title')->setValues('Kasper', 'T3BOARD')->setStrict(true));
        $responseSectionsTarget = $this->getFrontendResponse(self::VALUE_PageIdTarget)->getResponseSections();
        $this->assertThat($responseSectionsTarget, $this->getRequestSectionHasRecordConstraint()
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Regular Element #2'));
        $this->assertThat($responseSectionsTarget, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdLast)->setRecordField(self::FIELD_ContentImage)
            ->setTable(self::TABLE_FileReference)->setField('title')->setValues('This is Kasper', 'Taken at T3BOARD')->setStrict(true));
    }

    /**
     * @test
     * @see DataSet/moveContentRecordToDifferentPageAndChangeSorting.csv
     */
    public function moveContentToDifferentPageAndChangeSorting()
    {
        parent::moveContentToDifferentPageAndChangeSorting();
        $this->actionService->publishWorkspace(self::VALUE_WorkspaceId);
        $this->assertAssertionDataSet('moveContentToDifferentPageNChangeSorting');

        $responseSections = $this->getFrontendResponse(self::VALUE_PageIdTarget)->getResponseSections();
        $this->assertThat($responseSections, $this->getRequestSectionHasRecordConstraint()
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Regular Element #1', 'Regular Element #2'));
        $this->assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdFirst)->setRecordField(self::FIELD_ContentImage)
            ->setTable(self::TABLE_FileReference)->setField('title')->setValues('Kasper', 'T3BOARD')->setStrict(true));
        $this->assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdLast)->setRecordField(self::FIELD_ContentImage)
            ->setTable(self::TABLE_FileReference)->setField('title')->setValues('This is Kasper', 'Taken at T3BOARD')->setStrict(true));
    }

    /**
     * File references
     */

    /**
     * @test
     * @see DataSets/createContentWFileReference.csv
     */
    public function createContentWithFileReference()
    {
        parent::createContentWithFileReference();
        $this->actionService->publishWorkspace(self::VALUE_WorkspaceId);
        $this->assertAssertionDataSet('createContentWFileReference');

        $responseSections = $this->getFrontendResponse(self::VALUE_PageId)->getResponseSections();
        $this->assertThat($responseSections, $this->getRequestSectionHasRecordConstraint()
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Testing #1'));
        $this->assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':' . $this->recordIds['newContentId'])->setRecordField(self::FIELD_ContentImage)
            ->setTable(self::TABLE_FileReference)->setField('title')->setValues('Image #1')->setStrict(true));
    }

    /**
     * @test
     * @see DataSets/modifyContentWFileReference.csv
     */
    public function modifyContentWithFileReference()
    {
        parent::modifyContentWithFileReference();
        $this->actionService->publishWorkspace(self::VALUE_WorkspaceId);
        $this->assertAssertionDataSet('modifyContentWFileReference');

        $responseSections = $this->getFrontendResponse(self::VALUE_PageId)->getResponseSections();
        $this->assertThat($responseSections, $this->getRequestSectionHasRecordConstraint()
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Testing #1'));
        $this->assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdLast)->setRecordField(self::FIELD_ContentImage)
            ->setTable(self::TABLE_FileReference)->setField('title')->setValues('Taken at T3BOARD', 'Image #1')->setStrict(true));
    }

    /**
     * @test
     * @see DataSets/modifyContentNAddFileReference.csv
     */
    public function modifyContentAndAddFileReference()
    {
        parent::modifyContentAndAddFileReference();
        $this->actionService->publishWorkspace(self::VALUE_WorkspaceId);
        $this->assertAssertionDataSet('modifyContentNAddFileReference');

        $responseSections = $this->getFrontendResponse(self::VALUE_PageId)->getResponseSections();
        $this->assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdLast)->setRecordField(self::FIELD_ContentImage)
            ->setTable(self::TABLE_FileReference)->setField('title')->setValues('Taken at T3BOARD', 'This is Kasper', 'Image #3')->setStrict(true));
    }

    /**
     * @test
     * @see DataSets/modifyContentNDeleteFileReference.csv
     */
    public function modifyContentAndDeleteFileReference()
    {
        parent::modifyContentAndDeleteFileReference();
        $this->actionService->publishWorkspace(self::VALUE_WorkspaceId);
        $this->assertAssertionDataSet('modifyContentNDeleteFileReference');

        $responseSections = $this->getFrontendResponse(self::VALUE_PageId)->getResponseSections();
        $this->assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdLast)->setRecordField(self::FIELD_ContentImage)
            ->setTable(self::TABLE_FileReference)->setField('title')->setValues('This is Kasper')->setStrict(true));
        $this->assertThat($responseSections, $this->getRequestSectionStructureDoesNotHaveRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdLast)->setRecordField(self::FIELD_ContentImage)
            ->setTable(self::TABLE_FileReference)->setField('title')->setValues('Taken at T3BOARD'));
    }

    /**
     * @test
     * @see DataSets/modifyContentNDeleteAllFileReference.csv
     */
    public function modifyContentAndDeleteAllFileReference()
    {
        parent::modifyContentAndDeleteAllFileReference();
        $this->actionService->publishWorkspace(self::VALUE_WorkspaceId);
        $this->assertAssertionDataSet('modifyContentNDeleteAllFileReference');

        $responseSections = $this->getFrontendResponse(self::VALUE_PageId)->getResponseSections();
        $this->assertThat($responseSections, $this->getRequestSectionStructureDoesNotHaveRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdLast)->setRecordField(self::FIELD_ContentImage)
            ->setTable(self::TABLE_FileReference)->setField('title')->setValues('Taken at T3BOARD', 'This is Kasper'));
    }
}
