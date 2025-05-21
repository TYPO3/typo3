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

namespace TYPO3\CMS\Core\Tests\Functional\DataScenarios\CategoryManyToMany\Modify;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\ReferenceIndex;
use TYPO3\CMS\Core\Tests\Functional\DataScenarios\CategoryManyToMany\AbstractActionTestCase;
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
    public function addCategoryRelation(): void
    {
        parent::addCategoryRelation();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/addCategoryRelation.csv');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, (new StructureHasRecordConstraint())
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdFirst)->setRecordField('categories')
            ->setTable(self::TABLE_Category)->setField('title')->setValues('Category A', 'Category B', 'Category A.A'));
    }

    #[Test]
    public function addCategoryRelations(): void
    {
        parent::addCategoryRelations();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/addCategoryRelations.csv');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();

        self::assertThat(
            $responseSections,
            (new StructureHasRecordConstraint())
                ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdLast)->setRecordField(self::FIELD_Categories)
                ->setTable(self::TABLE_Category)->setField('title')->setValues('Category A', 'Category B', 'Category C', 'Category A.A')
        );
    }

    #[Test]
    public function createAndAddCategoryRelation(): void
    {
        parent::createAndAddCategoryRelation();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/createAndAddCategoryRelation.csv');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();

        self::assertThat(
            $responseSections,
            (new HasRecordConstraint())
                 ->setTable(self::TABLE_Category)->setField('title')->setValues('Category B.A')
        );

        self::assertThat(
            $responseSections,
            (new StructureHasRecordConstraint())
                 ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdFirst)->setRecordField(self::FIELD_Categories)
                 ->setTable(self::TABLE_Category)->setField('title')->setValues('Category A', 'Category B', 'Category B.A')
        );

        self::assertThat(
            $responseSections,
            (new StructureHasRecordConstraint())
                 ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdLast)->setRecordField(self::FIELD_Categories)
                 ->setTable(self::TABLE_Category)->setField('title')->setValues('Category B.A')
        );
    }

    #[Test]
    public function createAndReplaceCategoryRelation(): void
    {
        parent::createAndReplaceCategoryRelation();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/createAndReplaceCategoryRelation.csv');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();

        self::assertThat(
            $responseSections,
            (new HasRecordConstraint())
                 ->setTable(self::TABLE_Category)->setField('title')->setValues('Category B.A')
        );

        self::assertThat(
            $responseSections,
            (new StructureHasRecordConstraint())
                 ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdFirst)->setRecordField(self::FIELD_Categories)
                 ->setTable(self::TABLE_Category)->setField('title')->setValues('Category B.A')
        );
    }

    #[Test]
    public function modifyReferencingContentElement(): void
    {
        parent::modifyReferencingContentElement();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/modifyReferencingContentElement.csv');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();

        self::assertThat(
            $responseSections,
            (new HasRecordConstraint())
                 ->setTable(self::TABLE_Content)->setField('header')->setValues('Testing #1')
        );
    }

    #[Test]
    public function modifyContentOfRelatedCategory(): void
    {
        parent::modifyContentOfRelatedCategory();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/modifyContentOfRelatedCategory.csv');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();

        self::assertThat(
            $responseSections,
            (new StructureHasRecordConstraint())
                 ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdFirst)->setRecordField(self::FIELD_Categories)
                 ->setTable(self::TABLE_Category)->setField('title')->setValues('Category A', 'Testing #1')
        );
    }

    #[Test]
    public function modifyBothOfRelation(): void
    {
        parent::modifyBothOfRelation();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/modifyBothOfRelation.csv');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, (new StructureHasRecordConstraint())
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdFirst)->setRecordField('categories')
            ->setTable(self::TABLE_Category)->setField('title')->setValues('Testing #1', 'Category B'));
        self::assertThat($responseSections, (new HasRecordConstraint())
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Testing #1'));
    }

    #[Test]
    public function moveContentAndCategoryRelationToDifferentPage(): void
    {
        parent::moveContentAndCategoryRelationToDifferentPage();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/moveContentAndCategoryRelationToDifferentPage.csv');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_TargetPageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();

        self::assertThat(
            $responseSections,
            (new StructureHasRecordConstraint())
                 ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdFirst)->setRecordField(self::FIELD_Categories)
                 ->setTable(self::TABLE_Category)->setField('title')->setValues('Category A', 'Category B')
        );
    }

    #[Test]
    public function changeCategoryRelationSorting(): void
    {
        // @todo: Needs patch. Import data set should have sorting 1 for 29-298 in mm, then DH needs to
        //        be fixed to trigger update of refindex properly on local-side resort. Workspaces may or
        //        may not need adaption as well, at least the import has a dupe sorting as well.
        self::markTestSkipped('currently disabled since DH does not update refindex properly');
        parent::changeCategoryRelationSorting(); // @phpstan-ignore-line
        $this->assertCSVDataSet(__DIR__ . '/DataSet/changeCategoryRelationSorting.csv');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, (new StructureHasRecordConstraint())
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdFirst)->setRecordField('categories')
            ->setTable(self::TABLE_Category)->setField('title')->setValues('Category A', 'Category B'));
    }

    #[Test]
    public function changeContentAndCategorySorting(): void
    {
        parent::changeContentAndCategorySorting();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/changeContentAndCategorySorting.csv');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();

        self::assertThat(
            $responseSections,
            (new StructureHasRecordConstraint())
                 ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdFirst)->setRecordField(self::FIELD_Categories)
                 ->setTable(self::TABLE_Category)->setField('title')->setValues('Category B', 'Category A')
        );
    }

    #[Test]
    public function deleteCategoryRelation(): void
    {
        parent::deleteCategoryRelation();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/deleteCategoryRelation.csv');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();

        self::assertThat(
            $responseSections,
            (new StructureHasRecordConstraint())
                 ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdFirst)->setRecordField(self::FIELD_Categories)
                 ->setTable(self::TABLE_Category)->setField('title')->setValues('Category A')
        );

        self::assertThat(
            $responseSections,
            (new StructureDoesNotHaveRecordConstraint())
                 ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdFirst)->setRecordField(self::FIELD_Categories)
                 ->setTable(self::TABLE_Category)->setField('title')->setValues('Category B')
        );
    }

    #[Test]
    public function deleteCategoryRelations(): void
    {
        parent::deleteCategoryRelations();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/deleteCategoryRelations.csv');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();

        self::assertThat(
            $responseSections,
            (new StructureDoesNotHaveRecordConstraint())
                ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdFirst)->setRecordField(self::FIELD_Categories)
                ->setTable(self::TABLE_Category)->setField('title')->setValues('Category A', 'Category B')
        );
    }

    #[Test]
    public function deleteCategoryOfRelation(): void
    {
        parent::deleteCategoryOfRelation();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/deleteCategoryOfRelation.csv');
    }

    #[Test]
    public function deleteCategoryThenHardDeleteCategory(): void
    {
        // This test is only done in live since this is a "recycler" scenario and "recycler" is disabled in workspaces.
        parent::deleteCategoryThenHardDeleteCategory();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/deleteCategoryThenHardDeleteCategory.csv');
    }

    #[Test]
    public function deleteContentOfRelation(): void
    {
        parent::deleteContentOfRelation();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/deleteContentOfRelation.csv');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, (new DoesNotHaveRecordConstraint())
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Testing #1'));
    }

    #[Test]
    public function deleteContentThenHardDeleteContent(): void
    {
        // This test is only done in live since this is a "recycler" scenario and "recycler" is disabled in workspaces.
        parent::deleteContentThenHardDeleteContent();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/deleteContentThenHardDeleteContent.csv');
    }

    #[Test]
    public function copyContentOfRelation(): void
    {
        parent::copyContentOfRelation();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/copyContentOfRelation.csv');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, (new StructureHasRecordConstraint())
            ->setRecordIdentifier(self::TABLE_Content . ':' . $this->recordIds['newContentId'])->setRecordField('categories')
            ->setTable(self::TABLE_Category)->setField('title')->setValues('Category A', 'Category B'));
    }

    #[Test]
    public function copyCategoryOfRelation(): void
    {
        parent::copyCategoryOfRelation();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/copyCategoryOfRelation.csv');
    }

    #[Test]
    public function copyContentToLanguageOfRelation(): void
    {
        parent::copyContentToLanguageOfRelation();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/copyContentToLanguageOfRelation.csv');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId)->withLanguageId(self::VALUE_LanguageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, (new StructureHasRecordConstraint())
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdFirst)->setRecordField('categories')
            ->setTable(self::TABLE_Category)->setField('title')->setValues('Category A', 'Category B'));
    }

    #[Test]
    public function copyCategoryToLanguageOfRelation(): void
    {
        parent::copyCategoryToLanguageOfRelation();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/copyCategoryToLanguageOfRelation.csv');
        //in this case the translated element is orphaned (no CE with relation to it, and it has no l10n_parent)
        //so on frontend there is no change.
        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId)->withLanguageId(self::VALUE_LanguageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, (new StructureHasRecordConstraint())
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdFirst)->setRecordField('categories')
            ->setTable(self::TABLE_Category)->setField('title')->setValues('Category A', 'Category B'));
    }

    #[Test]
    public function copyPage(): void
    {
        parent::copyPage();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/copyPage.csv');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId($this->recordIds['newPageId']));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, (new HasRecordConstraint())
            ->setTable(self::TABLE_Page)->setField('title')->setValues('DataHandlerTest'));
        self::assertThat($responseSections, (new HasRecordConstraint())
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Regular Element #1', 'Regular Element #2'));
        self::assertThat($responseSections, (new StructureHasRecordConstraint())
            ->setRecordIdentifier(self::TABLE_Content . ':' . $this->recordIds['newContentIdFirst'])->setRecordField('categories')
            ->setTable(self::TABLE_Category)->setField('title')->setValues('Category A', 'Category B'));
        self::assertThat($responseSections, (new StructureHasRecordConstraint())
            ->setRecordIdentifier(self::TABLE_Content . ':' . $this->recordIds['newContentIdLast'])->setRecordField('categories')
            ->setTable(self::TABLE_Category)->setField('title')->setValues('Category B', 'Category C'));
    }

    #[Test]
    public function localizeCategoryOfRelation(): void
    {
        // Create translated page first
        $this->actionService->copyRecordToLanguage(self::TABLE_Page, self::VALUE_PageId, self::VALUE_LanguageId);
        parent::localizeCategoryOfRelation();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/localizeCategoryOfRelation.csv');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId)->withLanguageId(self::VALUE_LanguageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, (new StructureHasRecordConstraint())
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdFirst)->setRecordField('categories')
            ->setTable(self::TABLE_Category)->setField('title')->setValues('[Translate to Dansk:] Category A', 'Category B'));
    }

    #[Test]
    public function localizeContentOfRelation(): void
    {
        parent::localizeContentOfRelation();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/localizeContentOfRelation.csv');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId)->withLanguageId(self::VALUE_LanguageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, (new StructureHasRecordConstraint())
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdLast)->setRecordField('categories')
            ->setTable(self::TABLE_Category)->setField('title')->setValues('Category B', 'Category C'));
    }

    #[Test]
    public function localizeContentChainOfRelationAndAddCategoryWithLanguageSynchronization(): void
    {
        parent::localizeContentChainOfRelationAndAddCategoryWithLanguageSynchronization();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/localizeContentChainOfRelationNAddCategoryWSynchronization.csv');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId)->withLanguageId(self::VALUE_LanguageIdSecond));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, (new StructureHasRecordConstraint())
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdLast)->setRecordField('categories')
            ->setTable(self::TABLE_Category)->setField('title')->setValues('Category B', 'Category C', 'Category A.A'));
    }

    #[Test]
    public function localizeContentOfRelationAndAddCategoryWithLanguageSynchronization(): void
    {
        parent::localizeContentOfRelationAndAddCategoryWithLanguageSynchronization();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/localizeContentOfRelationNAddCategoryWSynchronization.csv');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId)->withLanguageId(self::VALUE_LanguageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, (new StructureHasRecordConstraint())
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdLast)->setRecordField('categories')
            ->setTable(self::TABLE_Category)->setField('title')->setValues('Category B', 'Category C', 'Category A.A'));
    }

    #[Test]
    public function localizeContentOfRelationWithLanguageSynchronization(): void
    {
        parent::localizeContentOfRelationWithLanguageSynchronization();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/localizeContentOfRelationWSynchronization.csv');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId)->withLanguageId(self::VALUE_LanguageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, (new StructureHasRecordConstraint())
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdLast)->setRecordField('categories')
            ->setTable(self::TABLE_Category)->setField('title')->setValues('Category B', 'Category C'));
    }

    #[Test]
    public function localizeContentOfRelationWithLanguageExclude(): void
    {
        parent::localizeContentOfRelationWithLanguageExclude();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/localizeContentOfRelationWExclude.csv');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId)->withLanguageId(self::VALUE_LanguageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, (new StructureHasRecordConstraint())
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdLast)->setRecordField('categories')
            ->setTable(self::TABLE_Category)->setField('title')->setValues('Category B', 'Category C'));
    }

    #[Test]
    public function moveContentOfRelationToDifferentPage(): void
    {
        parent::moveContentOfRelationToDifferentPage();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/moveContentOfRelationToDifferentPage.csv');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_TargetPageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, (new StructureHasRecordConstraint())
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdLast)->setRecordField('categories')
            ->setTable(self::TABLE_Category)->setField('title')->setValues('Category B', 'Category C'));
    }
}
