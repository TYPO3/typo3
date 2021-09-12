<?php

declare(strict_types=1);

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

namespace TYPO3\CMS\Workspaces\Tests\Functional\DataHandling\Group\Discard;

use TYPO3\CMS\Workspaces\Tests\Functional\DataHandling\Group\AbstractActionTestCase;

/**
 * Functional test for the DataHandler
 */
class ActionTest extends AbstractActionTestCase
{
    /**
     * @var string
     */
    protected $assertionDataSetDirectory = 'typo3/sysext/workspaces/Tests/Functional/DataHandling/Group/Discard/DataSet/';

    /**
     * @test
     */
    public function verifyCleanReferenceIndex()
    {
        // The test verifies the imported data set has a clean reference index by the check in tearDown()
        self::assertTrue(true);
    }

    /**
     * @test
     */
    public function addElementRelation()
    {
        parent::addElementRelation();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, self::VALUE_ContentIdFirst);
        $this->assertAssertionDataSet('addElementRelation');
    }

    /**
     * @test
     */
    public function deleteElementRelation()
    {
        parent::deleteElementRelation();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, self::VALUE_ContentIdFirst);
        $this->assertAssertionDataSet('deleteElementRelation');
    }

    /**
     * @test
     */
    public function changeElementSorting()
    {
        parent::changeElementSorting();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Element, self::VALUE_ElementIdFirst);
        $this->assertAssertionDataSet('changeElementSorting');
    }

    /**
     * @test
     */
    public function changeElementRelationSorting()
    {
        parent::changeElementRelationSorting();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, self::VALUE_ContentIdFirst);
        $this->assertAssertionDataSet('changeElementRelationSorting');
    }

    /**
     * @test
     */
    public function createContentAndAddElementRelation()
    {
        parent::createContentAndAddElementRelation();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, $this->recordIds['newContentId']);
        $this->assertAssertionDataSet('createContentNAddRelation');
    }

    /**
     * @test
     * Special discard case of createContentAndCreateElementRelation from Modify
     */
    public function createContentAndCreateElementRelationAndDiscardElement()
    {
        // Remove this once the relation part is resolved, see the todo in the CSV
        $this->assertCleanReferenceIndex = false;
        $this->createContentAndCreateElementRelation();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Element, $this->recordIds['newElementId']);
        $this->assertAssertionDataSet('createContentNCreateRelationNDiscardElement');
    }

    /**
     * @test
     * Special discard case for createContentAndCreateElementRelation from Modify
     */
    public function createContentAndCreateElementRelationAndDiscardContent()
    {
        $this->createContentAndCreateElementRelation();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, $this->recordIds['newContentId']);
        $this->assertAssertionDataSet('createContentNCreateRelationNDiscardContent');
    }

    /**
     * @test
     */
    public function modifyElementOfRelation()
    {
        parent::modifyElementOfRelation();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Element, self::VALUE_ElementIdFirst);
        $this->assertAssertionDataSet('modifyElementOfRelation');
    }

    /**
     * @test
     */
    public function modifyContentOfRelation()
    {
        parent::modifyContentOfRelation();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, self::VALUE_ContentIdFirst);
        $this->assertAssertionDataSet('modifyContentOfRelation');
    }

    /**
     * @test
     */
    public function modifyBothSidesOfRelation()
    {
        parent::modifyBothSidesOfRelation();
        $this->actionService->clearWorkspaceRecords([
            self::TABLE_Content => [self::VALUE_ContentIdFirst],
            self::TABLE_Element => [self::VALUE_ElementIdFirst],
        ]);
        $this->assertAssertionDataSet('modifyBothSidesOfRelation');
    }

    /**
     * @test
     */
    public function deleteContentOfRelation()
    {
        parent::deleteContentOfRelation();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, self::VALUE_ContentIdLast);
        $this->assertAssertionDataSet('deleteContentOfRelation');
    }

    /**
     * @test
     */
    public function deleteElementOfRelation()
    {
        parent::deleteElementOfRelation();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Element, self::VALUE_ElementIdFirst);
        $this->assertAssertionDataSet('deleteElementOfRelation');
    }

    /**
     * @test
     */
    public function copyContentOfRelation()
    {
        parent::copyContentOfRelation();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, $this->recordIds['copiedContentId']);
        $this->assertAssertionDataSet('copyContentOfRelation');
    }

    /**
     * @test
     */
    public function copyElementOfRelation()
    {
        parent::copyElementOfRelation();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Element, $this->recordIds['copiedElementId']);
        $this->assertAssertionDataSet('copyElementOfRelation');
    }

    /**
     * @test
     */
    public function localizeContentOfRelation()
    {
        parent::localizeContentOfRelation();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, $this->recordIds['localizedContentId']);
        $this->assertAssertionDataSet('localizeContentOfRelation');
    }

    /**
     * @test
     */
    public function localizeElementOfRelation()
    {
        // Create translated page first
        $this->actionService->copyRecordToLanguage('pages', self::VALUE_PageId, self::VALUE_LanguageId);
        parent::localizeElementOfRelation();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Element, $this->recordIds['localizedElementId']);
        $this->assertAssertionDataSet('localizeElementOfRelation');
    }

    /**
     * @test
     */
    public function moveContentOfRelationToDifferentPage()
    {
        parent::moveContentOfRelationToDifferentPage();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, $this->recordIds['movedContentId']);
        $this->assertAssertionDataSet('moveContentOfRelationToDifferentPage');
    }
}
