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

namespace TYPO3\CMS\Workspaces\Tests\Functional\DataHandling\Regular\Discard;

use TYPO3\CMS\Workspaces\Tests\Functional\DataHandling\Regular\AbstractActionTestCase;

/**
 * Functional test for the DataHandler
 */
class ActionTest extends AbstractActionTestCase
{
    /**
     * @var string
     */
    protected $assertionDataSetDirectory = 'typo3/sysext/workspaces/Tests/Functional/DataHandling/Regular/Discard/DataSet/';

    /**
     * @test
     */
    public function verifyCleanReferenceIndex()
    {
        // The test verifies the imported data set has a clean reference index by the check in tearDown()
        self::assertTrue(true);
    }

    /**
     * @test
     */
    public function createContents()
    {
        parent::createContents();
        $this->actionService->clearWorkspaceRecords(
            [
                self::TABLE_Content => [$this->recordIds['newContentIdFirst'], $this->recordIds['newContentIdLast']],
            ]
        );
        $this->assertAssertionDataSet('createContents');
    }

    /**
     * @test
     */
    public function createContentAndCopyContent()
    {
        parent::createContentAndCopyContent();
        // discard copied content
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, $this->recordIds['versionedCopiedContentId']);
        $this->assertAssertionDataSet('createContentAndCopyContent');
    }

    /**
     * @test
     */
    public function createContentAndLocalize()
    {
        parent::createContentAndLocalize();
        // discard default language content
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, $this->recordIds['newContentId']);
        $this->assertAssertionDataSet('createContentAndLocalize');
    }

    /**
     * @test
     */
    public function modifyContent()
    {
        parent::modifyContent();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, self::VALUE_ContentIdSecond);
        $this->assertAssertionDataSet('modifyContent');
    }

    /**
     * @test
     */
    public function hideContent()
    {
        parent::hideContent();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, self::VALUE_ContentIdSecond);
        $this->assertAssertionDataSet('hideContent');
    }

    /**
     * @test
     */
    public function hideContentAndMoveToDifferentPage()
    {
        parent::hideContent();
        parent::moveContentToDifferentPage();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, self::VALUE_ContentIdSecond);
        $this->assertAssertionDataSet('hideContentAndMoveToDifferentPage');
    }

    /**
     * @test
     */
    public function deleteContent()
    {
        parent::deleteContent();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, self::VALUE_ContentIdSecond);
        $this->assertAssertionDataSet('deleteContent');
    }

    /**
     * @test
     */
    public function deleteLocalizedContentAndDeleteContent()
    {
        // Create translated page first
        $this->actionService->copyRecordToLanguage(self::TABLE_Page, self::VALUE_PageId, self::VALUE_LanguageId);
        parent::deleteLocalizedContentAndDeleteContent();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, self::VALUE_ContentIdThird);
        $this->assertAssertionDataSet('deleteLocalizedContentNDeleteContent');
    }

    /**
     * @test
     */
    public function copyContent()
    {
        parent::copyContent();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, $this->recordIds['copiedContentId']);
        $this->assertAssertionDataSet('copyContent');
    }

    /**
     * @test
     */
    public function copyContentToLanguage()
    {
        // Create translated page first
        $this->actionService->copyRecordToLanguage(self::TABLE_Page, self::VALUE_PageId, self::VALUE_LanguageId);
        parent::copyContentToLanguage();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, $this->recordIds['localizedContentId']);
        $this->assertAssertionDataSet('copyContentToLanguage');
    }

    /**
     * @test
     */
    public function copyContentToLanguageFromNonDefaultLanguage()
    {
        // Create translated page first
        $this->actionService->copyRecordToLanguage(self::TABLE_Page, self::VALUE_PageId, self::VALUE_LanguageId);
        $this->actionService->copyRecordToLanguage(self::TABLE_Page, self::VALUE_PageId, self::VALUE_LanguageIdSecond);
        parent::copyContentToLanguageFromNonDefaultLanguage();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, $this->recordIds['localizedContentId']);
        $this->assertAssertionDataSet('copyContentToLanguageFromNonDefaultLanguage');
    }

    /**
     * @test
     */
    public function localizeContent()
    {
        // Create translated page first
        $this->actionService->copyRecordToLanguage(self::TABLE_Page, self::VALUE_PageId, self::VALUE_LanguageId);
        parent::localizeContent();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, $this->recordIds['localizedContentId']);
        $this->assertAssertionDataSet('localizeContent');
    }

    /**
     * @test
     */
    public function localizeContentAfterMovedContent()
    {
        parent::localizeContentAfterMovedContent();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, $this->recordIds['localizedContentId']);
        $this->assertAssertionDataSet('localizeContentAfterMovedContent');
    }

    /**
     * @test
     */
    public function localizeContentAfterMovedInLiveContent()
    {
        parent::localizeContentAfterMovedInLiveContent();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, $this->recordIds['localizedContentId']);
        $this->assertAssertionDataSet('localizeContentAfterMovedInLiveContent');
    }

    /**
     * @test
     */
    public function localizeContentFromNonDefaultLanguage()
    {
        // Create translated page first
        $this->actionService->copyRecordToLanguage(self::TABLE_Page, self::VALUE_PageId, self::VALUE_LanguageId);
        $this->actionService->copyRecordToLanguage(self::TABLE_Page, self::VALUE_PageId, self::VALUE_LanguageIdSecond);
        parent::localizeContentFromNonDefaultLanguage();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, $this->recordIds['localizedContentId']);
        $this->assertAssertionDataSet('localizeContentFromNonDefaultLanguage');
    }

    /**
     * @test
     */
    public function changeContentSorting()
    {
        parent::changeContentSorting();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, self::VALUE_ContentIdFirst);
        $this->assertAssertionDataSet('changeContentSorting');
    }

    /**
     * @test
     */
    public function changeContentSortingAfterSelf()
    {
        parent::changeContentSortingAfterSelf();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, self::VALUE_ContentIdFirst);
        $this->assertAssertionDataSet('changeContentSortingAfterSelf');
    }

    /**
     * @test
     */
    public function changeContentSortingAndDeleteMovedRecord()
    {
        parent::changeContentSortingAndDeleteMovedRecord();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, self::VALUE_ContentIdFirst);
        // Note the deleted=1 records are NOT discarded. This is ok since deleted=1 means "not seen in backend",
        // so it is also ignored by the discard operation.
        $this->assertAssertionDataSet('changeContentSortingNDeleteMovedRecord');
    }

    /**
     * @test
     */
    public function changeContentSortingAndDeleteLiveRecord()
    {
        parent::changeContentSortingAndDeleteLiveRecord();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, self::VALUE_ContentIdFirst);
        // Note the deleted=1 records are NOT discarded. This is ok since deleted=1 means "not seen in backend",
        // so it is also ignored by the discard operation.
        $this->assertAssertionDataSet('changeContentSortingNDeleteLiveRecord');
    }

    /**
     * @test
     */
    public function moveContentToDifferentPage()
    {
        parent::moveContentToDifferentPage();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, self::VALUE_ContentIdSecond);
        $this->assertAssertionDataSet('moveContentToDifferentPage');
    }

    /**
     * @test
     */
    public function moveContentToDifferentPageAndChangeSorting()
    {
        parent::moveContentToDifferentPageAndChangeSorting();
        $this->actionService->clearWorkspaceRecords([
            self::TABLE_Content => [self::VALUE_ContentIdFirst, self::VALUE_ContentIdSecond]
        ]);
        $this->assertAssertionDataSet('moveContentToDifferentPageNChangeSorting');
    }

    /**
     * @test
     */
    public function moveContentToDifferentPageAndHide()
    {
        parent::moveContentToDifferentPageAndHide();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, self::VALUE_ContentIdSecond);
        $this->assertAssertionDataSet('moveContentToDifferentPageAndHide');
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
        $this->actionService->clearWorkspaceRecord(self::TABLE_Page, $this->recordIds['newPageId']);
        $this->assertAssertionDataSet('createPage');
    }

    /**
     * @test
     */
    public function createPageAndSubPageAndSubPageContent()
    {
        parent::createPageAndSubPageAndSubPageContent();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Page, $this->recordIds['newPageId']);
        $this->assertAssertionDataSet('createPageAndSubPageAndSubPageContent');
    }

    /**
     * @test
     */
    public function modifyPage()
    {
        parent::modifyPage();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Page, self::VALUE_PageId);
        $this->assertAssertionDataSet('modifyPage');
    }

    /**
     * @test
     */
    public function deletePage()
    {
        parent::deletePage();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Page, self::VALUE_PageId);
        $this->assertAssertionDataSet('deletePage');
    }

    /**
     * @test
     */
    public function deleteContentAndPage()
    {
        parent::deleteContentAndPage();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Page, self::VALUE_PageId);
        $this->assertAssertionDataSet('deleteContentAndPage');
    }

    /**
     * @test
     */
    public function localizePageAndContentsAndDeletePageLocalization()
    {
        // Create localized page and localize content elements first
        parent::localizePageAndContentsAndDeletePageLocalization();
        // Deleted records are not discarded
        $this->actionService->clearWorkspaceRecord(self::TABLE_Page, $this->recordIds['localizedPageId']);
        $this->assertAssertionDataSet('localizePageAndContentsAndDeletePageLocalization');
    }

    /**
     * @test
     */
    public function localizeNestedPagesAndContents()
    {
        parent::localizeNestedPagesAndContents();
        // Should discard the localized parent page and its content elements, but no sub page change or default lang content element
        $this->actionService->clearWorkspaceRecord(self::TABLE_Page, $this->recordIds['localizedParentPageId']);
        $this->assertAssertionDataSet('localizeNestedPagesAndContents');
    }

    /**
     * @test
     */
    public function copyPage()
    {
        parent::copyPage();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Page, $this->recordIds['newPageId']);
        $this->assertAssertionDataSet('copyPage');
    }

    /**
     * @test
     */
    public function copyPageFreeMode()
    {
        parent::copyPageFreeMode();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Page, $this->recordIds['newPageId']);
        $this->assertAssertionDataSet('copyPageFreeMode');
    }

    /**
     * @test
     */
    public function localizePage()
    {
        parent::localizePage();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Page, $this->recordIds['localizedPageId']);
        $this->assertAssertionDataSet('localizePage');
    }

    /**
     * @test
     */
    public function createPageAndChangePageSorting()
    {
        parent::createPageAndChangePageSorting();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Page, $this->recordIds['newPageId']);
        $this->assertAssertionDataSet('createPageAndChangePageSorting');
    }

    /**
     * @test
     */
    public function createPageAndMoveCreatedPage()
    {
        parent::createPageAndMoveCreatedPage();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Page, $this->recordIds['newPageId']);
        $this->assertAssertionDataSet('createPageAndMoveCreatedPage');
    }

    /**
     * @test
     */
    public function changePageSorting()
    {
        parent::changePageSorting();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Page, self::VALUE_PageId);
        $this->assertAssertionDataSet('changePageSorting');
    }

    /**
     * @test
     */
    public function changePageSortingAfterSelf()
    {
        parent::changePageSortingAfterSelf();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Page, self::VALUE_PageId);
        $this->assertAssertionDataSet('changePageSortingAfterSelf');
    }

    /**
     * @test
     */
    public function movePageToDifferentPage()
    {
        parent::movePageToDifferentPage();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Page, self::VALUE_PageId);
        $this->assertAssertionDataSet('movePageToDifferentPage');
    }

    /**
     * @test
     */
    public function movePageToDifferentPageTwice()
    {
        parent::movePageToDifferentPageTwice();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Page, self::VALUE_PageId);
        $this->assertAssertionDataSet('movePageToDifferentPageTwice');
    }

    /**
     * @test
     */
    public function movePageLocalizedToDifferentPageTwice()
    {
        parent::movePageLocalizedToDifferentPageTwice();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Page, self::VALUE_PageId);
        $this->assertAssertionDataSet('movePageLocalizedToDifferentPageTwice');
    }

    /**
     * @test
     */
    public function movePageLocalizedInLiveToDifferentPageTwice()
    {
        parent::movePageLocalizedInLiveToDifferentPageTwice();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Page, self::VALUE_PageId);
        $this->assertAssertionDataSet('movePageLocalizedInLiveToDifferentPageTwice');
    }

    /**
     * @test
     */
    public function movePageLocalizedInLiveWorkspaceChangedToDifferentPageTwice()
    {
        parent::movePageLocalizedInLiveWorkspaceChangedToDifferentPageTwice();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Page, self::VALUE_PageId);
        $this->assertAssertionDataSet('movePageLocalizedInLiveWorkspaceChangedToDifferentPageTwice');
    }

    /**
     * @test
     */
    public function movePageLocalizedInLiveWorkspaceDeletedToDifferentPageTwice()
    {
        parent::movePageLocalizedInLiveWorkspaceDeletedToDifferentPageTwice();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Page, self::VALUE_PageId);
        $this->assertAssertionDataSet('movePageLocalizedInLiveWorkspaceDeletedToDifferentPageTwice');
    }

    /**
     * @test
     */
    public function movePageToDifferentPageAndChangeSorting()
    {
        parent::movePageToDifferentPageAndChangeSorting();
        $this->actionService->clearWorkspaceRecords([
            self::TABLE_Page => [self::VALUE_PageId, self::VALUE_PageIdTarget],
        ]);
        $this->assertAssertionDataSet('movePageToDifferentPageNChangeSorting');
    }

    /**
     * @test
     * @see https://forge.typo3.org/issues/33104
     * @see https://forge.typo3.org/issues/55573
     */
    public function movePageToDifferentPageAndCreatePageAfterMovedPage()
    {
        parent::movePageToDifferentPageAndCreatePageAfterMovedPage();
        $this->actionService->clearWorkspaceRecords([
            self::TABLE_Page => [self::VALUE_PageIdTarget, $this->recordIds['newPageId']],
        ]);
        $this->assertAssertionDataSet('movePageToDifferentPageNCreatePageAfterMovedPage');
    }

    /*************************************
     * Copying page contents and sub-pages
     *************************************/

    /**
     * @test
     */
    public function createContentAndCopyDraftPage()
    {
        parent::createContentAndCopyDraftPage();
        $this->actionService->clearWorkspaceRecords([
            self::TABLE_Content => [$this->recordIds['newContentId']],
            self::TABLE_Page => [$this->recordIds['copiedPageId']]
        ]);
        $this->assertAssertionDataSet('createContentAndCopyDraftPage');
    }

    /**
     * @test
     */
    public function createPageAndCopyDraftParentPage()
    {
        parent::createPageAndCopyDraftParentPage();
        $this->actionService->clearWorkspaceRecords([
            self::TABLE_Page => [$this->recordIds['newPageId'], $this->recordIds['copiedPageId']]
        ]);
        $this->assertAssertionDataSet('createPageAndCopyDraftParentPage');
    }

    /**
     * @test
     */
    public function createNestedPagesAndCopyDraftParentPage()
    {
        parent::createNestedPagesAndCopyDraftParentPage();
        // Discarding only the copied parent page to see what happens with sub pages
        $this->actionService->clearWorkspaceRecord(self::TABLE_Page, $this->recordIds['copiedPageId']);
        $this->assertAssertionDataSet('createNestedPagesAndCopyDraftParentPage');
    }

    /**
     * @test
     */
    public function createPlaceholdersAndDeleteDraftParentPage()
    {
        parent::createPlaceholdersAndDeleteDraftParentPage();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Page, $this->recordIds['deletedPageId']);
        $this->assertAssertionDataSet('createPlaceholdersAndDeleteDraftParentPage');
    }
}
