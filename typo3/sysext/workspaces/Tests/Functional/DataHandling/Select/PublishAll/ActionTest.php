<?php
namespace TYPO3\CMS\Workspaces\Tests\Functional\DataHandling\Select\PublishAll;

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
class ActionTest extends \TYPO3\CMS\Workspaces\Tests\Functional\DataHandling\Select\AbstractActionTestCase
{
    /**
     * @var string
     */
    protected $assertionDataSetDirectory = 'typo3/sysext/workspaces/Tests/Functional/DataHandling/Select/PublishAll/DataSet/';

    /**
     * Relations
     */

    /**
     * @test
     * @see DataSet/addElementRelation.csv
     */
    public function addElementRelation()
    {
        parent::addElementRelation();
        $this->actionService->publishWorkspace(self::VALUE_WorkspaceId);
        $this->assertAssertionDataSet('addElementRelation');

        $responseSections = $this->getFrontendResponse(self::VALUE_PageId)->getResponseSections();
        $this->assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdFirst)->setRecordField(self::FIELD_ContentElement)
            ->setTable(self::TABLE_Element)->setField('title')->setValues('Element #1', 'Element #2', 'Element #3'));
    }

    /**
     * @test
     * @see DataSet/deleteElementRelation.csv
     */
    public function deleteElementRelation()
    {
        parent::deleteElementRelation();
        $this->actionService->publishWorkspace(self::VALUE_WorkspaceId);
        $this->assertAssertionDataSet('deleteElementRelation');

        $responseSections = $this->getFrontendResponse(self::VALUE_PageId)->getResponseSections();
        $this->assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdFirst)->setRecordField(self::FIELD_ContentElement)
            ->setTable(self::TABLE_Element)->setField('title')->setValues('Element #1'));
        $this->assertThat($responseSections, $this->getRequestSectionStructureDoesNotHaveRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdFirst)->setRecordField(self::FIELD_ContentElement)
            ->setTable(self::TABLE_Element)->setField('title')->setValues('Element #2', 'Element #3'));
    }

    /**
     * @test
     * @see DataSet/changeElementSorting.csv
     */
    public function changeElementSorting()
    {
        parent::changeElementSorting();
        $this->actionService->publishWorkspace(self::VALUE_WorkspaceId);
        $this->assertAssertionDataSet('changeElementSorting');

        $responseSections = $this->getFrontendResponse(self::VALUE_PageId)->getResponseSections();
        $this->assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdFirst)->setRecordField(self::FIELD_ContentElement)
            ->setTable(self::TABLE_Element)->setField('title')->setValues('Element #1', 'Element #2'));
    }

    /**
     * @test
     * @see DataSet/changeElementRelationSorting.csv
     */
    public function changeElementRelationSorting()
    {
        parent::changeElementRelationSorting();
        $this->actionService->publishWorkspace(self::VALUE_WorkspaceId);
        $this->assertAssertionDataSet('changeElementRelationSorting');

        $responseSections = $this->getFrontendResponse(self::VALUE_PageId)->getResponseSections();
        $this->assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdFirst)->setRecordField(self::FIELD_ContentElement)
            ->setTable(self::TABLE_Element)->setField('title')->setValues('Element #1', 'Element #2'));
    }

    /**
     * @test
     * @see DataSet/createContentNAddRelation.csv
     */
    public function createContentAndAddElementRelation()
    {
        parent::createContentAndAddElementRelation();
        $this->actionService->publishWorkspace(self::VALUE_WorkspaceId);
        $this->assertAssertionDataSet('createContentNAddRelation');

        $responseSections = $this->getFrontendResponse(self::VALUE_PageId)->getResponseSections();
        $this->assertThat($responseSections, $this->getRequestSectionHasRecordConstraint()
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Testing #1'));
        $this->assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':' . $this->recordIds['newContentId'])->setRecordField(self::FIELD_ContentElement)
            ->setTable(self::TABLE_Element)->setField('title')->setValues('Element #1'));
    }

    /**
     * @test
     * @see DataSet/createContentNCreateRelation.csv
     */
    public function createContentAndCreateElementRelation()
    {
        parent::createContentAndCreateElementRelation();
        $this->actionService->publishWorkspace(self::VALUE_WorkspaceId);
        $this->assertAssertionDataSet('createContentNCreateRelation');

        $responseSections = $this->getFrontendResponse(self::VALUE_PageId)->getResponseSections();
        $this->assertThat($responseSections, $this->getRequestSectionHasRecordConstraint()
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Testing #1'));
        $this->assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':' . $this->recordIds['newContentId'])->setRecordField(self::FIELD_ContentElement)
            ->setTable(self::TABLE_Element)->setField('title')->setValues('Testing #1'));
    }

    /**
     * @test
     * @see DataSet/modifyElementOfRelation.csv
     */
    public function modifyElementOfRelation()
    {
        parent::modifyElementOfRelation();
        $this->actionService->publishWorkspace(self::VALUE_WorkspaceId);
        $this->assertAssertionDataSet('modifyElementOfRelation');

        $responseSections = $this->getFrontendResponse(self::VALUE_PageId)->getResponseSections();
        $this->assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdFirst)->setRecordField(self::FIELD_ContentElement)
            ->setTable(self::TABLE_Element)->setField('title')->setValues('Testing #1', 'Element #2'));
    }

    /**
     * @test
     * @see DataSet/modifyContentOfRelation.csv
     */
    public function modifyContentOfRelation()
    {
        parent::modifyContentOfRelation();
        $this->actionService->publishWorkspace(self::VALUE_WorkspaceId);
        $this->assertAssertionDataSet('modifyContentOfRelation');

        $responseSections = $this->getFrontendResponse(self::VALUE_PageId)->getResponseSections();
        $this->assertThat($responseSections, $this->getRequestSectionHasRecordConstraint()
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Testing #1'));
    }

    /**
     * @test
     * @see DataSet/modifyBothSidesOfRelation.csv
     */
    public function modifyBothSidesOfRelation()
    {
        parent::modifyBothSidesOfRelation();
        $this->actionService->publishWorkspace(self::VALUE_WorkspaceId);
        $this->assertAssertionDataSet('modifyBothSidesOfRelation');

        $responseSections = $this->getFrontendResponse(self::VALUE_PageId)->getResponseSections();
        $this->assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdFirst)->setRecordField(self::FIELD_ContentElement)
            ->setTable(self::TABLE_Element)->setField('title')->setValues('Testing #1', 'Element #2'));
        $this->assertThat($responseSections, $this->getRequestSectionHasRecordConstraint()
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Testing #1'));
    }

    /**
     * @test
     * @see DataSet/deleteContentOfRelation.csv
     */
    public function deleteContentOfRelation()
    {
        parent::deleteContentOfRelation();
        $this->actionService->publishWorkspace(self::VALUE_WorkspaceId);
        $this->assertAssertionDataSet('deleteContentOfRelation');

        $responseSections = $this->getFrontendResponse(self::VALUE_PageId)->getResponseSections();
        $this->assertThat($responseSections, $this->getRequestSectionDoesNotHaveRecordConstraint()
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Testing #1'));
    }

    /**
     * @test
     * @see DataSet/deleteElementOfRelation.csv
     */
    public function deleteElementOfRelation()
    {
        parent::deleteElementOfRelation();
        $this->actionService->publishWorkspace(self::VALUE_WorkspaceId);
        $this->assertAssertionDataSet('deleteElementOfRelation');

        $responseSections = $this->getFrontendResponse(self::VALUE_PageId)->getResponseSections();
        $this->assertThat($responseSections, $this->getRequestSectionStructureDoesNotHaveRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdFirst)->setRecordField(self::FIELD_ContentElement)
            ->setTable(self::TABLE_Element)->setField('title')->setValues('Element #1'));
    }

    /**
     * @test
     * @see DataSet/copyContentOfRelation.csv
     */
    public function copyContentOfRelation()
    {
        parent::copyContentOfRelation();
        $this->actionService->publishWorkspace(self::VALUE_WorkspaceId);
        $this->assertAssertionDataSet('copyContentOfRelation');

        $responseSections = $this->getFrontendResponse(self::VALUE_PageId)->getResponseSections();
        // Referenced elements are not copied with the "parent", which is expected and correct
        $this->assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':' . $this->recordIds['copiedContentId'])->setRecordField(self::FIELD_ContentElement)
            ->setTable(self::TABLE_Element)->setField('title')->setValues('Element #2', 'Element #3'));
    }

    /**
     * @test
     * @see DataSet/copyElementOfRelation.csv
     */
    public function copyElementOfRelation()
    {
        parent::copyElementOfRelation();
        $this->actionService->publishWorkspace(self::VALUE_WorkspaceId);
        $this->assertAssertionDataSet('copyElementOfRelation');

        $responseSections = $this->getFrontendResponse(self::VALUE_PageId)->getResponseSections();
        $this->assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdFirst)->setRecordField(self::FIELD_ContentElement)
            ->setTable(self::TABLE_Element)->setField('title')->setValues('Element #1'));
        // Referenced elements are not updated at the "parent", which is expected and correct
        $this->assertThat($responseSections, $this->getRequestSectionStructureDoesNotHaveRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdFirst)->setRecordField(self::FIELD_ContentElement)
            ->setTable(self::TABLE_Element)->setField('title')->setValues('Element #1 (copy 1)'));
    }

    /**
     * @test
     * @see DataSet/localizeContentOfRelation.csv
     */
    public function localizeContentOfRelation()
    {
        parent::localizeContentOfRelation();
        $this->actionService->publishWorkspace(self::VALUE_WorkspaceId);
        $this->assertAssertionDataSet('localizeContentOfRelation');

        $responseSections = $this->getFrontendResponse(self::VALUE_PageId, self::VALUE_LanguageId)->getResponseSections();
        $this->assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdLast)->setRecordField(self::FIELD_ContentElement)
            ->setTable(self::TABLE_Element)->setField('title')->setValues('Element #2', 'Element #3'));
    }

    /**
     * @test
     * @see DataSet/localizeElementOfRelation.csv
     */
    public function localizeElementOfRelation()
    {
        parent::localizeElementOfRelation();
        $this->actionService->publishWorkspace(self::VALUE_WorkspaceId);
        $this->assertAssertionDataSet('localizeElementOfRelation');

        $responseSections = $this->getFrontendResponse(self::VALUE_PageId, self::VALUE_LanguageId)->getResponseSections();
        $this->assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdFirst)->setRecordField(self::FIELD_ContentElement)
            ->setTable(self::TABLE_Element)->setField('title')->setValues('[Translate to Dansk:] Element #1', 'Element #2'));
    }

    /**
     * @test
     * @see DataSet/moveContentOfRelationToDifferentPage.csv
     */
    public function moveContentOfRelationToDifferentPage()
    {
        parent::moveContentOfRelationToDifferentPage();
        $this->actionService->publishWorkspace(self::VALUE_WorkspaceId);
        $this->assertAssertionDataSet('moveContentOfRelationToDifferentPage');

        $responseSections = $this->getFrontendResponse(self::VALUE_PageIdTarget)->getResponseSections();
        $this->assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdLast)->setRecordField(self::FIELD_ContentElement)
            ->setTable(self::TABLE_Element)->setField('title')->setValues('Element #2', 'Element #3'));
    }
}
