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

namespace TYPO3\CMS\Workspaces\Tests\Functional\DataHandling\ManyToMany\PublishAll;

use TYPO3\CMS\Workspaces\Tests\Functional\DataHandling\ManyToMany\AbstractActionTestCase;
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
    protected $assertionDataSetDirectory = 'typo3/sysext/workspaces/Tests/Functional/DataHandling/ManyToMany/PublishAll/DataSet/';

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
     */
    public function addCategoryRelation(): void
    {
        parent::addCategoryRelation();
        $this->actionService->publishWorkspace(self::VALUE_WorkspaceId);
        $this->assertAssertionDataSet('addCategoryRelation');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdFirst)->setRecordField('categories')
            ->setTable(self::TABLE_Category)->setField('title')->setValues('Category A', 'Category B', 'Category A.A'));

        // @todo: reference index not clean after this test. Needs investigation.
        $this->assertCleanReferenceIndex = false;
    }

    /**
     * @test
     */
    public function deleteCategoryRelation(): void
    {
        parent::deleteCategoryRelation();
        $this->actionService->publishWorkspace(self::VALUE_WorkspaceId);
        $this->assertAssertionDataSet('deleteCategoryRelation');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdFirst)->setRecordField('categories')
            ->setTable(self::TABLE_Category)->setField('title')->setValues('Category A'));
        self::assertThat($responseSections, $this->getRequestSectionStructureDoesNotHaveRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdFirst)->setRecordField('categories')
            ->setTable(self::TABLE_Category)->setField('title')->setValues('Category B', 'Category C', 'Category A.A'));

        // @todo: reference index not clean after this test. Needs investigation.
        $this->assertCleanReferenceIndex = false;
    }

    /**
     * @test
     */
    public function changeCategoryRelationSorting(): void
    {
        parent::changeCategoryRelationSorting();
        $this->actionService->publishWorkspace(self::VALUE_WorkspaceId);
        $this->assertAssertionDataSet('changeCategoryRelationSorting');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdFirst)->setRecordField('categories')
            ->setTable(self::TABLE_Category)->setField('title')->setValues('Category A', 'Category B'));

        // @todo: reference index not clean after this test. Needs investigation.
        $this->assertCleanReferenceIndex = false;
    }

    /**
     * @test
     */
    public function createContentAndAddRelation(): void
    {
        parent::createContentAndAddRelation();
        $this->actionService->publishWorkspace(self::VALUE_WorkspaceId);
        $this->assertAssertionDataSet('createContentNAddRelation');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, $this->getRequestSectionHasRecordConstraint()
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Testing #1'));
        self::assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':' . $this->recordIds['newContentId'])->setRecordField('categories')
            ->setTable(self::TABLE_Category)->setField('title')->setValues('Category B'));

        // @todo: reference index not clean after this test. Needs investigation.
        $this->assertCleanReferenceIndex = false;
    }

    /**
     * @test
     */
    public function createCategoryAndAddRelation(): void
    {
        parent::createCategoryAndAddRelation();
        $this->actionService->publishWorkspace(self::VALUE_WorkspaceId);
        $this->assertAssertionDataSet('createCategoryNAddRelation');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, $this->getRequestSectionHasRecordConstraint()
            ->setTable(self::TABLE_Category)->setField('title')->setValues('Testing #1'));
        self::assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdFirst)->setRecordField('categories')
            ->setTable(self::TABLE_Category)->setField('title')->setValues('Testing #1'));
    }

    /**
     * @test
     */
    public function createContentAndCreateRelation(): void
    {
        parent::createContentAndCreateRelation();
        $this->actionService->publishWorkspace(self::VALUE_WorkspaceId);
        $this->assertAssertionDataSet('createContentNCreateRelation');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, $this->getRequestSectionHasRecordConstraint()
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Testing #1'));
        self::assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':' . $this->recordIds['newContentId'])->setRecordField('categories')
            ->setTable(self::TABLE_Category)->setField('title')->setValues('Testing #1'));
    }

    /**
     * @test
     */
    public function createCategoryAndCreateRelation(): void
    {
        parent::createCategoryAndCreateRelation();
        $this->actionService->publishWorkspace(self::VALUE_WorkspaceId);
        $this->assertAssertionDataSet('createCategoryNCreateRelation');
    }

    /**
     * @test
     */
    public function createContentWithCategoryAndAddRelation(): void
    {
        parent::createContentWithCategoryAndAddRelation();
        $this->actionService->publishWorkspace(self::VALUE_WorkspaceId);
        $this->assertAssertionDataSet('createContentWCategoryNAddRelation');
    }

    /**
     * @test
     */
    public function createCategoryWithContentAndAddRelation(): void
    {
        parent::createCategoryWithContentAndAddRelation();
        $this->actionService->publishWorkspace(self::VALUE_WorkspaceId);
        $this->assertAssertionDataSet('createCategoryWContentNAddRelation');
    }

    /**
     * @test
     */
    public function modifyCategoryOfRelation(): void
    {
        parent::modifyCategoryOfRelation();
        $this->actionService->publishWorkspace(self::VALUE_WorkspaceId);
        $this->assertAssertionDataSet('modifyCategoryOfRelation');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdFirst)->setRecordField('categories')
            ->setTable(self::TABLE_Category)->setField('title')->setValues('Testing #1', 'Category B'));
    }

    /**
     * @test
     */
    public function modifyContentOfRelation(): void
    {
        parent::modifyContentOfRelation();
        $this->actionService->publishWorkspace(self::VALUE_WorkspaceId);
        $this->assertAssertionDataSet('modifyContentOfRelation');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, $this->getRequestSectionHasRecordConstraint()
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Testing #1'));

        // @todo: reference index not clean after this test. Needs investigation.
        $this->assertCleanReferenceIndex = false;
    }

    /**
     * @test
     */
    public function modifyBothsOfRelation(): void
    {
        parent::modifyBothsOfRelation();
        $this->actionService->publishWorkspace(self::VALUE_WorkspaceId);
        $this->assertAssertionDataSet('modifyBothsOfRelation');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdFirst)->setRecordField('categories')
            ->setTable(self::TABLE_Category)->setField('title')->setValues('Testing #1', 'Category B'));
        self::assertThat($responseSections, $this->getRequestSectionHasRecordConstraint()
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Testing #1'));

        // @todo: reference index not clean after this test. Needs investigation.
        $this->assertCleanReferenceIndex = false;
    }

    /**
     * @test
     */
    public function deleteContentOfRelation(): void
    {
        parent::deleteContentOfRelation();
        $this->actionService->publishWorkspace(self::VALUE_WorkspaceId);
        $this->assertAssertionDataSet('deleteContentOfRelation');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, $this->getRequestSectionDoesNotHaveRecordConstraint()
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Testing #1'));

        // @todo: reference index not clean after this test. Needs investigation.
        $this->assertCleanReferenceIndex = false;
    }

    /**
     * @test
     */
    public function deleteCategoryOfRelation(): void
    {
        parent::deleteCategoryOfRelation();
        $this->actionService->publishWorkspace(self::VALUE_WorkspaceId);
        $this->assertAssertionDataSet('deleteCategoryOfRelation');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, $this->getRequestSectionStructureDoesNotHaveRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdFirst)->setRecordField('categories')
            ->setTable(self::TABLE_Category)->setField('title')->setValues('Category A'));
    }

    /**
     * @test
     */
    public function copyContentOfRelation(): void
    {
        parent::copyContentOfRelation();
        $this->actionService->publishWorkspace(self::VALUE_WorkspaceId);
        $this->assertAssertionDataSet('copyContentOfRelation');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':' . $this->recordIds['newContentId'])->setRecordField('categories')
            ->setTable(self::TABLE_Category)->setField('title')->setValues('Category B', 'Category C'));

        // @todo: reference index not clean after this test. Needs investigation.
        $this->assertCleanReferenceIndex = false;
    }

    /**
     * @test
     */
    public function copyCategoryOfRelation(): void
    {
        parent::copyCategoryOfRelation();
        $this->actionService->publishWorkspace(self::VALUE_WorkspaceId);
        $this->assertAssertionDataSet('copyCategoryOfRelation');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdFirst)->setRecordField('categories')
            ->setTable(self::TABLE_Category)->setField('title')->setValues('Category A', 'Category A (copy 1)'));
    }

    /**
     * @test
     */
    public function localizeContentOfRelation(): void
    {
        parent::localizeContentOfRelation();
        $this->actionService->publishWorkspace(self::VALUE_WorkspaceId);
        $this->assertAssertionDataSet('localizeContentOfRelation');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId)->withLanguageId(self::VALUE_LanguageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdLast)->setRecordField('categories')
            ->setTable(self::TABLE_Category)->setField('title')->setValues('Category B', 'Category C'));

        // @todo: reference index not clean after this test. Needs investigation.
        $this->assertCleanReferenceIndex = false;
    }

    /**
     * @test
     */
    public function localizeCategoryOfRelation(): void
    {
        // Create translated page first
        $this->actionService->copyRecordToLanguage(self::TABLE_Page, self::VALUE_PageId, self::VALUE_LanguageId);
        parent::localizeCategoryOfRelation();
        $this->actionService->publishWorkspace(self::VALUE_WorkspaceId);
        $this->assertAssertionDataSet('localizeCategoryOfRelation');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId)->withLanguageId(self::VALUE_LanguageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdFirst)->setRecordField('categories')
            ->setTable(self::TABLE_Category)->setField('title')->setValues('[Translate to Dansk:] Category A', 'Category B'));
    }

    /**
     * @test
     */
    public function moveContentOfRelationToDifferentPage(): void
    {
        parent::moveContentOfRelationToDifferentPage();
        $this->actionService->publishWorkspace(self::VALUE_WorkspaceId);
        $this->assertAssertionDataSet('moveContentOfRelationToDifferentPage');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageIdTarget));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdLast)->setRecordField('categories')
            ->setTable(self::TABLE_Category)->setField('title')->setValues('Category B', 'Category C'));

        // @todo: reference index not clean after this test. Needs investigation.
        $this->assertCleanReferenceIndex = false;
    }

    /**
     * @test
     */
    public function copyPage(): void
    {
        parent::copyPage();
        $this->actionService->publishWorkspace(self::VALUE_WorkspaceId);
        $this->assertAssertionDataSet('copyPage');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId($this->recordIds['newPageId']));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, $this->getRequestSectionHasRecordConstraint()
            ->setTable(self::TABLE_Page)->setField('title')->setValues('Relations'));
        self::assertThat($responseSections, $this->getRequestSectionHasRecordConstraint()
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Regular Element #1', 'Regular Element #2'));
        self::assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':' . $this->recordIds['newContentIdFirst'])->setRecordField('categories')
            ->setTable(self::TABLE_Category)->setField('title')->setValues('Category A', 'Category B'));
        self::assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':' . $this->recordIds['newContentIdLast'])->setRecordField('categories')
            ->setTable(self::TABLE_Category)->setField('title')->setValues('Category B', 'Category C'));

        // @todo: reference index not clean after this test. Needs investigation.
        $this->assertCleanReferenceIndex = false;
    }
}
