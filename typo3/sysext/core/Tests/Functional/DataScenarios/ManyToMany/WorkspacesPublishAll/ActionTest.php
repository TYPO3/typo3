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

namespace TYPO3\CMS\Core\Tests\Functional\DataScenarios\ManyToMany\WorkspacesPublishAll;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\ReferenceIndex;
use TYPO3\CMS\Core\Tests\Functional\DataScenarios\ManyToMany\AbstractActionWorkspacesTestCase;
use TYPO3\TestingFramework\Core\Functional\Framework\Constraint\RequestSection\DoesNotHaveRecordConstraint;
use TYPO3\TestingFramework\Core\Functional\Framework\Constraint\RequestSection\HasRecordConstraint;
use TYPO3\TestingFramework\Core\Functional\Framework\Constraint\RequestSection\StructureDoesNotHaveRecordConstraint;
use TYPO3\TestingFramework\Core\Functional\Framework\Constraint\RequestSection\StructureHasRecordConstraint;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\ResponseContent;

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
        $this->actionService->publishWorkspace(self::VALUE_WorkspaceId);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/addGroupMM1RelationOnForeignSide.csv');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, (new StructureHasRecordConstraint())
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdFirst)->setRecordField(self::FIELD_GROUP_MM_1_FOREIGN)
            ->setTable(self::TABLE_TEST_MM)->setField('title')->setValues('Surf A', 'Surf B', 'Surf A.A'));
    }

    #[Test]
    public function deleteGroupMM1RelationOnForeignSide(): void
    {
        parent::deleteGroupMM1RelationOnForeignSide();
        $this->actionService->publishWorkspace(self::VALUE_WorkspaceId);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/deleteGroupMM1RelationOnForeignSide.csv');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, (new StructureHasRecordConstraint())
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdFirst)->setRecordField(self::FIELD_GROUP_MM_1_FOREIGN)
            ->setTable(self::TABLE_TEST_MM)->setField('title')->setValues('Surf A'));
        self::assertThat($responseSections, (new StructureDoesNotHaveRecordConstraint())
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdFirst)->setRecordField(self::FIELD_GROUP_MM_1_FOREIGN)
            ->setTable(self::TABLE_TEST_MM)->setField('title')->setValues('Surf B', 'Surf C', 'Surf A.A'));
    }

    #[Test]
    public function changeGroupMM1SortingOnForeignSide(): void
    {
        parent::changeGroupMM1SortingOnForeignSide();
        $this->actionService->publishWorkspace(self::VALUE_WorkspaceId);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/changeGroupMM1SortingOnForeignSide.csv');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, (new StructureHasRecordConstraint())
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdFirst)->setRecordField(self::FIELD_GROUP_MM_1_FOREIGN)
            ->setTable(self::TABLE_TEST_MM)->setField('title')->setValues('Surf A', 'Surf B'));
    }

    #[Test]
    public function createContentAndAddGroupMM1Relation(): void
    {
        parent::createContentAndAddGroupMM1Relation();
        $this->actionService->publishWorkspace(self::VALUE_WorkspaceId);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/createContentAndAddGroupMM1Relation.csv');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, (new HasRecordConstraint())
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Surfing #1'));
        self::assertThat($responseSections, (new StructureHasRecordConstraint())
            ->setRecordIdentifier(self::TABLE_Content . ':' . $this->recordIds['newContentId'])->setRecordField(self::FIELD_GROUP_MM_1_FOREIGN)
            ->setTable(self::TABLE_TEST_MM)->setField('title')->setValues('Surf B'));
    }

    #[Test]
    public function createTestMMAndAddGroupMM1Relation(): void
    {
        parent::createTestMMAndAddGroupMM1Relation();
        $this->actionService->publishWorkspace(self::VALUE_WorkspaceId);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/createTestMMAndAddGroupMM1Relation.csv');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, (new HasRecordConstraint())
            ->setTable(self::TABLE_TEST_MM)->setField('title')->setValues('Surfing #1'));
        self::assertThat($responseSections, (new StructureHasRecordConstraint())
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdFirst)->setRecordField(self::FIELD_GROUP_MM_1_FOREIGN)
            ->setTable(self::TABLE_TEST_MM)->setField('title')->setValues('Surfing #1'));
    }

    #[Test]
    public function createTestMMAndContentWithGroupMM1Relation(): void
    {
        parent::createTestMMAndContentWithGroupMM1Relation();
        $this->actionService->publishWorkspace(self::VALUE_WorkspaceId);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/createTestMMAndContentWithGroupMM1Relation.csv');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, (new HasRecordConstraint())
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Surfing #1'));
        self::assertThat($responseSections, (new StructureHasRecordConstraint())
            ->setRecordIdentifier(self::TABLE_Content . ':' . $this->recordIds['newContentId'])->setRecordField(self::FIELD_GROUP_MM_1_FOREIGN)
            ->setTable(self::TABLE_TEST_MM)->setField('title')->setValues('Surfing #1'));
    }

    #[Test]
    public function createContentAndTestMMWithGroupMM1Relation(): void
    {
        parent::createContentAndTestMMWithGroupMM1Relation();
        $this->actionService->publishWorkspace(self::VALUE_WorkspaceId);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/createContentAndTestMMWithGroupMM1Relation.csv');
    }

    #[Test]
    public function createTestMMAndContentWithAddedGroupMM1Relation(): void
    {
        parent::createTestMMAndContentWithAddedGroupMM1Relation();
        $this->actionService->publishWorkspace(self::VALUE_WorkspaceId);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/createTestMMAndContentWithAddedGroupMM1Relation.csv');
    }

    #[Test]
    public function createContentAndTestMMWithAddedGroupMM1Relation(): void
    {
        parent::createContentAndTestMMWithAddedGroupMM1Relation();
        $this->actionService->publishWorkspace(self::VALUE_WorkspaceId);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/createContentAndTestMMWithAddedGroupMM1Relation.csv');
    }

    #[Test]
    public function modifyTestMM(): void
    {
        parent::modifyTestMM();
        $this->actionService->publishWorkspace(self::VALUE_WorkspaceId);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/modifyTestMM.csv');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, (new StructureHasRecordConstraint())
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdFirst)->setRecordField(self::FIELD_GROUP_MM_1_FOREIGN)
            ->setTable(self::TABLE_TEST_MM)->setField('title')->setValues('Surfing #1', 'Surf B'));
    }

    #[Test]
    public function modifyContent(): void
    {
        parent::modifyContent();
        $this->actionService->publishWorkspace(self::VALUE_WorkspaceId);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/modifyContent.csv');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, (new HasRecordConstraint())
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Surfing #1'));
    }

    #[Test]
    public function modifyTestMMAndContent(): void
    {
        parent::modifyTestMMAndContent();
        $this->actionService->publishWorkspace(self::VALUE_WorkspaceId);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/modifyTestMMAndContent.csv');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, (new StructureHasRecordConstraint())
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdFirst)->setRecordField(self::FIELD_GROUP_MM_1_FOREIGN)
            ->setTable(self::TABLE_TEST_MM)->setField('title')->setValues('Surfing #1', 'Surf B'));
        self::assertThat($responseSections, (new HasRecordConstraint())
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Surfing #1'));
    }

    #[Test]
    public function deleteContentWithMultipleRelations(): void
    {
        parent::deleteContentWithMultipleRelations();
        $this->actionService->publishWorkspace(self::VALUE_WorkspaceId);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/deleteContentWithMultipleRelations.csv');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, (new DoesNotHaveRecordConstraint())
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Surfing #1'));
    }

    #[Test]
    public function deleteContentWithMultipleRelationsAndWithoutSoftDelete(): void
    {
        parent::deleteContentWithMultipleRelationsAndWithoutSoftDelete();
        $this->actionService->publishWorkspace(self::VALUE_WorkspaceId);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/deleteContentWithMultipleRelationsAndWithoutSoftDelete.csv');
    }

    #[Test]
    public function deleteTestMM(): void
    {
        parent::deleteTestMM();
        $this->actionService->publishWorkspace(self::VALUE_WorkspaceId);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/deleteTestMM.csv');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, (new StructureDoesNotHaveRecordConstraint())
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdFirst)->setRecordField(self::FIELD_GROUP_MM_1_FOREIGN)
            ->setTable(self::TABLE_TEST_MM)->setField('title')->setValues('Surf A'));
    }

    #[Test]
    public function copyContentWithRelations(): void
    {
        parent::copyContentWithRelations();
        $this->actionService->publishWorkspace(self::VALUE_WorkspaceId);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/copyContentWithRelations.csv');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, (new StructureHasRecordConstraint())
            ->setRecordIdentifier(self::TABLE_Content . ':' . $this->recordIds['newContentId'])->setRecordField(self::FIELD_GROUP_MM_1_FOREIGN)
            ->setTable(self::TABLE_TEST_MM)->setField('title')->setValues('Surf B', 'Surf C'));
    }

    #[Test]
    public function copyContentToLanguage(): void
    {
        parent::copyContentToLanguage();
        $this->actionService->publishWorkspace(self::VALUE_WorkspaceId);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/copyContentToLanguage.csv');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, (new StructureHasRecordConstraint())
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdLast)->setRecordField(self::FIELD_GROUP_MM_1_FOREIGN)
            ->setTable(self::TABLE_TEST_MM)->setField('title')->setValues('Surf B', 'Surf C'));
    }

    #[Test]
    public function copyTestMM(): void
    {
        parent::copyTestMM();
        $this->actionService->publishWorkspace(self::VALUE_WorkspaceId);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/copyTestMM.csv');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, (new StructureHasRecordConstraint())
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdFirst)->setRecordField(self::FIELD_GROUP_MM_1_FOREIGN)
            ->setTable(self::TABLE_TEST_MM)->setField('title')->setValues('Surf A', 'Surf A (copy 1)'));
    }

    #[Test]
    public function copyTestMMToLanguage(): void
    {
        parent::copyTestMMToLanguage();
        $this->actionService->publishWorkspace(self::VALUE_WorkspaceId);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/copyTestMMToLanguage.csv');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, (new StructureHasRecordConstraint())
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdFirst)->setRecordField(self::FIELD_GROUP_MM_1_FOREIGN)
            ->setTable(self::TABLE_TEST_MM)->setField('title')->setValues('Surf A'));
        // [Translate to Dansk:] Surf A is not connected, thus it is not shown
        // ->setTable(self::TABLE_Surf)->setField('title')->setValues('Surf A', '[Translate to Dansk:] Surf A'));
    }

    #[Test]
    public function localizeContent(): void
    {
        parent::localizeContent();
        $this->actionService->publishWorkspace(self::VALUE_WorkspaceId);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/localizeContent.csv');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId)->withLanguageId(self::VALUE_LanguageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, (new StructureHasRecordConstraint())
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdLast)->setRecordField(self::FIELD_GROUP_MM_1_FOREIGN)
            ->setTable(self::TABLE_TEST_MM)->setField('title')->setValues('Surf B', 'Surf C'));
    }

    #[Test]
    public function localizeContentWithLanguageSynchronization(): void
    {
        parent::localizeContentWithLanguageSynchronization();
        $this->actionService->publishWorkspace(self::VALUE_WorkspaceId);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/localizeContentWithLanguageSynchronization.csv');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId)->withLanguageId(self::VALUE_LanguageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, (new StructureHasRecordConstraint())
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdLast)->setRecordField(self::FIELD_GROUP_MM_1_FOREIGN)
            ->setTable(self::TABLE_TEST_MM)->setField('title')->setValues('Surf B', 'Surf C'));
    }

    #[Test]
    public function localizeContentWithLanguageExclude(): void
    {
        parent::localizeContentWithLanguageExclude();
        $this->actionService->publishWorkspace(self::VALUE_WorkspaceId);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/localizeContentWithLanguageExclude.csv');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId)->withLanguageId(self::VALUE_LanguageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, (new StructureHasRecordConstraint())
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdLast)->setRecordField(self::FIELD_GROUP_MM_1_FOREIGN)
            ->setTable(self::TABLE_TEST_MM)->setField('title')->setValues('Surf B', 'Surf C'));
    }

    #[Test]
    public function localizeContentAndAddTestMMWithLanguageSynchronization(): void
    {
        parent::localizeContentAndAddTestMMWithLanguageSynchronization();
        $this->actionService->publishWorkspace(self::VALUE_WorkspaceId);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/localizeContentAndAddTestMMWithLanguageSynchronization.csv');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId)->withLanguageId(self::VALUE_LanguageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, (new StructureHasRecordConstraint())
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdLast)->setRecordField(self::FIELD_GROUP_MM_1_FOREIGN)
            ->setTable(self::TABLE_TEST_MM)->setField('title')->setValues('Surf B', 'Surf C'));
    }

    #[Test]
    public function localizeContentChainAndAddTestMMWithLanguageSynchronization(): void
    {
        parent::localizeContentChainAndAddTestMMWithLanguageSynchronization();
        $this->actionService->publishWorkspace(self::VALUE_WorkspaceId);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/localizeContentChainAndAddTestMMWithLanguageSynchronization.csv');

        // @todo: should we check for LanguageId_Second?
        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId)->withLanguageId(self::VALUE_LanguageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, (new StructureHasRecordConstraint())
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdLast)->setRecordField(self::FIELD_GROUP_MM_1_FOREIGN)
            ->setTable(self::TABLE_TEST_MM)->setField('title')->setValues('Surf B', 'Surf C'));
    }

    #[Test]
    public function localizeTestMM(): void
    {
        // Create translated page first
        $this->actionService->copyRecordToLanguage(self::TABLE_Page, self::VALUE_PageId, self::VALUE_LanguageId);
        parent::localizeTestMM();
        $this->actionService->publishWorkspace(self::VALUE_WorkspaceId);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/localizeTestMM.csv');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId)->withLanguageId(self::VALUE_LanguageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, (new StructureHasRecordConstraint())
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdFirst)->setRecordField(self::FIELD_GROUP_MM_1_FOREIGN)
            ->setTable(self::TABLE_TEST_MM)->setField('title')->setValues('[Translate to Dansk:] Surf A', 'Surf B'));
    }

    #[Test]
    public function moveContentToDifferentPage(): void
    {
        parent::moveContentToDifferentPage();
        $this->actionService->publishWorkspace(self::VALUE_WorkspaceId);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/moveContentToDifferentPage.csv');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageIdTarget));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, (new StructureHasRecordConstraint())
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdLast)->setRecordField(self::FIELD_GROUP_MM_1_FOREIGN)
            ->setTable(self::TABLE_TEST_MM)->setField('title')->setValues('Surf B', 'Surf C'));
    }

    #[Test]
    public function copyPage(): void
    {
        parent::copyPage();
        $this->actionService->publishWorkspace(self::VALUE_WorkspaceId);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/copyPage.csv');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId($this->recordIds['newPageId']));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, (new HasRecordConstraint())
            ->setTable(self::TABLE_Page)->setField('title')->setValues('Relations'));
        self::assertThat($responseSections, (new HasRecordConstraint())
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Regular Element #1', 'Regular Element #2'));
        self::assertThat($responseSections, (new StructureHasRecordConstraint())
            ->setRecordIdentifier(self::TABLE_Content . ':' . $this->recordIds['newContentIdFirst'])->setRecordField(self::FIELD_GROUP_MM_1_FOREIGN)
            ->setTable(self::TABLE_TEST_MM)->setField('title')->setValues('Surf A', 'Surf B'));
        self::assertThat($responseSections, (new StructureHasRecordConstraint())
            ->setRecordIdentifier(self::TABLE_Content . ':' . $this->recordIds['newContentIdLast'])->setRecordField(self::FIELD_GROUP_MM_1_FOREIGN)
            ->setTable(self::TABLE_TEST_MM)->setField('title')->setValues('Surf B', 'Surf C'));
    }

    #[Test]
    public function localizeTestMMSelect1MMLocal(): void
    {
        parent::localizeTestMMSelect1MMLocal();
        $this->actionService->publishWorkspace(self::VALUE_WorkspaceId);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/localizeTestMMSelect1MMLocal.csv');
    }

    #[Test]
    public function localizeTestMMSelect1MMLocalWithExclude(): void
    {
        parent::localizeTestMMSelect1MMLocalWithExclude();
        $this->actionService->publishWorkspace(self::VALUE_WorkspaceId);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/localizeTestMMSelect1MMLocalWithExclude.csv');
    }

    #[Test]
    public function localizeTestMMSelect1MMLocalWithLanguageSynchronization(): void
    {
        parent::localizeTestMMSelect1MMLocalWithLanguageSynchronization();
        $this->actionService->publishWorkspace(self::VALUE_WorkspaceId);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/localizeTestMMSelect1MMLocalWithLanguageSynchronization.csv');
    }

    #[Test]
    public function localizeContentSelect1MMForeign(): void
    {
        parent::localizeContentSelect1MMForeign();
        $this->actionService->publishWorkspace(self::VALUE_WorkspaceId);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/localizeContentSelect1MMForeign.csv');
    }

    #[Test]
    public function localizeContentSelect1MMForeignWithExclude(): void
    {
        parent::localizeContentSelect1MMForeignWithExclude();
        $this->actionService->publishWorkspace(self::VALUE_WorkspaceId);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/localizeContentSelect1MMForeignWithExclude.csv');
    }

    #[Test]
    public function localizeContentSelect1MMForeignWithLanguageSynchronization(): void
    {
        parent::localizeContentSelect1MMForeignWithLanguageSynchronization();
        $this->actionService->publishWorkspace(self::VALUE_WorkspaceId);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/localizeContentSelect1MMForeignWithLanguageSynchronization.csv');
    }
}
