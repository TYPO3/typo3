<?php

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

namespace TYPO3\CMS\Workspaces\Tests\Functional\DataHandling\ManyToMany\Publish;

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
    protected $assertionDataSetDirectory = 'typo3/sysext/workspaces/Tests/Functional/DataHandling/ManyToMany/Publish/DataSet/';

    /**
     * @var bool False as temporary hack
     */
    protected $assertCleanReferenceIndex = false;

    /**
     * @test
     */
    public function addCategoryRelation()
    {
        parent::addCategoryRelation();
        $this->actionService->publishRecord(self::TABLE_Content, self::VALUE_ContentIdFirst);
        $this->assertAssertionDataSet('addCategoryRelation');

        $response = $this->executeFrontendRequest((new InternalRequest())->withPageId(self::VALUE_PageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdFirst)->setRecordField('categories')
            ->setTable(self::TABLE_Category)->setField('title')->setValues('Category A', 'Category B', 'Category A.A'));
    }

    /**
     * @test
     */
    public function deleteCategoryRelation()
    {
        parent::deleteCategoryRelation();
        $this->actionService->publishRecord(self::TABLE_Content, self::VALUE_ContentIdFirst);
        $this->assertAssertionDataSet('deleteCategoryRelation');

        $response = $this->executeFrontendRequest((new InternalRequest())->withPageId(self::VALUE_PageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdFirst)->setRecordField('categories')
            ->setTable(self::TABLE_Category)->setField('title')->setValues('Category A'));
        self::assertThat($responseSections, $this->getRequestSectionStructureDoesNotHaveRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdFirst)->setRecordField('categories')
            ->setTable(self::TABLE_Category)->setField('title')->setValues('Category B', 'Category C', 'Category A.A'));
    }

    /**
     * @test
     */
    public function changeCategoryRelationSorting()
    {
        parent::changeCategoryRelationSorting();
        $this->actionService->publishRecord(self::TABLE_Content, self::VALUE_ContentIdFirst);
        $this->assertAssertionDataSet('changeCategoryRelationSorting');

        $response = $this->executeFrontendRequest((new InternalRequest())->withPageId(self::VALUE_PageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdFirst)->setRecordField('categories')
            ->setTable(self::TABLE_Category)->setField('title')->setValues('Category A', 'Category B'));
    }

    /**
     * @test
     */
    public function createContentAndAddRelation()
    {
        parent::createContentAndAddRelation();
        $this->actionService->publishRecord(self::TABLE_Content, $this->recordIds['newContentId']);
        $this->assertAssertionDataSet('createContentNAddRelation');

        $response = $this->executeFrontendRequest((new InternalRequest())->withPageId(self::VALUE_PageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, $this->getRequestSectionHasRecordConstraint()
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Testing #1'));
        self::assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':' . $this->recordIds['newContentId'])->setRecordField('categories')
            ->setTable(self::TABLE_Category)->setField('title')->setValues('Category B'));
    }

    /**
     * @test
     */
    public function createCategoryAndAddRelation()
    {
        parent::createCategoryAndAddRelation();
        $this->actionService->publishRecord(self::TABLE_Category, $this->recordIds['newCategoryId']);
        $this->assertAssertionDataSet('createCategoryNAddRelation');

        $response = $this->executeFrontendRequest((new InternalRequest())->withPageId(self::VALUE_PageId));
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
    public function createContentAndCreateRelation()
    {
        parent::createContentAndCreateRelation();
        $this->actionService->publishRecords([
            self::TABLE_Category => [$this->recordIds['newCategoryId']],
            self::TABLE_Content => [$this->recordIds['newContentId']],
        ]);
        $this->assertAssertionDataSet('createContentNCreateRelation');

        $response = $this->executeFrontendRequest((new InternalRequest())->withPageId(self::VALUE_PageId));
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
    public function createCategoryAndCreateRelation()
    {
        parent::createCategoryAndCreateRelation();
        $this->actionService->publishRecords([
            self::TABLE_Content => [$this->recordIds['newContentId']],
            self::TABLE_Category => [$this->recordIds['newCategoryId']],
        ]);
        $this->actionService->publishWorkspace(self::VALUE_WorkspaceId);
        $this->assertAssertionDataSet('createCategoryNCreateRelation');
    }

    /**
     * @test
     */
    public function createContentWithCategoryAndAddRelation()
    {
        parent::createContentWithCategoryAndAddRelation();
        $this->actionService->publishRecords([
            self::TABLE_Category => [$this->recordIds['newCategoryId']],
            self::TABLE_Content => [$this->recordIds['newContentId']],
        ]);
        $this->assertAssertionDataSet('createContentWCategoryNAddRelation');
    }

    /**
     * @test
     */
    public function createCategoryWithContentAndAddRelation()
    {
        parent::createCategoryWithContentAndAddRelation();
        $this->actionService->publishRecords([
            self::TABLE_Content => [$this->recordIds['newContentId']],
            self::TABLE_Category => [$this->recordIds['newCategoryId']],
        ]);
        $this->assertAssertionDataSet('createCategoryWContentNAddRelation');
    }

    /**
     * @test
     */
    public function modifyCategoryOfRelation()
    {
        parent::modifyCategoryOfRelation();
        $this->actionService->publishRecord(self::TABLE_Category, self::VALUE_CategoryIdFirst);
        $this->assertAssertionDataSet('modifyCategoryOfRelation');

        $response = $this->executeFrontendRequest((new InternalRequest())->withPageId(self::VALUE_PageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdFirst)->setRecordField('categories')
            ->setTable(self::TABLE_Category)->setField('title')->setValues('Testing #1', 'Category B'));
    }

    /**
     * @test
     */
    public function modifyContentOfRelation()
    {
        parent::modifyContentOfRelation();
        $this->actionService->publishRecord(self::TABLE_Content, self::VALUE_ContentIdFirst);
        $this->assertAssertionDataSet('modifyContentOfRelation');

        $response = $this->executeFrontendRequest((new InternalRequest())->withPageId(self::VALUE_PageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, $this->getRequestSectionHasRecordConstraint()
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Testing #1'));
    }

    /**
     * @test
     */
    public function modifyBothsOfRelation()
    {
        parent::modifyBothsOfRelation();
        $this->actionService->publishRecords([
            self::TABLE_Content => [self::VALUE_ContentIdFirst],
            self::TABLE_Category => [self::VALUE_CategoryIdFirst],
        ]);
        $this->assertAssertionDataSet('modifyBothsOfRelation');

        $response = $this->executeFrontendRequest((new InternalRequest())->withPageId(self::VALUE_PageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdFirst)->setRecordField('categories')
            ->setTable(self::TABLE_Category)->setField('title')->setValues('Testing #1', 'Category B'));
        self::assertThat($responseSections, $this->getRequestSectionHasRecordConstraint()
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Testing #1'));
    }

    /**
     * @test
     */
    public function deleteContentOfRelation()
    {
        parent::deleteContentOfRelation();
        $this->actionService->publishRecord(self::TABLE_Content, self::VALUE_ContentIdLast);
        $this->assertAssertionDataSet('deleteContentOfRelation');

        $response = $this->executeFrontendRequest((new InternalRequest())->withPageId(self::VALUE_PageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, $this->getRequestSectionDoesNotHaveRecordConstraint()
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Testing #1'));
    }

    /**
     * @test
     */
    public function deleteCategoryOfRelation()
    {
        parent::deleteCategoryOfRelation();
        $this->actionService->publishRecord(self::TABLE_Category, self::VALUE_CategoryIdFirst);
        $this->assertAssertionDataSet('deleteCategoryOfRelation');

        $response = $this->executeFrontendRequest((new InternalRequest())->withPageId(self::VALUE_PageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, $this->getRequestSectionStructureDoesNotHaveRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdFirst)->setRecordField('categories')
            ->setTable(self::TABLE_Category)->setField('title')->setValues('Category A'));
    }

    /**
     * @test
     */
    public function copyContentOfRelation()
    {
        parent::copyContentOfRelation();
        $this->actionService->publishRecord(self::TABLE_Content, $this->recordIds['newContentId']);
        $this->assertAssertionDataSet('copyContentOfRelation');

        $response = $this->executeFrontendRequest((new InternalRequest())->withPageId(self::VALUE_PageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':' . $this->recordIds['newContentId'])->setRecordField('categories')
            ->setTable(self::TABLE_Category)->setField('title')->setValues('Category B', 'Category C'));
    }

    /**
     * @test
     */
    public function copyCategoryOfRelation()
    {
        parent::copyCategoryOfRelation();
        $this->actionService->publishRecord(self::TABLE_Category, $this->recordIds['newCategoryId']);
        $this->assertAssertionDataSet('copyCategoryOfRelation');

        $response = $this->executeFrontendRequest((new InternalRequest())->withPageId(self::VALUE_PageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdFirst)->setRecordField('categories')
            ->setTable(self::TABLE_Category)->setField('title')->setValues('Category A', 'Category A (copy 1)'));
    }

    /**
     * @test
     */
    public function localizeContentOfRelation()
    {
        parent::localizeContentOfRelation();
        $this->actionService->publishRecord(self::TABLE_Content, $this->recordIds['localizedContentId']);
        $this->assertAssertionDataSet('localizeContentOfRelation');

        $response = $this->executeFrontendRequest((new InternalRequest())->withPageId(self::VALUE_PageId)->withLanguageId(self::VALUE_LanguageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdLast)->setRecordField('categories')
            ->setTable(self::TABLE_Category)->setField('title')->setValues('Category B', 'Category C'));
    }

    /**
     * @test
     */
    public function localizeCategoryOfRelation()
    {
        // Create and publish translated page first
        $translatedPageResult = $this->actionService->copyRecordToLanguage(self::TABLE_Page, self::VALUE_PageId, self::VALUE_LanguageId);
        $this->actionService->publishRecord(self::TABLE_Page, $translatedPageResult[self::TABLE_Page][self::VALUE_PageId]);
        parent::localizeCategoryOfRelation();
        $this->actionService->publishRecord(self::TABLE_Category, $this->recordIds['localizedCategoryId']);
        $this->assertAssertionDataSet('localizeCategoryOfRelation');

        $response = $this->executeFrontendRequest((new InternalRequest())->withPageId(self::VALUE_PageId)->withLanguageId(self::VALUE_LanguageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdFirst)->setRecordField('categories')
            ->setTable(self::TABLE_Category)->setField('title')->setValues('[Translate to Dansk:] Category A', 'Category B'));
    }

    /**
     * @test
     */
    public function moveContentOfRelationToDifferentPage()
    {
        parent::moveContentOfRelationToDifferentPage();
        $this->actionService->publishRecord(self::TABLE_Content, self::VALUE_ContentIdLast);
        $this->assertAssertionDataSet('moveContentOfRelationToDifferentPage');

        $response = $this->executeFrontendRequest((new InternalRequest())->withPageId(self::VALUE_PageIdTarget));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdLast)->setRecordField('categories')
            ->setTable(self::TABLE_Category)->setField('title')->setValues('Category B', 'Category C'));
    }

    /**
     * @test
     */
    public function copyPage()
    {
        parent::copyPage();
        $this->actionService->publishRecords([
            self::TABLE_Page => [$this->recordIds['newPageId']],
            self::TABLE_Content => [$this->recordIds['newContentIdFirst'], $this->recordIds['newContentIdLast']],
        ]);
        $this->assertAssertionDataSet('copyPage');

        $response = $this->executeFrontendRequest((new InternalRequest())->withPageId($this->recordIds['newPageId']));
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
    }
}
