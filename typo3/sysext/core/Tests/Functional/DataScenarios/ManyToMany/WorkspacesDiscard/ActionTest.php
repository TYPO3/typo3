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
    public function addSurfRelation(): void
    {
        parent::addSurfRelation();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, self::VALUE_ContentIdFirst);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/addSurfRelation.csv');
    }

    #[Test]
    public function deleteSurfRelation(): void
    {
        parent::deleteSurfRelation();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, self::VALUE_ContentIdFirst);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/deleteSurfRelation.csv');
    }

    #[Test]
    public function changeSurfRelationSorting(): void
    {
        parent::changeSurfRelationSorting();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, self::VALUE_ContentIdFirst);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/changeSurfRelationSorting.csv');
    }

    #[Test]
    public function createContentAndAddRelation(): void
    {
        parent::createContentAndAddRelation();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, $this->recordIds['newContentId']);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/createContentNAddRelation.csv');
    }

    #[Test]
    public function createSurfAndAddRelation(): void
    {
        parent::createSurfAndAddRelation();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Surf, $this->recordIds['newSurfId']);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/createSurfNAddRelation.csv');
    }

    #[Test]
    public function createContentAndCreateRelation(): void
    {
        parent::createContentAndCreateRelation();
        $this->actionService->clearWorkspaceRecords([
            self::TABLE_Surf => [$this->recordIds['newSurfId']],
            self::TABLE_Content => [$this->recordIds['newContentId']],
        ]);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/createContentNCreateRelation.csv');
    }

    #[Test]
    public function createSurfAndCreateRelation(): void
    {
        parent::createSurfAndCreateRelation();
        $this->actionService->clearWorkspaceRecords([
            self::TABLE_Content => [$this->recordIds['newContentId']],
            self::TABLE_Surf => [$this->recordIds['newSurfId']],
        ]);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/createSurfNCreateRelation.csv');
    }

    #[Test]
    public function createContentWithSurfAndAddRelation(): void
    {
        parent::createContentWithSurfAndAddRelation();
        $this->actionService->clearWorkspaceRecords([
            self::TABLE_Surf => [$this->recordIds['newSurfId']],
            self::TABLE_Content => [$this->recordIds['newContentId']],
        ]);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/createContentWSurfNAddRelation.csv');
    }

    #[Test]
    public function createSurfWithContentAndAddRelation(): void
    {
        parent::createSurfWithContentAndAddRelation();
        $this->actionService->clearWorkspaceRecords([
            self::TABLE_Content => [$this->recordIds['newContentId']],
            self::TABLE_Surf => [$this->recordIds['newSurfId']],
        ]);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/createSurfWContentNAddRelation.csv');
    }

    #[Test]
    public function modifySurfOfRelation(): void
    {
        parent::modifySurfOfRelation();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Surf, self::VALUE_SurfIdFirst);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/modifySurfOfRelation.csv');
    }

    #[Test]
    public function modifyContentOfRelation(): void
    {
        parent::modifyContentOfRelation();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, self::VALUE_ContentIdFirst);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/modifyContentOfRelation.csv');
    }

    #[Test]
    public function modifyBothsOfRelation(): void
    {
        parent::modifyBothsOfRelation();
        $this->actionService->clearWorkspaceRecords([
            self::TABLE_Content => [self::VALUE_ContentIdFirst],
            self::TABLE_Surf => [self::VALUE_SurfIdFirst],
        ]);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/modifyBothsOfRelation.csv');
    }

    #[Test]
    public function deleteContentOfRelation(): void
    {
        parent::deleteContentOfRelation();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, self::VALUE_ContentIdLast);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/deleteContentOfRelation.csv');
    }

    #[Test]
    public function deleteContentOfRelationWithoutSoftDelete(): void
    {
        parent::deleteContentOfRelationWithoutSoftDelete();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, $this->recordIds['deletedRecordId']);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/deleteContentOfRelationWithoutSoftDelete.csv');
    }

    #[Test]
    public function deleteSurfOfRelation(): void
    {
        parent::deleteSurfOfRelation();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Surf, self::VALUE_SurfIdFirst);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/deleteSurfOfRelation.csv');
    }

    #[Test]
    public function copyContentOfRelation(): void
    {
        parent::copyContentOfRelation();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, $this->recordIds['newContentId']);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/copyContentOfRelation.csv');
    }

    #[Test]
    public function copyContentToLanguageOfRelation(): void
    {
        parent::copyContentToLanguageOfRelation();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, $this->recordIds['newContentId']);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/copyContentToLanguageOfRelation.csv');
    }

    #[Test]
    public function copySurfOfRelation(): void
    {
        parent::copySurfOfRelation();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Surf, $this->recordIds['newSurfId']);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/copySurfOfRelation.csv');
    }

    /**
     * @todo: this is a faulty test, because the Surf should be discarded
     */
    #[Test]
    public function copySurfToLanguageOfRelation(): void
    {
        parent::copySurfToLanguageOfRelation();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, $this->recordIds['newSurfId']);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/copySurfToLanguageOfRelation.csv');
    }

    #[Test]
    public function localizeContentOfRelation(): void
    {
        parent::localizeContentOfRelation();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, $this->recordIds['localizedContentId']);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/localizeContentOfRelation.csv');
    }

    #[Test]
    public function localizeContentOfRelationWithLanguageSynchronization(): void
    {
        parent::localizeContentOfRelationWithLanguageSynchronization();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, $this->recordIds['localizedContentId']);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/localizeContentOfRelationWSynchronization.csv');
    }

    #[Test]
    public function localizeContentOfRelationWithLanguageExclude(): void
    {
        parent::localizeContentOfRelationWithLanguageExclude();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, $this->recordIds['localizedContentId']);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/localizeContentOfRelationWExclude.csv');
    }

    #[Test]
    public function localizeContentOfRelationAndAddSurfWithLanguageSynchronization(): void
    {
        parent::localizeContentOfRelationAndAddSurfWithLanguageSynchronization();
        // @todo: even if we discard this record, it is still showing up in the result
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, $this->recordIds['localizedContentId']);
        // @todo: do we need to discard the references manually?
        $this->assertCSVDataSet(__DIR__ . '/DataSet/localizeContentOfRelationNAddSurfWSynchronization.csv');
    }

    #[Test]
    public function localizeContentChainOfRelationAndAddSurfWithLanguageSynchronization(): void
    {
        parent::localizeContentChainOfRelationAndAddSurfWithLanguageSynchronization();
        // @todo: even if we discard this record, it is still showing up in the result
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, $this->recordIds['localizedContentId']);
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, $this->recordIds['localizedContentIdSecond']);
        // @todo: do we need to discard the references manually?
        $this->assertCSVDataSet(__DIR__ . '/DataSet/localizeContentChainOfRelationNAddSurfWSynchronization.csv');
    }

    #[Test]
    public function localizeSurfOfRelation(): void
    {
        // Create translated page first
        $this->actionService->copyRecordToLanguage(self::TABLE_Page, self::VALUE_PageId, self::VALUE_LanguageId);
        parent::localizeSurfOfRelation();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Surf, $this->recordIds['localizedSurfId']);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/localizeSurfOfRelation.csv');
    }

    #[Test]
    public function moveContentOfRelationToDifferentPage(): void
    {
        parent::moveContentOfRelationToDifferentPage();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, self::VALUE_ContentIdLast);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/moveContentOfRelationToDifferentPage.csv');
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
}
