<?php

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

namespace TYPO3\CMS\Workspaces\Tests\Functional\DataHandling\ManyToMany\Discard;

use TYPO3\CMS\Workspaces\Tests\Functional\DataHandling\ManyToMany\AbstractActionTestCase;

/**
 * Functional test for the DataHandler
 */
class ActionTest extends AbstractActionTestCase
{
    /**
     * @var string
     */
    protected $assertionDataSetDirectory = 'typo3/sysext/workspaces/Tests/Functional/DataHandling/ManyToMany/Discard/DataSet/';

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
    public function addCategoryRelation()
    {
        parent::addCategoryRelation();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, self::VALUE_ContentIdFirst);
        $this->assertAssertionDataSet('addCategoryRelation');

        // @todo: reference index not clean after this test. Needs investigation.
        $this->assertCleanReferenceIndex = false;
    }

    /**
     * @test
     */
    public function deleteCategoryRelation()
    {
        parent::deleteCategoryRelation();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, self::VALUE_ContentIdFirst);
        $this->assertAssertionDataSet('deleteCategoryRelation');

        // @todo: reference index not clean after this test. Needs investigation.
        $this->assertCleanReferenceIndex = false;
    }

    /**
     * @test
     */
    public function changeCategoryRelationSorting()
    {
        parent::changeCategoryRelationSorting();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, self::VALUE_ContentIdFirst);
        $this->assertAssertionDataSet('changeCategoryRelationSorting');

        // @todo: reference index not clean after this test. Needs investigation.
        $this->assertCleanReferenceIndex = false;
    }

    /**
     * @test
     */
    public function createContentAndAddRelation()
    {
        parent::createContentAndAddRelation();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, $this->recordIds['newContentId']);
        $this->assertAssertionDataSet('createContentNAddRelation');

        // @todo: reference index not clean after this test. Needs investigation.
        $this->assertCleanReferenceIndex = false;
    }

    /**
     * @test
     */
    public function createCategoryAndAddRelation()
    {
        parent::createCategoryAndAddRelation();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Category, $this->recordIds['newCategoryId']);
        $this->assertAssertionDataSet('createCategoryNAddRelation');
    }

    /**
     * @test
     */
    public function createContentAndCreateRelation()
    {
        parent::createContentAndCreateRelation();
        $this->actionService->clearWorkspaceRecords([
            self::TABLE_Category => [$this->recordIds['newCategoryId']],
            self::TABLE_Content => [$this->recordIds['newContentId']],
        ]);
        $this->assertAssertionDataSet('createContentNCreateRelation');
    }

    /**
     * @test
     */
    public function createCategoryAndCreateRelation()
    {
        parent::createCategoryAndCreateRelation();
        $this->actionService->clearWorkspaceRecords([
            self::TABLE_Content => [$this->recordIds['newContentId']],
            self::TABLE_Category => [$this->recordIds['newCategoryId']],
        ]);
        $this->assertAssertionDataSet('createCategoryNCreateRelation');
    }

    /**
     * @test
     */
    public function createContentWithCategoryAndAddRelation()
    {
        parent::createContentWithCategoryAndAddRelation();
        $this->actionService->clearWorkspaceRecords([
            self::TABLE_Category => [$this->recordIds['newCategoryId']],
            self::TABLE_Content => [$this->recordIds['newContentId']],
        ]);
        $this->assertAssertionDataSet('createContentWCategoryNAddRelation');
    }

    /**
     * @test
     */
    public function createCategoryWithContentAndAddRelation()
    {
        parent::createCategoryWithContentAndAddRelation();
        $this->actionService->clearWorkspaceRecords([
            self::TABLE_Content => [$this->recordIds['newContentId']],
            self::TABLE_Category => [$this->recordIds['newCategoryId']],
        ]);
        $this->assertAssertionDataSet('createCategoryWContentNAddRelation');
    }

    /**
     * @test
     */
    public function modifyCategoryOfRelation()
    {
        parent::modifyCategoryOfRelation();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Category, self::VALUE_CategoryIdFirst);
        $this->assertAssertionDataSet('modifyCategoryOfRelation');
    }

    /**
     * @test
     */
    public function modifyContentOfRelation()
    {
        parent::modifyContentOfRelation();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, self::VALUE_ContentIdFirst);
        $this->assertAssertionDataSet('modifyContentOfRelation');

        // @todo: reference index not clean after this test. Needs investigation.
        $this->assertCleanReferenceIndex = false;
    }

    /**
     * @test
     */
    public function modifyBothsOfRelation()
    {
        parent::modifyBothsOfRelation();
        $this->actionService->clearWorkspaceRecords([
            self::TABLE_Content => [self::VALUE_ContentIdFirst],
            self::TABLE_Category => [self::VALUE_CategoryIdFirst],
        ]);
        $this->assertAssertionDataSet('modifyBothsOfRelation');

        // @todo: reference index not clean after this test. Needs investigation.
        $this->assertCleanReferenceIndex = false;
    }

    /**
     * @test
     */
    public function deleteContentOfRelation()
    {
        parent::deleteContentOfRelation();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, self::VALUE_ContentIdLast);
        $this->assertAssertionDataSet('deleteContentOfRelation');

        // @todo: reference index not clean after this test. Needs investigation.
        $this->assertCleanReferenceIndex = false;
    }

    /**
     * @test
     */
    public function deleteCategoryOfRelation()
    {
        parent::deleteCategoryOfRelation();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Category, self::VALUE_CategoryIdFirst);
        $this->assertAssertionDataSet('deleteCategoryOfRelation');
    }

    /**
     * @test
     */
    public function copyContentOfRelation()
    {
        parent::copyContentOfRelation();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, $this->recordIds['newContentId']);
        $this->assertAssertionDataSet('copyContentOfRelation');

        // @todo: reference index not clean after this test. Needs investigation.
        $this->assertCleanReferenceIndex = false;
    }

    /**
     * @test
     */
    public function copyCategoryOfRelation()
    {
        parent::copyCategoryOfRelation();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Category, $this->recordIds['newCategoryId']);
        $this->assertAssertionDataSet('copyCategoryOfRelation');
    }

    /**
     * @test
     */
    public function localizeContentOfRelation()
    {
        parent::localizeContentOfRelation();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, $this->recordIds['localizedContentId']);
        $this->assertAssertionDataSet('localizeContentOfRelation');

        // @todo: reference index not clean after this test. Needs investigation.
        $this->assertCleanReferenceIndex = false;
    }

    /**
     * @test
     */
    public function localizeCategoryOfRelation()
    {
        // Create translated page first
        $this->actionService->copyRecordToLanguage(self::TABLE_Page, self::VALUE_PageId, self::VALUE_LanguageId);
        parent::localizeCategoryOfRelation();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Category, $this->recordIds['localizedCategoryId']);
        $this->assertAssertionDataSet('localizeCategoryOfRelation');
    }

    /**
     * @test
     */
    public function moveContentOfRelationToDifferentPage()
    {
        parent::moveContentOfRelationToDifferentPage();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, self::VALUE_ContentIdLast);
        $this->assertAssertionDataSet('moveContentOfRelationToDifferentPage');

        // @todo: reference index not clean after this test. Needs investigation.
        $this->assertCleanReferenceIndex = false;
    }

    /**
     * @test
     */
    public function copyPage()
    {
        parent::copyPage();
        $this->actionService->clearWorkspaceRecords([
            self::TABLE_Page => [$this->recordIds['newPageId']],
        ]);
        $this->assertAssertionDataSet('copyPage');

        // @todo: reference index not clean after this test. Needs investigation.
        $this->assertCleanReferenceIndex = false;
    }
}
