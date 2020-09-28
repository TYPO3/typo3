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

namespace TYPO3\CMS\Workspaces\Tests\Functional\DataHandling\Regular\Publish;

use TYPO3\CMS\Workspaces\Tests\Functional\DataHandling\Regular\AbstractActionTestCase;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;

/**
 * Functional test for the DataHandler
 */
class ActionTest extends AbstractActionTestCase
{
    /**
     * @var string
     */
    protected $assertionDataSetDirectory = 'typo3/sysext/workspaces/Tests/Functional/DataHandling/Regular/Publish/DataSet/';

    /**
     * @var bool False as temporary hack
     */
    protected $assertCleanReferenceIndex = false;

    /**
     * @test
     * See DataSet/createContentRecords.csv
     */
    public function createContents()
    {
        parent::createContents();
        $this->actionService->publishRecords(
            [
                self::TABLE_Content => [$this->recordIds['newContentIdFirst'], $this->recordIds['newContentIdLast']],
            ]
        );
        $this->assertAssertionDataSet('createContents');

        $responseSections = $this->getFrontendResponse(self::VALUE_PageId, 0)->getResponseSections();
        self::assertThat($responseSections, $this->getRequestSectionHasRecordConstraint()
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Testing #1', 'Testing #2'));
    }

    /**
     * @test
     */
    public function createContentAndCopyContent()
    {
        parent::createContentAndCopyContent();
        $this->actionService->publishRecord(self::TABLE_Content, $this->recordIds['newContentId']);
        $this->actionService->publishRecord(self::TABLE_Content, $this->recordIds['copiedContentId']);
        $this->assertAssertionDataSet('createContentAndCopyContent');

        $responseSections = $this->getFrontendResponse(self::VALUE_PageId, 0)->getResponseSections();
        self::assertThat($responseSections, $this->getRequestSectionHasRecordConstraint()
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Testing #1', 'Testing #1 (copy 1)'));
    }

    /**
     * @test
     * See DataSet/modifyContentRecord.csv
     */
    public function modifyContent()
    {
        parent::modifyContent();
        $this->actionService->publishRecord(self::TABLE_Content, self::VALUE_ContentIdSecond);
        $this->assertAssertionDataSet('modifyContent');

        $responseSections = $this->getFrontendResponse(self::VALUE_PageId, 0)->getResponseSections();
        self::assertThat($responseSections, $this->getRequestSectionHasRecordConstraint()
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Testing #1'));
    }

    /**
     * @test
     */
    public function hideContent()
    {
        parent::hideContent();
        $this->actionService->publishRecord(self::TABLE_Content, self::VALUE_ContentIdSecond);
        $this->assertAssertionDataSet('hideContent');

        $responseSections = $this->getFrontendResponse(self::VALUE_PageId, 0, self::VALUE_BackendUserId)->getResponseSections();
        self::assertThat($responseSections, $this->getRequestSectionHasRecordConstraint()
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Regular Element #2'));
    }

    /**
     * @test
     */
    public function hideContentAndMoveToDifferentPage()
    {
        parent::hideContent();
        parent::moveContentToDifferentPage();
        $this->actionService->publishRecord(self::TABLE_Content, self::VALUE_ContentIdSecond);
        $this->assertAssertionDataSet('hideContentAndMoveToDifferentPage');

        $responseSectionsSource = $this->getFrontendResponse(self::VALUE_PageId, 0, self::VALUE_BackendUserId)->getResponseSections();
        self::assertThat($responseSectionsSource, $this->getRequestSectionHasRecordConstraint()
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Regular Element #1'));
        self::assertThat($responseSectionsSource, $this->getRequestSectionDoesNotHaveRecordConstraint()
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Regular Element #2'));
        $responseSectionsTarget = $this->getFrontendResponse(self::VALUE_PageIdTarget, 0, self::VALUE_BackendUserId)->getResponseSections();
        self::assertThat($responseSectionsTarget, $this->getRequestSectionHasRecordConstraint()
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Regular Element #2'));
    }

    /**
     * @test
     */
    public function deleteContent()
    {
        parent::deleteContent();
        $this->actionService->publishRecord(self::TABLE_Content, self::VALUE_ContentIdSecond);
        $this->assertAssertionDataSet('deleteContent');

        $responseSections = $this->getFrontendResponse(self::VALUE_PageId, 0)->getResponseSections();
        self::assertThat($responseSections, $this->getRequestSectionHasRecordConstraint()
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Regular Element #1'));
        self::assertThat($responseSections, $this->getRequestSectionDoesNotHaveRecordConstraint()
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Regular Element #2'));
    }

    /**
     * @test
     */
    public function deleteLocalizedContentAndDeleteContent()
    {
        // this test will not rely on a translated page, because it only tests the act of publishing.
        // The actual content of frontend response does not matter much, and it would increase the scope
        // of the test, when a translated page is also published here.
        parent::deleteLocalizedContentAndDeleteContent();
        $this->actionService->publishRecords(
            [
                self::TABLE_Content => [self::VALUE_ContentIdThird, self::VALUE_ContentIdThirdLocalized],
            ]
        );
        $this->assertAssertionDataSet('deleteLocalizedContentNDeleteContent');

        $responseSections = $this->getFrontendResponse(self::VALUE_PageId, self::VALUE_LanguageId)->getResponseSections();
        self::assertThat($responseSections, $this->getRequestSectionDoesNotHaveRecordConstraint()
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Regular Element #3', '[Translate to Dansk:] Regular Element #3'));
    }

    /**
     * @test
     */
    public function copyContent()
    {
        parent::copyContent();
        $this->actionService->publishRecord(self::TABLE_Content, $this->recordIds['copiedContentId']);
        $this->assertAssertionDataSet('copyContent');

        $responseSections = $this->getFrontendResponse(self::VALUE_PageId, 0)->getResponseSections();
        self::assertThat($responseSections, $this->getRequestSectionHasRecordConstraint()
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Regular Element #2 (copy 1)'));
    }

    /**
     * @test
     */
    public function copyContentToLanguage()
    {
        // Create and publish translated page first
        $translatedPageResult = $this->actionService->copyRecordToLanguage(self::TABLE_Page, self::VALUE_PageId, self::VALUE_LanguageId);
        $this->actionService->publishRecord(self::TABLE_Page, $translatedPageResult[self::TABLE_Page][self::VALUE_PageId]);
        parent::copyContentToLanguage();
        $this->actionService->publishRecord(self::TABLE_Content, $this->recordIds['localizedContentId']);
        $this->assertAssertionDataSet('copyContentToLanguage');

        // Set up "dk" to not have overlays
        $languageConfiguration = $this->siteLanguageConfiguration;
        $languageConfiguration[self::VALUE_LanguageId]['fallbackType'] = 'free';
        $this->setUpFrontendSite(1, $languageConfiguration);
        $responseSections = $this->getFrontendResponse(self::VALUE_PageId, self::VALUE_LanguageId)->getResponseSections();
        self::assertThat($responseSections, $this->getRequestSectionHasRecordConstraint()
            ->setTable(self::TABLE_Content)->setField('header')->setValues('[Translate to Dansk:] Regular Element #3', '[Translate to Dansk:] Regular Element #2'));
    }

    /**
     * @test
     */
    public function copyContentToLanguageFromNonDefaultLanguage()
    {
        // Create and publish translated page first
        $translatedPageResult = $this->actionService->copyRecordToLanguage(self::TABLE_Page, self::VALUE_PageId, self::VALUE_LanguageIdSecond);
        $this->actionService->publishRecord(self::TABLE_Page, $translatedPageResult[self::TABLE_Page][self::VALUE_PageId]);
        parent::copyContentToLanguageFromNonDefaultLanguage();
        $this->actionService->publishRecord(self::TABLE_Content, $this->recordIds['localizedContentId']);
        $this->assertAssertionDataSet('copyContentToLanguageFromNonDefaultLanguage');

        // Set up "dk" to not have overlays
        $languageConfiguration = $this->siteLanguageConfiguration;
        $languageConfiguration[self::VALUE_LanguageIdSecond]['fallbackType'] = 'free';
        $this->setUpFrontendSite(1, $languageConfiguration);
        $responseSections = $this->getFrontendResponse(self::VALUE_PageId, self::VALUE_LanguageIdSecond)->getResponseSections();
        self::assertThat($responseSections, $this->getRequestSectionHasRecordConstraint()
            ->setTable(self::TABLE_Content)->setField('header')->setValues('[Translate to Deutsch:] [Translate to Dansk:] Regular Element #3'));
    }

    /**
     * @test
     */
    public function localizeContent()
    {
        // Create and publish translated page first
        $translatedPageResult = $this->actionService->copyRecordToLanguage(self::TABLE_Page, self::VALUE_PageId, self::VALUE_LanguageId);
        $this->actionService->publishRecord(self::TABLE_Page, $translatedPageResult[self::TABLE_Page][self::VALUE_PageId]);
        parent::localizeContent();
        $this->actionService->publishRecord(self::TABLE_Content, $this->recordIds['localizedContentId']);
        $this->assertAssertionDataSet('localizeContent');

        $responseSections = $this->getFrontendResponse(self::VALUE_PageId, self::VALUE_LanguageId)->getResponseSections();
        self::assertThat($responseSections, $this->getRequestSectionHasRecordConstraint()
            ->setTable(self::TABLE_Content)->setField('header')->setValues('[Translate to Dansk:] Regular Element #1', '[Translate to Dansk:] Regular Element #2'));
    }

    /**
     * @test
     */
    public function localizeContentFromNonDefaultLanguage()
    {
        // Create and publish translated page first
        $translatedPageResult = $this->actionService->copyRecordToLanguage(self::TABLE_Page, self::VALUE_PageId, self::VALUE_LanguageIdSecond);
        $this->actionService->publishRecord(self::TABLE_Page, $translatedPageResult[self::TABLE_Page][self::VALUE_PageId]);
        parent::localizeContentFromNonDefaultLanguage();
        $this->actionService->publishRecord(self::TABLE_Content, $this->recordIds['localizedContentId']);
        $this->assertAssertionDataSet('localizeContentFromNonDefaultLanguage');

        $responseSections = $this->getFrontendResponse(self::VALUE_PageId, self::VALUE_LanguageIdSecond)->getResponseSections();
        self::assertThat($responseSections, $this->getRequestSectionHasRecordConstraint()
            ->setTable(self::TABLE_Content)->setField('header')->setValues('[Translate to Deutsch:] [Translate to Dansk:] Regular Element #1', '[Translate to Deutsch:] [Translate to Dansk:] Regular Element #3'));
    }

    /**
     * @test
     */
    public function changeContentSorting()
    {
        parent::changeContentSorting();
        $this->actionService->publishRecord(self::TABLE_Content, self::VALUE_ContentIdFirst);
        $this->assertAssertionDataSet('changeContentSorting');

        $responseSections = $this->getFrontendResponse(self::VALUE_PageId, 0)->getResponseSections();
        self::assertThat($responseSections, $this->getRequestSectionHasRecordConstraint()
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Regular Element #1', 'Regular Element #2'));
    }

    /**
     * @test
     */
    public function changeContentSortingAfterSelf()
    {
        parent::changeContentSortingAfterSelf();
        $this->actionService->publishRecord(self::TABLE_Content, self::VALUE_ContentIdFirst);
        $this->assertAssertionDataSet('changeContentSortingAfterSelf');

        $responseSections = $this->getFrontendResponse(self::VALUE_PageId, 0, self::VALUE_BackendUserId, self::VALUE_WorkspaceId)->getResponseSections();
        self::assertThat($responseSections, $this->getRequestSectionHasRecordConstraint()
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Regular Element #1', 'Regular Element #2'));
    }

    /**
     * @test
     */
    public function moveContentToDifferentPage()
    {
        parent::moveContentToDifferentPage();
        $this->actionService->publishRecord(self::TABLE_Content, self::VALUE_ContentIdSecond);
        $this->assertAssertionDataSet('moveContentToDifferentPage');

        $responseSectionsSource = $this->getFrontendResponse(self::VALUE_PageId, 0)->getResponseSections();
        self::assertThat($responseSectionsSource, $this->getRequestSectionHasRecordConstraint()
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Regular Element #1'));
        $responseSectionsTarget = $this->getFrontendResponse(self::VALUE_PageIdTarget, 0)->getResponseSections();
        self::assertThat($responseSectionsTarget, $this->getRequestSectionHasRecordConstraint()
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Regular Element #2'));
    }

    /**
     * @test
     */
    public function moveContentToDifferentPageAndChangeSorting()
    {
        parent::moveContentToDifferentPageAndChangeSorting();
        $this->actionService->publishRecords([
            self::TABLE_Content => [self::VALUE_ContentIdFirst, self::VALUE_ContentIdSecond],
        ]);
        $this->assertAssertionDataSet('moveContentToDifferentPageNChangeSorting');

        $responseSections = $this->getFrontendResponse(self::VALUE_PageIdTarget, 0)->getResponseSections();
        self::assertThat($responseSections, $this->getRequestSectionHasRecordConstraint()
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Regular Element #1', 'Regular Element #2'));
    }

    /**
     * @test
     */
    public function moveContentToDifferentPageAndHide()
    {
        parent::moveContentToDifferentPageAndHide();
        $this->actionService->publishRecord(self::TABLE_Content, self::VALUE_ContentIdSecond);
        $this->assertAssertionDataSet('moveContentToDifferentPageAndHide');

        $responseSections = $this->getFrontendResponse(self::VALUE_PageIdTarget, 0, self::VALUE_BackendUserId, self::VALUE_WorkspaceId)->getResponseSections();
        self::assertThat($responseSections, $this->getRequestSectionDoesNotHaveRecordConstraint()
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Regular Element #2'));
    }

    /**
     * Page records
     */

    /**
     * @test
     */
    public function createPage()
    {
        parent::createPage();
        $this->actionService->publishRecord(self::TABLE_Page, $this->recordIds['newPageId']);
        $this->assertAssertionDataSet('createPage');

        $responseSections = $this->getFrontendResponse($this->recordIds['newPageId'], 0)->getResponseSections();
        self::assertThat($responseSections, $this->getRequestSectionHasRecordConstraint()
            ->setTable(self::TABLE_Page)->setField('title')->setValues('Testing #1'));
    }

    /**
     * @test
     */
    public function createPageAndSubPageAndSubPageContent()
    {
        parent::createPageAndSubPageAndSubPageContent();
        $this->actionService->publishRecord(self::TABLE_Page, $this->recordIds['newPageId']);
        $this->assertAssertionDataSet('createPageAndSubPageAndSubPageContent');

        // Sub page is not published together with parent page
        $responseSections = $this->getFrontendResponse($this->recordIds['newSubPageId'], 0, self::VALUE_BackendUserId, self::VALUE_WorkspaceId)->getResponseSections();
        self::assertThat($responseSections, $this->getRequestSectionHasRecordConstraint()
            ->setTable(self::TABLE_Page)->setField('title')->setValues('Testing #1 #1'));
    }

    /**
     * @test
     */
    public function modifyPage()
    {
        parent::modifyPage();
        $this->actionService->publishRecord(self::TABLE_Page, self::VALUE_PageId);
        $this->assertAssertionDataSet('modifyPage');

        $responseSections = $this->getFrontendResponse(self::VALUE_PageId, 0)->getResponseSections();
        self::assertThat($responseSections, $this->getRequestSectionHasRecordConstraint()
            ->setTable(self::TABLE_Page)->setField('title')->setValues('Testing #1'));
    }

    /**
     * @test
     */
    public function deletePage()
    {
        parent::deletePage();
        $this->actionService->publishRecord(self::TABLE_Page, self::VALUE_PageId);
        $this->assertAssertionDataSet('deletePage');

        $response = $this->executeFrontendRequest(
            (new InternalRequest())->withPageId(self::VALUE_PageId)
        );
        self::assertEquals(404, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function deleteContentAndPage()
    {
        parent::deleteContentAndPage();
        $this->actionService->publishRecord(self::TABLE_Page, self::VALUE_PageId);
        $this->assertAssertionDataSet('deleteContentAndPage');

        $response = $this->executeFrontendRequest(
            (new InternalRequest())->withPageId(self::VALUE_PageId)
        );
        self::assertEquals(404, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function localizeNestedPagesAndContents()
    {
        parent::localizeNestedPagesAndContents();
        // Will publish only the page translation, not it's content elements
        $this->actionService->publishRecord(self::TABLE_Page, $this->recordIds['localizedParentPageId']);
        $this->assertAssertionDataSet('localizeNestedPagesAndContents');
    }

    /**
     * @test
     */
    public function copyPage()
    {
        parent::copyPage();
        $this->actionService->publishRecords(
            [
                self::TABLE_Page => [$this->recordIds['newPageId']],
                self::TABLE_Content => [$this->recordIds['newContentIdFirst'], $this->recordIds['newContentIdLast']],
            ]
        );
        $this->assertAssertionDataSet('copyPage');

        $responseSections = $this->getFrontendResponse($this->recordIds['newPageId'], 0)->getResponseSections();
        self::assertThat($responseSections, $this->getRequestSectionHasRecordConstraint()
            ->setTable(self::TABLE_Page)->setField('title')->setValues('Relations'));
    }

    /**
     * @test
     */
    public function copyPageFreeMode()
    {
        parent::copyPageFreeMode();
        $this->actionService->publishRecords(
            [
                self::TABLE_Page => [$this->recordIds['newPageId']],
                self::TABLE_Content => [$this->recordIds['newContentIdTenth'], $this->recordIds['newContentIdTenthLocalized'],  $this->recordIds['newContentIdTenthLocalized2']],
            ]
        );
        $this->assertAssertionDataSet('copyPageFreeMode');

        $responseSections = $this->getFrontendResponse($this->recordIds['newPageId'], 0)->getResponseSections();
        self::assertThat($responseSections, $this->getRequestSectionHasRecordConstraint()
            ->setTable(self::TABLE_Page)->setField('title')->setValues('Target'));
        self::assertThat($responseSections, $this->getRequestSectionHasRecordConstraint()
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Regular Element #10'));
    }

    /**
     * @test
     */
    public function localizePage()
    {
        parent::localizePage();
        $this->actionService->publishRecord(self::TABLE_Page, $this->recordIds['localizedPageId']);
        $this->assertAssertionDataSet('localizePage');

        $responseSections = $this->getFrontendResponse(self::VALUE_PageId, self::VALUE_LanguageId)->getResponseSections();
        self::assertThat($responseSections, $this->getRequestSectionHasRecordConstraint()
            ->setTable(self::TABLE_Page)->setField('title')->setValues('[Translate to Dansk:] Relations'));
    }

    /**
     * @test
     */
    public function createPageAndChangePageSorting()
    {
        parent::createPageAndChangePageSorting();
        $this->actionService->publishRecord(self::TABLE_Page, $this->recordIds['newPageId']);
        $this->assertAssertionDataSet('createPageAndChangePageSorting');
    }

    /**
     * @test
     */
    public function createPageAndMoveCreatedPage()
    {
        parent::createPageAndMoveCreatedPage();
        $this->actionService->publishRecord(self::TABLE_Page, $this->recordIds['newPageId']);
        $this->assertAssertionDataSet('createPageAndMoveCreatedPage');
    }

    /**
     * @test
     */
    public function changePageSorting()
    {
        parent::changePageSorting();
        $this->actionService->publishRecord(self::TABLE_Page, self::VALUE_PageId);
        $this->assertAssertionDataSet('changePageSorting');

        $responseSections = $this->getFrontendResponse(self::VALUE_PageId, 0)->getResponseSections();
        self::assertThat($responseSections, $this->getRequestSectionHasRecordConstraint()
            ->setTable(self::TABLE_Page)->setField('title')->setValues('Relations'));
        self::assertThat($responseSections, $this->getRequestSectionHasRecordConstraint()
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Regular Element #1', 'Regular Element #2'));
    }

    /**
     * @test
     */
    public function changePageSortingAfterSelf()
    {
        parent::changePageSortingAfterSelf();
        $this->actionService->publishRecord(self::TABLE_Page, self::VALUE_PageId);
        $this->assertAssertionDataSet('changePageSortingAfterSelf');

        $responseSections = $this->getFrontendResponse(self::VALUE_PageId, 0)->getResponseSections();
        self::assertThat($responseSections, $this->getRequestSectionHasRecordConstraint()
            ->setTable(self::TABLE_Page)->setField('title')->setValues('Relations'));
        self::assertThat($responseSections, $this->getRequestSectionHasRecordConstraint()
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Regular Element #1', 'Regular Element #2'));
    }

    /**
     * @test
     */
    public function movePageToDifferentPage()
    {
        parent::movePageToDifferentPage();
        $this->actionService->publishRecord(self::TABLE_Page, self::VALUE_PageId);
        $this->assertAssertionDataSet('movePageToDifferentPage');

        $responseSections = $this->getFrontendResponse(self::VALUE_PageId, 0)->getResponseSections();
        self::assertThat($responseSections, $this->getRequestSectionHasRecordConstraint()
            ->setTable(self::TABLE_Page)->setField('title')->setValues('Relations'));
        self::assertThat($responseSections, $this->getRequestSectionHasRecordConstraint()
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Regular Element #1', 'Regular Element #2'));
    }

    /**
     * @test
     */
    public function movePageToDifferentPageAndChangeSorting()
    {
        parent::movePageToDifferentPageAndChangeSorting();
        $this->actionService->publishRecords([
            self::TABLE_Page => [self::VALUE_PageId, self::VALUE_PageIdTarget],
        ]);
        $this->assertAssertionDataSet('movePageToDifferentPageNChangeSorting');

        $responseSectionsPage = $this->getFrontendResponse(self::VALUE_PageId, 0)->getResponseSections();
        self::assertThat($responseSectionsPage, $this->getRequestSectionHasRecordConstraint()
            ->setTable(self::TABLE_Page)->setField('title')->setValues('Relations'));
        self::assertThat($responseSectionsPage, $this->getRequestSectionHasRecordConstraint()
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Regular Element #1', 'Regular Element #2'));
        $responseSectionsWebsite = $this->getFrontendResponse(self::VALUE_PageIdWebsite, 0)->getResponseSections();
        self::assertThat($responseSectionsWebsite, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Page . ':' . self::VALUE_PageIdWebsite)->setRecordField('__pages')
            ->setTable(self::TABLE_Page)->setField('title')->setValues('Target', 'Relations', 'DataHandlerTest'));
    }

    /**
     * @test
     * @see https://forge.typo3.org/issues/33104
     * @see https://forge.typo3.org/issues/55573
     */
    public function movePageToDifferentPageAndCreatePageAfterMovedPage()
    {
        parent::movePageToDifferentPageAndCreatePageAfterMovedPage();
        $this->actionService->publishRecords([
            self::TABLE_Page => [self::VALUE_PageIdTarget, $this->recordIds['newPageId']],
        ]);
        $this->assertAssertionDataSet('movePageToDifferentPageNCreatePageAfterMovedPage');

        $responseSections = $this->getFrontendResponse(self::VALUE_PageIdWebsite, 0)->getResponseSections();
        self::assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Page . ':' . self::VALUE_PageIdWebsite)->setRecordField('__pages')
            ->setTable(self::TABLE_Page)->setField('title')->setValues('Target', 'Testing #1', 'DataHandlerTest'));
    }

    /**
     * @test
     * @group not-postgres
     * @group not-mssql
     * @todo Analyze PostgreSQL issues further, which is a generic issue
     */
    public function changeContentSortingAndCopyDraftPage()
    {
        parent::changeContentSortingAndCopyDraftPage();
        $this->actionService->publishRecord(self::TABLE_Content, self::VALUE_ContentIdFirst);
        $this->assertAssertionDataSet('changeContentSortingAndCopyDraftPage');
    }

    /**
     * @test
     */
    public function createContentAndCopyDraftPage()
    {
        parent::createContentAndCopyDraftPage();
        $this->actionService->publishRecords([
            self::TABLE_Content => [$this->recordIds['newContentId']],
            self::TABLE_Page => [$this->recordIds['copiedPageId']]
        ]);
        $this->assertAssertionDataSet('createContentAndCopyDraftPage');

        $responseSectionsDraft = $this->getFrontendResponse($this->recordIds['copiedPageId'], 0, self::VALUE_BackendUserId, self::VALUE_WorkspaceId)->getResponseSections();
        self::assertThat($responseSectionsDraft, $this->getRequestSectionHasRecordConstraint()
            ->setTable(static::TABLE_Content)->setField('header')->setValues('Testing #1'));
    }

    /**
     * @test
     */
    public function createContentAndLocalize()
    {
        parent::createContentAndLocalize();
        $this->actionService->publishRecords([
            self::TABLE_Content => [$this->recordIds['newContentId']],
        ]);
        $this->assertAssertionDataSet('createContentAndLocalize');

        $responseSections = $this->getFrontendResponse(self::VALUE_PageId, self::VALUE_LanguageId, self::VALUE_BackendUserId, self::VALUE_WorkspaceId)->getResponseSections();
        self::assertThat($responseSections, $this->getRequestSectionHasRecordConstraint()
            ->setTable(self::TABLE_Content)->setField('header')->setValues('[Translate to Dansk:] Testing #1'));
    }

    /**
     * @test
     */
    public function createPageAndCopyDraftParentPage()
    {
        parent::createPageAndCopyDraftParentPage();
        $this->actionService->publishRecords([
            self::TABLE_Page => [$this->recordIds['newPageId'], $this->recordIds['copiedPageId']]
        ]);
        $this->assertAssertionDataSet('createPageAndCopyDraftParentPage');
    }

    /**
     * @test
     */
    public function createPlaceholdersAndDeleteDraftParentPage()
    {
        parent::createPlaceholdersAndDeleteDraftParentPage();
        $this->actionService->publishRecord(self::TABLE_Page, self::VALUE_ParentPageId);
        $this->assertAssertionDataSet('createPlaceholdersAndDeleteDraftParentPage');
    }
}
