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

namespace TYPO3\CMS\Core\Tests\Functional\DataScenarios\CategoryManyToMany\WorkspacesDiscard;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\ReferenceIndex;
use TYPO3\CMS\Core\Tests\Functional\DataScenarios\CategoryManyToMany\AbstractActionWorkspacesTestCase;

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
    public function addCategoryRelation(): void
    {
        parent::addCategoryRelation();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, self::VALUE_ContentIdFirst);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/sameAsInput.csv');
    }

    #[Test]
    public function deleteCategoryRelation(): void
    {
        parent::deleteCategoryRelation();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, self::VALUE_ContentIdFirst);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/sameAsInput.csv');
    }

    #[Test]
    public function changeCategoryRelationSorting(): void
    {
        parent::changeCategoryRelationSorting();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, self::VALUE_ContentIdFirst);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/sameAsInput.csv');
    }

    #[Test]
    public function createContentAndAddRelation(): void
    {
        parent::createContentAndAddRelation();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, $this->recordIds['newContentId']);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/sameAsInput.csv');
    }

    #[Test]
    public function createCategoryAndAddRelation(): void
    {
        parent::createCategoryAndAddRelation();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Category, $this->recordIds['newCategoryId']);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/sameAsInput.csv');
    }

    #[Test]
    public function createContentAndCreateRelation(): void
    {
        parent::createContentAndCreateRelation();
        $this->actionService->clearWorkspaceRecords([
            self::TABLE_Category => [$this->recordIds['newCategoryId']],
            self::TABLE_Content => [$this->recordIds['newContentId']],
        ]);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/sameAsInput.csv');
    }

    #[Test]
    public function createCategoryAndCreateRelation(): void
    {
        parent::createCategoryAndCreateRelation();
        $this->actionService->clearWorkspaceRecords([
            self::TABLE_Content => [$this->recordIds['newContentId']],
            self::TABLE_Category => [$this->recordIds['newCategoryId']],
        ]);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/sameAsInput.csv');
    }

    #[Test]
    public function createContentWithCategoryAndAddRelation(): void
    {
        parent::createContentWithCategoryAndAddRelation();
        $this->actionService->clearWorkspaceRecords([
            self::TABLE_Category => [$this->recordIds['newCategoryId']],
            self::TABLE_Content => [$this->recordIds['newContentId']],
        ]);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/sameAsInput.csv');
    }

    #[Test]
    public function createCategoryWithContentAndAddRelation(): void
    {
        parent::createCategoryWithContentAndAddRelation();
        $this->actionService->clearWorkspaceRecords([
            self::TABLE_Content => [$this->recordIds['newContentId']],
            self::TABLE_Category => [$this->recordIds['newCategoryId']],
        ]);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/sameAsInput.csv');
    }

    #[Test]
    public function modifyCategoryOfRelation(): void
    {
        parent::modifyCategoryOfRelation();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Category, self::VALUE_CategoryIdFirst);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/sameAsInput.csv');
    }

    #[Test]
    public function modifyBothOfRelation(): void
    {
        parent::modifyBothOfRelation();
        $this->actionService->clearWorkspaceRecords([
            self::TABLE_Content => [self::VALUE_ContentIdFirst],
            self::TABLE_Category => [self::VALUE_CategoryIdFirst],
        ]);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/sameAsInput.csv');
    }

    #[Test]
    public function deleteContentOfRelation(): void
    {
        parent::deleteContentOfRelation();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, self::VALUE_ContentIdLast);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/sameAsInput.csv');
    }

    #[Test]
    public function deleteContentOfRelationWithoutSoftDelete(): void
    {
        parent::deleteContentOfRelationWithoutSoftDelete();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, $this->recordIds['deletedRecordId']);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/sameAsInput.csv');
    }

    #[Test]
    public function deleteCategoryOfRelation(): void
    {
        parent::deleteCategoryOfRelation();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Category, self::VALUE_CategoryIdFirst);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/sameAsInput.csv');
    }

    #[Test]
    public function copyContentOfRelation(): void
    {
        parent::copyContentOfRelation();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, $this->recordIds['newContentId']);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/sameAsInput.csv');
    }

    #[Test]
    public function copyContentToLanguageOfRelation(): void
    {
        parent::copyContentToLanguageOfRelation();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, $this->recordIds['newContentId']);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/sameAsInput.csv');
    }

    #[Test]
    public function copyCategoryOfRelation(): void
    {
        parent::copyCategoryOfRelation();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Category, $this->recordIds['newCategoryId']);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/sameAsInput.csv');
    }

    /**
     * @todo: this is a faulty test, because the category should be discarded
     */
    #[Test]
    public function copyCategoryToLanguageOfRelation(): void
    {
        parent::copyCategoryToLanguageOfRelation();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, $this->recordIds['newCategoryId']);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/copyCategoryToLanguageOfRelation.csv');
    }

    #[Test]
    public function localizeContentOfRelation(): void
    {
        parent::localizeContentOfRelation();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, $this->recordIds['localizedContentId']);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/sameAsInput.csv');
    }

    #[Test]
    public function localizeContentOfRelationWithLanguageSynchronization(): void
    {
        parent::localizeContentOfRelationWithLanguageSynchronization();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, $this->recordIds['localizedContentId']);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/sameAsInput.csv');
    }

    #[Test]
    public function localizeContentOfRelationWithLanguageExclude(): void
    {
        parent::localizeContentOfRelationWithLanguageExclude();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, $this->recordIds['localizedContentId']);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/sameAsInput.csv');
    }

    #[Test]
    public function localizeContentOfRelationAndAddCategoryWithLanguageSynchronization(): void
    {
        parent::localizeContentOfRelationAndAddCategoryWithLanguageSynchronization();
        // @todo: even if we discard this record, it is still showing up in the result
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, $this->recordIds['localizedContentId']);
        // @todo: do we need to discard the references manually?
        $this->assertCSVDataSet(__DIR__ . '/DataSet/localizeContentOfRelationNAddCategoryWSynchronization.csv');
    }

    #[Test]
    public function localizeContentChainOfRelationAndAddCategoryWithLanguageSynchronization(): void
    {
        parent::localizeContentChainOfRelationAndAddCategoryWithLanguageSynchronization();
        // @todo: even if we discard this record, it is still showing up in the result
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, $this->recordIds['localizedContentId']);
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, $this->recordIds['localizedContentIdSecond']);
        // @todo: do we need to discard the references manually?
        $this->assertCSVDataSet(__DIR__ . '/DataSet/localizeContentChainOfRelationNAddCategoryWSynchronization.csv');
    }

    #[Test]
    public function localizeCategoryOfRelation(): void
    {
        // Create translated page first
        $this->actionService->copyRecordToLanguage(self::TABLE_Page, self::VALUE_PageId, self::VALUE_LanguageId);
        parent::localizeCategoryOfRelation();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Category, $this->recordIds['localizedCategoryId']);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/localizeCategoryRelation.csv');
    }

    #[Test]
    public function moveContentOfRelationToDifferentPage(): void
    {
        parent::moveContentOfRelationToDifferentPage();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, self::VALUE_ContentIdLast);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/sameAsInput.csv');
    }

    #[Test]
    public function copyPage(): void
    {
        parent::copyPage();
        $this->actionService->clearWorkspaceRecords([
            self::TABLE_Page => [$this->recordIds['newPageId']],
        ]);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/sameAsInput.csv');
    }
}
