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
    public function verifyCleanReferenceIndex(): void
    {
        // The test verifies the imported data set has a clean reference index by the check in tearDown()
        self::assertTrue(true);
    }

    /**
     * @test
     */
    public function createContents(): void
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
    public function createContentAndCopyContent(): void
    {
        parent::createContentAndCopyContent();
        // discard copied content
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, $this->recordIds['versionedCopiedContentId']);
        $this->assertAssertionDataSet('createContentAndCopyContent');
    }

    /**
     * @test
     */
    public function createContentAndLocalize(): void
    {
        parent::createContentAndLocalize();
        // discard default language content
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, $this->recordIds['newContentId']);
        $this->assertAssertionDataSet('createContentAndLocalize');
    }

    /**
     * @test
     */
    public function modifyContent(): void
    {
        parent::modifyContent();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, self::VALUE_ContentIdSecond);
        $this->assertAssertionDataSet('modifyContent');
    }

    /**
     * @test
     */
    public function hideContent(): void
    {
        parent::hideContent();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, self::VALUE_ContentIdSecond);
        $this->assertAssertionDataSet('hideContent');
    }

    /**
     * @test
     */
    public function hideContentAndMoveToDifferentPage(): void
    {
        parent::hideContent();
        parent::moveContentToDifferentPage();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, self::VALUE_ContentIdSecond);
        $this->assertAssertionDataSet('hideContentAndMoveToDifferentPage');
    }

    /**
     * @test
     */
    public function deleteContent(): void
    {
        parent::deleteContent();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, self::VALUE_ContentIdSecond);
        $this->assertAssertionDataSet('deleteContent');
    }

    /**
     * @test
     */
    public function deleteLocalizedContentAndDeleteContent(): void
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
    public function copyContent(): void
    {
        parent::copyContent();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, $this->recordIds['copiedContentId']);
        $this->assertAssertionDataSet('copyContent');
    }

    /**
     * @test
     */
    public function copyContentToLanguage(): void
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
    public function copyContentToLanguageFromNonDefaultLanguage(): void
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
    public function localizeContent(): void
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
    public function localizeContentAfterMovedContent(): void
    {
        parent::localizeContentAfterMovedContent();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, $this->recordIds['localizedContentId']);
        $this->assertAssertionDataSet('localizeContentAfterMovedContent');
    }

    /**
     * @test
     */
    public function localizeContentAfterMovedInLiveContent(): void
    {
        parent::localizeContentAfterMovedInLiveContent();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, $this->recordIds['localizedContentId']);
        $this->assertAssertionDataSet('localizeContentAfterMovedInLiveContent');
    }

    /**
     * @test
     */
    public function localizeContentFromNonDefaultLanguage(): void
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
    public function changeContentSorting(): void
    {
        parent::changeContentSorting();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, self::VALUE_ContentIdFirst);
        $this->assertAssertionDataSet('changeContentSorting');
    }

    /**
     * @test
     */
    public function changeContentSortingAfterSelf(): void
    {
        parent::changeContentSortingAfterSelf();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, self::VALUE_ContentIdFirst);
        $this->assertAssertionDataSet('changeContentSortingAfterSelf');
    }

    /**
     * @test
     */
    public function changeContentSortingAndDeleteMovedRecord(): void
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
    public function changeContentSortingAndDeleteLiveRecord(): void
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
    public function moveContentToDifferentPage(): void
    {
        parent::moveContentToDifferentPage();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, self::VALUE_ContentIdSecond);
        $this->assertAssertionDataSet('moveContentToDifferentPage');
    }

    /**
     * @test
     */
    public function moveContentToDifferentPageAndChangeSorting(): void
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
    public function moveContentToDifferentPageAndHide(): void
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
    public function createPage(): void
    {
        parent::createPage();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Page, $this->recordIds['newPageId']);
        $this->assertAssertionDataSet('createPage');
    }

    /**
     * @test
     */
    public function createPageAndSubPageAndSubPageContent(): void
    {
        parent::createPageAndSubPageAndSubPageContent();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Page, $this->recordIds['newPageId']);
        $this->assertAssertionDataSet('createPageAndSubPageAndSubPageContent');
    }

    /**
     * @test
     */
    public function modifyPage(): void
    {
        parent::modifyPage();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Page, self::VALUE_PageId);
        $this->assertAssertionDataSet('modifyPage');
    }

    /**
     * @test
     */
    public function deletePage(): void
    {
        parent::deletePage();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Page, self::VALUE_PageId);
        $this->assertAssertionDataSet('deletePage');
    }

    /**
     * @test
     */
    public function deleteContentAndPage(): void
    {
        parent::deleteContentAndPage();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Page, self::VALUE_PageId);
        $this->assertAssertionDataSet('deleteContentAndPage');
    }

    /**
     * @test
     */
    public function localizePageAndContentsAndDeletePageLocalization(): void
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
    public function localizeNestedPagesAndContents(): void
    {
        parent::localizeNestedPagesAndContents();
        // Should discard the localized parent page and its content elements, but no sub page change or default lang content element
        $this->actionService->clearWorkspaceRecord(self::TABLE_Page, $this->recordIds['localizedParentPageId']);
        $this->assertAssertionDataSet('localizeNestedPagesAndContents');
    }

    /**
     * @test
     */
    public function copyPage(): void
    {
        parent::copyPage();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Page, $this->recordIds['newPageId']);
        $this->assertAssertionDataSet('copyPage');
    }

    /**
     * @test
     */
    public function copyPageFreeMode(): void
    {
        parent::copyPageFreeMode();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Page, $this->recordIds['newPageId']);
        $this->assertAssertionDataSet('copyPageFreeMode');
    }

    /**
     * @test
     */
    public function localizePage(): void
    {
        parent::localizePage();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Page, $this->recordIds['localizedPageId']);
        $this->assertAssertionDataSet('localizePage');
    }

    /**
     * @test
     */
    public function createPageAndChangePageSorting(): void
    {
        parent::createPageAndChangePageSorting();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Page, $this->recordIds['newPageId']);
        $this->assertAssertionDataSet('createPageAndChangePageSorting');
    }

    /**
     * @test
     */
    public function createPageAndMoveCreatedPage(): void
    {
        parent::createPageAndMoveCreatedPage();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Page, $this->recordIds['newPageId']);
        $this->assertAssertionDataSet('createPageAndMoveCreatedPage');
    }

    /**
     * @test
     */
    public function changePageSorting(): void
    {
        parent::changePageSorting();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Page, self::VALUE_PageId);
        $this->assertAssertionDataSet('changePageSorting');
    }

    /**
     * @test
     */
    public function changePageSortingAfterSelf(): void
    {
        parent::changePageSortingAfterSelf();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Page, self::VALUE_PageId);
        $this->assertAssertionDataSet('changePageSortingAfterSelf');
    }

    /**
     * @test
     */
    public function movePageToDifferentPage(): void
    {
        parent::movePageToDifferentPage();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Page, self::VALUE_PageId);
        $this->assertAssertionDataSet('movePageToDifferentPage');
    }

    /**
     * @test
     */
    public function movePageToDifferentPageTwice(): void
    {
        parent::movePageToDifferentPageTwice();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Page, self::VALUE_PageId);
        $this->assertAssertionDataSet('movePageToDifferentPageTwice');
    }

    /**
     * @test
     */
    public function movePageLocalizedToDifferentPageTwice(): void
    {
        parent::movePageLocalizedToDifferentPageTwice();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Page, self::VALUE_PageId);
        $this->assertAssertionDataSet('movePageLocalizedToDifferentPageTwice');
    }

    /**
     * @test
     */
    public function movePageLocalizedInLiveToDifferentPageTwice(): void
    {
        parent::movePageLocalizedInLiveToDifferentPageTwice();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Page, self::VALUE_PageId);
        $this->assertAssertionDataSet('movePageLocalizedInLiveToDifferentPageTwice');
    }

    /**
     * @test
     */
    public function movePageLocalizedInLiveWorkspaceChangedToDifferentPageTwice(): void
    {
        parent::movePageLocalizedInLiveWorkspaceChangedToDifferentPageTwice();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Page, self::VALUE_PageId);
        $this->assertAssertionDataSet('movePageLocalizedInLiveWorkspaceChangedToDifferentPageTwice');
    }

    /**
     * @test
     */
    public function movePageLocalizedInLiveWorkspaceDeletedToDifferentPageTwice(): void
    {
        parent::movePageLocalizedInLiveWorkspaceDeletedToDifferentPageTwice();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Page, self::VALUE_PageId);
        $this->assertAssertionDataSet('movePageLocalizedInLiveWorkspaceDeletedToDifferentPageTwice');
    }

    /**
     * @test
     */
    public function movePageToDifferentPageAndChangeSorting(): void
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
    public function movePageToDifferentPageAndCreatePageAfterMovedPage(): void
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
    public function createContentAndCopyDraftPage(): void
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
    public function createPageAndCopyDraftParentPage(): void
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
    public function createNestedPagesAndCopyDraftParentPage(): void
    {
        parent::createNestedPagesAndCopyDraftParentPage();
        // Discarding only the copied parent page to see what happens with sub pages
        $this->actionService->clearWorkspaceRecord(self::TABLE_Page, $this->recordIds['copiedPageId']);
        $this->assertAssertionDataSet('createNestedPagesAndCopyDraftParentPage');
    }

    /**
     * @test
     */
    public function createPlaceholdersAndDeleteDraftParentPage(): void
    {
        parent::createPlaceholdersAndDeleteDraftParentPage();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Page, $this->recordIds['deletedPageId']);
        $this->assertAssertionDataSet('createPlaceholdersAndDeleteDraftParentPage');
    }
}
