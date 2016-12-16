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

namespace TYPO3\CMS\Core\Tests\Functional\DataScenarios\Select\WorkspacesDiscard;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\ReferenceIndex;
use TYPO3\CMS\Core\Tests\Functional\DataScenarios\Select\AbstractActionWorkspacesTestCase;

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
    public function addElementRelation(): void
    {
        parent::addElementRelation();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, self::VALUE_ContentIdFirst);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/addElementRelation.csv');
    }

    #[Test]
    public function deleteElementRelation(): void
    {
        parent::deleteElementRelation();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, self::VALUE_ContentIdFirst);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/deleteElementRelation.csv');
    }

    #[Test]
    public function changeElementSorting(): void
    {
        parent::changeElementSorting();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Element, self::VALUE_ElementIdFirst);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/changeElementSorting.csv');
    }

    #[Test]
    public function changeElementRelationSorting(): void
    {
        parent::changeElementRelationSorting();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, self::VALUE_ContentIdFirst);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/changeElementRelationSorting.csv');
    }

    #[Test]
    public function createContentAndAddElementRelation(): void
    {
        parent::createContentAndAddElementRelation();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, $this->recordIds['newContentId']);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/createContentNAddRelation.csv');
    }

    #[Test]
    public function createContentAndCreateElementRelation(): void
    {
        parent::createContentAndCreateElementRelation();
        $this->actionService->clearWorkspaceRecords([
            self::TABLE_Content => [$this->recordIds['newContentId']],
            self::TABLE_Element => [$this->recordIds['newElementId']],
        ]);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/createContentNCreateRelation.csv');
    }

    #[Test]
    public function modifyElementOfRelation(): void
    {
        parent::modifyElementOfRelation();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Element, self::VALUE_ElementIdFirst);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/modifyElementOfRelation.csv');
    }

    #[Test]
    public function modifyContentOfRelation(): void
    {
        parent::modifyContentOfRelation();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, self::VALUE_ContentIdFirst);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/modifyContentOfRelation.csv');
    }

    #[Test]
    public function modifyBothSidesOfRelation(): void
    {
        parent::modifyBothSidesOfRelation();
        $this->actionService->clearWorkspaceRecords([
            self::TABLE_Content => [self::VALUE_ContentIdFirst],
            self::TABLE_Element => [self::VALUE_ElementIdFirst],
        ]);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/modifyBothSidesOfRelation.csv');
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
    public function deleteElementOfRelation(): void
    {
        parent::deleteElementOfRelation();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Element, self::VALUE_ElementIdFirst);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/deleteElementOfRelation.csv');
    }

    #[Test]
    public function copyContentOfRelation(): void
    {
        parent::copyContentOfRelation();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, $this->recordIds['copiedContentId']);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/copyContentOfRelation.csv');
    }

    #[Test]
    public function copyElementOfRelation(): void
    {
        parent::copyElementOfRelation();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Element, $this->recordIds['copiedElementId']);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/copyElementOfRelation.csv');
    }

    #[Test]
    public function localizeContentOfRelation(): void
    {
        parent::localizeContentOfRelation();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, $this->recordIds['localizedContentId']);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/localizeContentOfRelation.csv');
    }

    #[Test]
    public function localizeElementOfRelation(): void
    {
        // Create translated page first
        $this->actionService->copyRecordToLanguage('pages', self::VALUE_PageId, self::VALUE_LanguageId);
        parent::localizeElementOfRelation();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Element, $this->recordIds['localizedElementId']);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/localizeElementOfRelation.csv');
    }

    #[Test]
    public function moveContentOfRelationToDifferentPage(): void
    {
        parent::moveContentOfRelationToDifferentPage();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, self::VALUE_ContentIdLast);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/moveContentOfRelationToDifferentPage.csv');
    }

    #[Test]
    public function localizeContentOfRelationWithLocalizeReferencesAtParentLocalization()
    {
        parent::localizeContentOfRelationWithLocalizeReferencesAtParentLocalization();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, $this->recordIds['localizedContentId']);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/localizeContentOfRelationWLocalizeReferencesAtParentLocalization.csv');
    }
}
