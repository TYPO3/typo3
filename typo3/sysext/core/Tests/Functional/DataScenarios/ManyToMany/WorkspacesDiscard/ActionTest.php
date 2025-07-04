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

namespace TYPO3\CMS\Core\Tests\Functional\DataScenarios\ManyToMany\WorkspacesDiscard;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\ReferenceIndex;
use TYPO3\CMS\Core\Tests\Functional\DataScenarios\ManyToMany\AbstractActionWorkspacesTestCase;

final class ActionTest extends AbstractActionWorkspacesTestCase
{
    #[Test]
    public function verifyCleanReferenceIndex(): void
    {
        // Fix refindex, then compare with import csv again to verify nothing changed.
        // This is to make sure the import csv is 'clean' - important for the other tests.
        $this->get(ReferenceIndex::class)->updateIndex(false);
        $this->assertCSVDataSet(self::SCENARIO_DataSet);
    }

    #[Test]
    public function addGroupMM1RelationOnForeignSide(): void
    {
        parent::addGroupMM1RelationOnForeignSide();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, self::VALUE_ContentIdFirst);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/addGroupMM1RelationOnForeignSide.csv');
    }

    #[Test]
    public function deleteGroupMM1RelationOnForeignSide(): void
    {
        parent::deleteGroupMM1RelationOnForeignSide();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, self::VALUE_ContentIdFirst);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/deleteGroupMM1RelationOnForeignSide.csv');
    }

    #[Test]
    public function changeGroupMM1SortingOnForeignSide(): void
    {
        parent::changeGroupMM1SortingOnForeignSide();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, self::VALUE_ContentIdFirst);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/changeGroupMM1SortingOnForeignSide.csv');
    }

    #[Test]
    public function createContentAndAddGroupMM1Relation(): void
    {
        parent::createContentAndAddGroupMM1Relation();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, $this->recordIds['newContentId']);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/createContentAndAddGroupMM1Relation.csv');
    }

    #[Test]
    public function createTestMMAndAddGroupMM1Relation(): void
    {
        parent::createTestMMAndAddGroupMM1Relation();
        $this->actionService->clearWorkspaceRecord(self::TABLE_TEST_MM, $this->recordIds['newGroupMM1Id']);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/createTestMMAndAddGroupMM1Relation.csv');
    }

    #[Test]
    public function createTestMMAndContentWithGroupMM1Relation(): void
    {
        parent::createTestMMAndContentWithGroupMM1Relation();
        $this->actionService->clearWorkspaceRecords([
            self::TABLE_TEST_MM => [$this->recordIds['newGroupMM1Id']],
            self::TABLE_Content => [$this->recordIds['newContentId']],
        ]);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/createTestMMAndContentWithGroupMM1Relation.csv');
    }

    #[Test]
    public function createContentAndTestMMWithGroupMM1Relation(): void
    {
        parent::createContentAndTestMMWithGroupMM1Relation();
        $this->actionService->clearWorkspaceRecords([
            self::TABLE_Content => [$this->recordIds['newContentId']],
            self::TABLE_TEST_MM => [$this->recordIds['newGroupMM1Id']],
        ]);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/createContentAndTestMMWithGroupMM1Relation.csv');
    }

    #[Test]
    public function createTestMMAndContentWithAddedGroupMM1Relation(): void
    {
        parent::createTestMMAndContentWithAddedGroupMM1Relation();
        $this->actionService->clearWorkspaceRecords([
            self::TABLE_TEST_MM => [$this->recordIds['newGroupMM1Id']],
            self::TABLE_Content => [$this->recordIds['newContentId']],
        ]);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/createTestMMAndContentWithAddedGroupMM1Relation.csv');
    }

    #[Test]
    public function createContentAndTestMMWithAddedGroupMM1Relation(): void
    {
        parent::createContentAndTestMMWithAddedGroupMM1Relation();
        $this->actionService->clearWorkspaceRecords([
            self::TABLE_Content => [$this->recordIds['newContentId']],
            self::TABLE_TEST_MM => [$this->recordIds['newGroupMM1Id']],
        ]);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/createContentAndTestMMWithAddedGroupMM1Relation.csv');
    }

    #[Test]
    public function modifyTestMM(): void
    {
        parent::modifyTestMM();
        $this->actionService->clearWorkspaceRecord(self::TABLE_TEST_MM, self::VALUE_TestMMIdFirst);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/modifyTestMM.csv');
    }

    #[Test]
    public function modifyContent(): void
    {
        parent::modifyContent();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, self::VALUE_ContentIdFirst);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/modifyContent.csv');
    }

    #[Test]
    public function modifyTestMMAndContent(): void
    {
        parent::modifyTestMMAndContent();
        $this->actionService->clearWorkspaceRecords([
            self::TABLE_Content => [self::VALUE_ContentIdFirst],
            self::TABLE_TEST_MM => [self::VALUE_TestMMIdFirst],
        ]);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/modifyTestMMAndContent.csv');
    }

    #[Test]
    public function deleteContentWithMultipleRelations(): void
    {
        parent::deleteContentWithMultipleRelations();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, self::VALUE_ContentIdLast);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/deleteContentWithMultipleRelations.csv');
    }

    #[Test]
    public function deleteContentWithMultipleRelationsAndWithoutSoftDelete(): void
    {
        parent::deleteContentWithMultipleRelationsAndWithoutSoftDelete();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, $this->recordIds['deletedRecordId']);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/deleteContentWithMultipleRelationsAndWithoutSoftDelete.csv');
    }

    #[Test]
    public function deleteTestMM(): void
    {
        parent::deleteTestMM();
        $this->actionService->clearWorkspaceRecord(self::TABLE_TEST_MM, self::VALUE_TestMMIdFirst);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/deleteTestMM.csv');
    }

    #[Test]
    public function copyContentWithRelations(): void
    {
        parent::copyContentWithRelations();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, $this->recordIds['newContentId']);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/copyContentWithRelations.csv');
    }

    #[Test]
    public function copyContentToLanguage(): void
    {
        parent::copyContentToLanguage();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, $this->recordIds['newContentId']);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/copyContentToLanguage.csv');
    }

    #[Test]
    public function copyTestMM(): void
    {
        parent::copyTestMM();
        $this->actionService->clearWorkspaceRecord(self::TABLE_TEST_MM, $this->recordIds['newGroupMM1Id']);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/copyTestMM.csv');
    }

    /**
     * @todo: this is a faulty test, because the Surf should be discarded
     */
    #[Test]
    public function copyTestMMToLanguage(): void
    {
        parent::copyTestMMToLanguage();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, $this->recordIds['newGroupMM1Id']);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/copyTestMMToLanguage.csv');
    }

    #[Test]
    public function localizeContent(): void
    {
        parent::localizeContent();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, $this->recordIds['localizedContentId']);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/localizeContent.csv');
    }

    #[Test]
    public function localizeContentWithLanguageSynchronization(): void
    {
        parent::localizeContentWithLanguageSynchronization();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, $this->recordIds['localizedContentId']);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/localizeContentWithLanguageSynchronization.csv');
    }

    #[Test]
    public function localizeContentWithLanguageExclude(): void
    {
        parent::localizeContentWithLanguageExclude();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, $this->recordIds['localizedContentId']);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/localizeContentWithLanguageExclude.csv');
    }

    #[Test]
    public function localizeContentAndAddTestMMWithLanguageSynchronization(): void
    {
        parent::localizeContentAndAddTestMMWithLanguageSynchronization();
        // @todo: even if we discard this record, it is still showing up in the result
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, $this->recordIds['localizedContentId']);
        // @todo: do we need to discard the references manually?
        $this->assertCSVDataSet(__DIR__ . '/DataSet/localizeContentAndAddTestMMWithLanguageSynchronization.csv');
    }

    #[Test]
    public function localizeContentChainAndAddTestMMWithLanguageSynchronization(): void
    {
        parent::localizeContentChainAndAddTestMMWithLanguageSynchronization();
        // @todo: even if we discard this record, it is still showing up in the result
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, $this->recordIds['localizedContentId']);
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, $this->recordIds['localizedContentIdSecond']);
        // @todo: do we need to discard the references manually?
        $this->assertCSVDataSet(__DIR__ . '/DataSet/localizeContentChainAndAddTestMMWithLanguageSynchronization.csv');
    }

    #[Test]
    public function localizeTestMM(): void
    {
        // Create translated page first
        $this->actionService->copyRecordToLanguage(self::TABLE_Page, self::VALUE_PageId, self::VALUE_LanguageId);
        parent::localizeTestMM();
        $this->actionService->clearWorkspaceRecord(self::TABLE_TEST_MM, $this->recordIds['localizedSurfId']);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/localizeTestMM.csv');
    }

    #[Test]
    public function moveContentToDifferentPage(): void
    {
        parent::moveContentToDifferentPage();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, self::VALUE_ContentIdLast);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/moveContentToDifferentPage.csv');
    }

    #[Test]
    public function copyPage(): void
    {
        parent::copyPage();
        $this->actionService->clearWorkspaceRecords([
            self::TABLE_Page => [$this->recordIds['newPageId']],
        ]);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/copyPage.csv');
    }

    #[Test]
    public function localizeTestMMSelect1MMLocal(): void
    {
        parent::localizeTestMMSelect1MMLocal();
        $this->actionService->clearWorkspaceRecord(self::TABLE_TEST_MM, $this->recordIds['localizedSurfId']);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/localizeTestMMSelect1MMLocal.csv');
    }

    #[Test]
    public function localizeTestMMSelect1MMLocalWithExclude(): void
    {
        parent::localizeTestMMSelect1MMLocalWithExclude();
        $this->actionService->clearWorkspaceRecord(self::TABLE_TEST_MM, $this->recordIds['localizedSurfId']);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/localizeTestMMSelect1MMLocalWithExclude.csv');
    }

    #[Test]
    public function localizeTestMMSelect1MMLocalWithLanguageSynchronization(): void
    {
        parent::localizeTestMMSelect1MMLocalWithLanguageSynchronization();
        $this->actionService->clearWorkspaceRecord(self::TABLE_TEST_MM, $this->recordIds['localizedSurfId']);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/localizeTestMMSelect1MMLocalWithLanguageSynchronization.csv');
    }

    #[Test]
    public function localizeContentSelect1MMForeign(): void
    {
        parent::localizeContentSelect1MMForeign();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, $this->recordIds['localizedContentId']);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/localizeContentSelect1MMForeign.csv');
    }

    #[Test]
    public function localizeContentSelect1MMForeignWithExclude(): void
    {
        parent::localizeContentSelect1MMForeignWithExclude();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, $this->recordIds['localizedContentId']);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/localizeContentSelect1MMForeignWithExclude.csv');
    }

    #[Test]
    public function localizeContentSelect1MMForeignWithLanguageSynchronization(): void
    {
        parent::localizeContentSelect1MMForeignWithLanguageSynchronization();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, $this->recordIds['localizedContentId']);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/localizeContentSelect1MMForeignWithLanguageSynchronization.csv');
    }
}
