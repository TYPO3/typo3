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

namespace TYPO3\CMS\Core\Tests\Functional\DataHandling\Category\ManyToMany\Modify;

use TYPO3\CMS\Core\Tests\Functional\DataHandling\Category\ManyToMany\AbstractActionTestCase;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\ResponseContent;

/**
 * Functional test for the DataHandler
 */
class ActionTest extends AbstractActionTestCase
{
    /**
     * @var string
     */
    protected $assertionDataSetDirectory = 'typo3/sysext/core/Tests/Functional/DataHandling/Category/ManyToMany/Modify/DataSet/';

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
        $this->assertAssertionDataSet('addCategoryRelation');

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
        $this->assertAssertionDataSet('addCategoryRelations');

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
        $this->assertAssertionDataSet('addCategoryRelationToExisting');

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
        $this->assertAssertionDataSet('addCategoryRelationsToExisting');

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
        $this->assertAssertionDataSet('createAndAddCategoryRelation');

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
        $this->assertAssertionDataSet('createAndReplaceCategoryRelation');

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
        $this->assertAssertionDataSet('addAndDeleteCategoryRelationsOnExisting');

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
        $this->assertAssertionDataSet('modifyReferencingContentElement');

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
        $this->assertAssertionDataSet('modifyContentOfRelatedCategory');

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
        $this->assertAssertionDataSet('moveContentAndCategoryRelationToDifferentPage');

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
        $this->assertAssertionDataSet('changeContentAndCategorySorting');

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
        $this->assertAssertionDataSet('deleteCategoryRelation');

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
        $this->assertAssertionDataSet('deleteCategoryRelations');

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
