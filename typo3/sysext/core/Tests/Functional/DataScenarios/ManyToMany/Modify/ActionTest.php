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

namespace TYPO3\CMS\Core\Tests\Functional\DataScenarios\ManyToMany\Modify;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\ReferenceIndex;
use TYPO3\CMS\Core\Tests\Functional\DataScenarios\ManyToMany\AbstractActionTestCase;
use TYPO3\TestingFramework\Core\Functional\Framework\Constraint\RequestSection\DoesNotHaveRecordConstraint;
use TYPO3\TestingFramework\Core\Functional\Framework\Constraint\RequestSection\HasRecordConstraint;
use TYPO3\TestingFramework\Core\Functional\Framework\Constraint\RequestSection\StructureDoesNotHaveRecordConstraint;
use TYPO3\TestingFramework\Core\Functional\Framework\Constraint\RequestSection\StructureHasRecordConstraint;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\ResponseContent;

final class ActionTest extends AbstractActionTestCase
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
        $this->assertCSVDataSet(__DIR__ . '/DataSet/addGroupMM1RelationOnForeignSide.csv');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, (new StructureHasRecordConstraint())
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdFirst)->setRecordField(self::FIELD_GROUP_MM_1_FOREIGN)
            ->setTable(self::TABLE_TEST_MM)->setField('title')->setValues('Surf A', 'Surf B', 'Surf A.A'));
    }

    #[Test]
    public function changeGroupMM1SortingOnForeignSide(): void
    {
        // @todo: Needs patch. Import data set should have sorting 1 for 29-298 in mm, then DH needs to
        //        be fixed to trigger update of refindex properly on local-side resort. Workspaces may or
        //        may not need adaption as well, at least the import has a dupe sorting as well.
        self::markTestSkipped('currently disabled since DH does not update refindex properly');
        parent::changeGroupMM1SortingOnForeignSide(); // @phpstan-ignore-line
        $this->assertCSVDataSet(__DIR__ . '/DataSet/changeGroupMM1SortingOnForeignSide.csv');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, (new StructureHasRecordConstraint())
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdFirst)->setRecordField(self::FIELD_GROUP_MM_1_FOREIGN)
            ->setTable(self::TABLE_TEST_MM)->setField('title')->setValues('Surf A', 'Surf B'));
    }

    #[Test]
    public function copyContentToLanguage(): void
    {
        parent::copyContentToLanguage();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/copyContentToLanguage.csv');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId)->withLanguageId(self::VALUE_LanguageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, (new StructureHasRecordConstraint())
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdLast)->setRecordField(self::FIELD_GROUP_MM_1_FOREIGN)
            ->setTable(self::TABLE_TEST_MM)->setField('title')->setValues('Surf B', 'Surf C'));
    }

    #[Test]
    public function copyContentWithRelations(): void
    {
        parent::copyContentWithRelations();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/copyContentWithRelations.csv');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, (new StructureHasRecordConstraint())
            ->setRecordIdentifier(self::TABLE_Content . ':' . $this->recordIds['newContentId'])->setRecordField(self::FIELD_GROUP_MM_1_FOREIGN)
            ->setTable(self::TABLE_TEST_MM)->setField('title')->setValues('Surf B', 'Surf C'));
    }

    #[Test]
    public function copyPage(): void
    {
        parent::copyPage();
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
    public function copyTestMM(): void
    {
        parent::copyTestMM();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/copyTestMM.csv');
    }

    #[Test]
    public function copyTestMMToLanguage(): void
    {
        parent::copyTestMMToLanguage();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/copyTestMMToLanguage.csv');
        //in this case the translated element is orphaned (no CE with relation to it, and it has no l10n_parent)
        //so on frontend there is no change.
        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId)->withLanguageId(self::VALUE_LanguageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, (new StructureHasRecordConstraint())
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdFirst)->setRecordField(self::FIELD_GROUP_MM_1_FOREIGN)
            ->setTable(self::TABLE_TEST_MM)->setField('title')->setValues('Surf A', 'Surf B'));
    }

    #[Test]
    public function createTestMMAndAddGroupMM1Relation(): void
    {
        parent::createTestMMAndAddGroupMM1Relation();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/createTestMMAndAddGroupMM1Relation.csv');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, (new StructureHasRecordConstraint())
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdFirst)->setRecordField(self::FIELD_GROUP_MM_1_FOREIGN)
            ->setTable(self::TABLE_TEST_MM)->setField('title')->setValues('Surf A', 'Surf B', 'Surfing #1'));
    }

    #[Test]
    public function createTestMMAndContentWithAddedGroupMM1Relation(): void
    {
        parent::createTestMMAndContentWithAddedGroupMM1Relation();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/createTestMMAndContentWithAddedGroupMM1Relation.csv');
    }

    #[Test]
    public function createTestMMAndContentWithGroupMM1Relation(): void
    {
        parent::createTestMMAndContentWithGroupMM1Relation();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/createTestMMAndContentWithGroupMM1Relation.csv');
    }

    #[Test]
    public function createContentAndAddGroupMM1Relation(): void
    {
        parent::createContentAndAddGroupMM1Relation();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/createContentAndAddGroupMM1Relation.csv');
    }

    #[Test]
    public function createContentAndTestMMWithAddedGroupMM1Relation(): void
    {
        parent::createContentAndTestMMWithAddedGroupMM1Relation();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/createContentAndTestMMWithAddedGroupMM1Relation.csv');
    }

    #[Test]
    public function createContentAndTestMMWithGroupMM1Relation(): void
    {
        parent::createContentAndTestMMWithGroupMM1Relation();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/createContentAndTestMMWithGroupMM1Relation.csv');
    }

    #[Test]
    public function deleteContentWithMultipleRelations(): void
    {
        parent::deleteContentWithMultipleRelations();
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
        $this->assertCSVDataSet(__DIR__ . '/DataSet/deleteContentWithMultipleRelationsAndWithoutSoftDelete.csv');
    }

    #[Test]
    public function deleteGroupMM1RelationOnForeignSide(): void
    {
        parent::deleteGroupMM1RelationOnForeignSide();
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
    public function deleteTestMM(): void
    {
        parent::deleteTestMM();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/deleteTestMM.csv');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, (new StructureDoesNotHaveRecordConstraint())
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdFirst)->setRecordField(self::FIELD_GROUP_MM_1_FOREIGN)
            ->setTable(self::TABLE_TEST_MM)->setField('title')->setValues('Surf A'));
    }

    #[Test]
    public function localizeContent(): void
    {
        parent::localizeContent();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/localizeContent.csv');

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
        $this->assertCSVDataSet(__DIR__ . '/DataSet/localizeContentAndAddTestMMWithLanguageSynchronization.csv');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId)->withLanguageId(self::VALUE_LanguageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, (new StructureHasRecordConstraint())
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdLast)->setRecordField(self::FIELD_GROUP_MM_1_FOREIGN)
            ->setTable(self::TABLE_TEST_MM)->setField('title')->setValues('Surf B', 'Surf C', 'Surf A.A'));
    }

    #[Test]
    public function localizeContentChainAndAddTestMMWithLanguageSynchronization(): void
    {
        parent::localizeContentChainAndAddTestMMWithLanguageSynchronization();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/localizeContentChainAndAddTestMMWithLanguageSynchronization.csv');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId)->withLanguageId(self::VALUE_LanguageIdSecond));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, (new StructureHasRecordConstraint())
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdLast)->setRecordField(self::FIELD_GROUP_MM_1_FOREIGN)
            ->setTable(self::TABLE_TEST_MM)->setField('title')->setValues('Surf B', 'Surf C', 'Surf A.A'));
    }

    #[Test]
    public function localizeContentSelect1MMForeign(): void
    {
        parent::localizeContentSelect1MMForeign();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/localizeContentSelect1MMForeign.csv');
    }

    #[Test]
    public function localizeContentSelect1MMForeignWithExclude(): void
    {
        parent::localizeContentSelect1MMForeignWithExclude();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/localizeContentSelect1MMForeignWithExclude.csv');
    }

    #[Test]
    public function localizeContentSelect1MMForeignWithLanguageSynchronization(): void
    {
        parent::localizeContentSelect1MMForeignWithLanguageSynchronization();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/localizeContentSelect1MMForeignWithLanguageSynchronization.csv');
    }

    #[Test]
    public function localizeContentWithLanguageExclude(): void
    {
        parent::localizeContentWithLanguageExclude();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/localizeContentWithLanguageExclude.csv');

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
        $this->assertCSVDataSet(__DIR__ . '/DataSet/localizeContentWithLanguageSynchronization.csv');

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
        $this->assertCSVDataSet(__DIR__ . '/DataSet/localizeTestMM.csv');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId)->withLanguageId(self::VALUE_LanguageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, (new StructureHasRecordConstraint())
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdFirst)->setRecordField(self::FIELD_GROUP_MM_1_FOREIGN)
            ->setTable(self::TABLE_TEST_MM)->setField('title')->setValues('[Translate to Dansk:] Surf A', 'Surf B'));
    }

    #[Test]
    public function localizeTestMMSelect1MMLocal(): void
    {
        parent::localizeTestMMSelect1MMLocal();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/localizeTestMMSelect1MMLocal.csv');
    }

    #[Test]
    public function localizeTestMMSelect1MMLocalWithExclude(): void
    {
        parent::localizeTestMMSelect1MMLocalWithExclude();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/localizeTestMMSelect1MMLocalWithExclude.csv');
    }

    #[Test]
    public function localizeTestMMSelect1MMLocalWithLanguageSynchronization(): void
    {
        parent::localizeTestMMSelect1MMLocalWithLanguageSynchronization();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/localizeTestMMSelect1MMLocalWithLanguageSynchronization.csv');
    }

    #[Test]
    public function modifyContent(): void
    {
        parent::modifyContent();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/modifyContent.csv');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, (new HasRecordConstraint())
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Surfing #1'));
    }

    #[Test]
    public function modifyTestMM(): void
    {
        parent::modifyTestMM();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/modifyTestMM.csv');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, (new StructureHasRecordConstraint())
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdFirst)->setRecordField(self::FIELD_GROUP_MM_1_FOREIGN)
            ->setTable(self::TABLE_TEST_MM)->setField('title')->setValues('Surfing #1', 'Surf B'));
    }

    #[Test]
    public function modifyTestMMAndContent(): void
    {
        parent::modifyTestMMAndContent();
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
    public function moveContentToDifferentPage(): void
    {
        parent::moveContentToDifferentPage();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/moveContentToDifferentPage.csv');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageIdTarget));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, (new StructureHasRecordConstraint())
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdLast)->setRecordField(self::FIELD_GROUP_MM_1_FOREIGN)
            ->setTable(self::TABLE_TEST_MM)->setField('title')->setValues('Surf B', 'Surf C'));
    }
}
