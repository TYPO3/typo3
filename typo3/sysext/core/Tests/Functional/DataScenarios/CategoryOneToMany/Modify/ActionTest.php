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

namespace TYPO3\CMS\Core\Tests\Functional\DataScenarios\CategoryOneToMany\Modify;

use TYPO3\CMS\Core\Tests\Functional\DataScenarios\CategoryOneToMany\AbstractActionTestCase;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\ResponseContent;

/**
 * Functional test for the DataHandler
 */
class ActionTest extends AbstractActionTestCase
{
    /**
     * @test
     */
    public function verifyCleanReferenceIndex(): void
    {
        // The test verifies the imported data set has a clean reference index by the check in tearDown()
        self::assertTrue(true);
    }

    /**
     * @test
     * See DataSet/addCategoryRelation.csv
     */
    public function addCategoryRelation(): void
    {
        parent::addCategoryRelation();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/addCategoryRelation.csv');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();

        self::assertThat(
            $responseSections,
            $this->getRequestSectionStructureHasRecordConstraint()
                ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdLast)->setRecordField(self::FIELD_Categories)
                ->setTable(self::TABLE_Category)->setField('title')->setValues('Category C')
        );
    }

    /**
     * @test
     * See DataSet/addCategoryRelations.csv
     */
    public function addCategoryRelations(): void
    {
        parent::addCategoryRelations();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/addCategoryRelations.csv');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();

        self::assertThat(
            $responseSections,
            $this->getRequestSectionStructureHasRecordConstraint()
                ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdLast)->setRecordField(self::FIELD_Categories)
                ->setTable(self::TABLE_Category)->setField('title')->setValues('Category C', 'Category A.A')
        );
    }

    /**
     * @test
     * See DataSet/addCategoryRelationToExisting.csv
     */
    public function addCategoryRelationToExisting(): void
    {
        parent::addCategoryRelationToExisting();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/addCategoryRelationToExisting.csv');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();

        self::assertThat(
            $responseSections,
            $this->getRequestSectionStructureHasRecordConstraint()
                 ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdFirst)->setRecordField(self::FIELD_Categories)
                 ->setTable(self::TABLE_Category)->setField('title')->setValues('Category A', 'Category B', 'Category C')
        );
    }

    /**
     * @test
     * See DataSet/addCategoryRelationsToExisting.csv
     */
    public function addCategoryRelationsToExisting(): void
    {
        parent::addCategoryRelationsToExisting();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/addCategoryRelationsToExisting.csv');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();

        self::assertThat(
            $responseSections,
            $this->getRequestSectionStructureHasRecordConstraint()
                 ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdFirst)->setRecordField(self::FIELD_Categories)
                 ->setTable(self::TABLE_Category)->setField('title')->setValues('Category A', 'Category B', 'Category C', 'Category A.A')
        );
    }

    /**
     * @test
     * See DataSet/createAndAddCategoryRelation.csv
     */
    public function createAndAddCategoryRelation(): void
    {
        parent::createAndAddCategoryRelation();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/createAndAddCategoryRelation.csv');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();

        self::assertThat(
            $responseSections,
            $this->getRequestSectionHasRecordConstraint()
                 ->setTable(self::TABLE_Category)->setField('title')->setValues('Category B.A')
        );

        self::assertThat(
            $responseSections,
            $this->getRequestSectionStructureHasRecordConstraint()
                 ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdFirst)->setRecordField(self::FIELD_Categories)
                 ->setTable(self::TABLE_Category)->setField('title')->setValues('Category A', 'Category B', 'Category B.A')
        );

        self::assertThat(
            $responseSections,
            $this->getRequestSectionStructureHasRecordConstraint()
                 ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdLast)->setRecordField(self::FIELD_Categories)
                 ->setTable(self::TABLE_Category)->setField('title')->setValues('Category B.A')
        );
    }

    /**
     * @test
     * See DataSet/createAndReplaceCategoryRelation.csv
     */
    public function createAndReplaceCategoryRelation(): void
    {
        parent::createAndReplaceCategoryRelation();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/createAndReplaceCategoryRelation.csv');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();

        self::assertThat(
            $responseSections,
            $this->getRequestSectionHasRecordConstraint()
                 ->setTable(self::TABLE_Category)->setField('title')->setValues('Category B.A')
        );

        self::assertThat(
            $responseSections,
            $this->getRequestSectionStructureHasRecordConstraint()
                 ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdFirst)->setRecordField(self::FIELD_Categories)
                 ->setTable(self::TABLE_Category)->setField('title')->setValues('Category B.A')
        );
    }

    /**
     * @test
     * See DataSet/addAndDeleteCategoryRelationsOnExisting.csv
     */
    public function addAndDeleteCategoryRelationsOnExisting(): void
    {
        parent::addAndDeleteCategoryRelationsOnExisting();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/addAndDeleteCategoryRelationsOnExisting.csv');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();

        self::assertThat(
            $responseSections,
            $this->getRequestSectionStructureHasRecordConstraint()
                 ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdFirst)->setRecordField(self::FIELD_Categories)
                 ->setTable(self::TABLE_Category)->setField('title')->setValues('Category A', 'Category C')
        );
    }

    /**
     * @test
     * See DataSet/modifyReferencingContentElement.csv
     */
    public function modifyReferencingContentElement(): void
    {
        parent::modifyReferencingContentElement();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/modifyReferencingContentElement.csv');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();

        self::assertThat(
            $responseSections,
            $this->getRequestSectionHasRecordConstraint()
                 ->setTable(self::TABLE_Content)->setField('header')->setValues('Testing #1')
        );
    }

    /**
     * @test
     * See DataSet/modifyContentOfRelatedCategory.csv
     */
    public function modifyContentOfRelatedCategory(): void
    {
        parent::modifyContentOfRelatedCategory();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/modifyContentOfRelatedCategory.csv');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();

        self::assertThat(
            $responseSections,
            $this->getRequestSectionStructureHasRecordConstraint()
                 ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdFirst)->setRecordField(self::FIELD_Categories)
                 ->setTable(self::TABLE_Category)->setField('title')->setValues('Category A', 'Testing #1')
        );
    }

    /**
     * @test
     * See DataSet/deleteCategoryRelation.csv
     */
    public function moveContentAndCategoryRelationToDifferentPage(): void
    {
        parent::moveContentAndCategoryRelationToDifferentPage();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/moveContentAndCategoryRelationToDifferentPage.csv');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_TargetPageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();

        self::assertThat(
            $responseSections,
            $this->getRequestSectionStructureHasRecordConstraint()
                 ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdFirst)->setRecordField(self::FIELD_Categories)
                 ->setTable(self::TABLE_Category)->setField('title')->setValues('Category A', 'Category B')
        );
    }

    /**
     * @test
     * See DataSet/changeContentAndCategorySorting.csv
     */
    public function changeContentAndCategorySorting(): void
    {
        parent::changeContentAndCategorySorting();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/changeContentAndCategorySorting.csv');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();

        self::assertThat(
            $responseSections,
            $this->getRequestSectionStructureHasRecordConstraint()
                 ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdFirst)->setRecordField(self::FIELD_Categories)
                 ->setTable(self::TABLE_Category)->setField('title')->setValues('Category B', 'Category A')
        );
    }

    /**
     * @test
     * See DataSet/deleteCategoryRelation.csv
     */
    public function deleteCategoryRelation(): void
    {
        parent::deleteCategoryRelation();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/deleteCategoryRelation.csv');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();

        self::assertThat(
            $responseSections,
            $this->getRequestSectionStructureHasRecordConstraint()
                 ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdFirst)->setRecordField(self::FIELD_Categories)
                 ->setTable(self::TABLE_Category)->setField('title')->setValues('Category A')
        );

        self::assertThat(
            $responseSections,
            $this->getRequestSectionStructureDoesNotHaveRecordConstraint()
                 ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdFirst)->setRecordField(self::FIELD_Categories)
                 ->setTable(self::TABLE_Category)->setField('title')->setValues('Category B')
        );
    }

    /**
     * @test
     * See DataSet/deleteCategoryRelations.csv
     */
    public function deleteCategoryRelations(): void
    {
        parent::deleteCategoryRelations();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/deleteCategoryRelations.csv');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();

        self::assertThat(
            $responseSections,
            $this->getRequestSectionStructureDoesNotHaveRecordConstraint()
                 ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdFirst)->setRecordField(self::FIELD_Categories)
                 ->setTable(self::TABLE_Category)->setField('title')->setValues('Category A', 'Category B')
        );
    }
}
