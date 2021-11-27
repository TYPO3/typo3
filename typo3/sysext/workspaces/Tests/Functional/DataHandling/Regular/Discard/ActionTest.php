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
        $this->assertCSVDataSet(__DIR__ . '/DataSet/createContents.csv');
    }

    /**
     * @test
     */
    public function createContentAndCopyContent(): void
    {
        parent::createContentAndCopyContent();
        // discard copied content
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, $this->recordIds['versionedCopiedContentId']);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/createContentAndCopyContent.csv');
    }

    /**
     * @test
     */
    public function createContentAndLocalize(): void
    {
        parent::createContentAndLocalize();
        // discard default language content
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, $this->recordIds['newContentId']);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/createContentAndLocalize.csv');
    }

    /**
     * @test
     */
    public function modifyContent(): void
    {
        parent::modifyContent();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, self::VALUE_ContentIdSecond);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/modifyContent.csv');
    }

    /**
     * @test
     */
    public function hideContent(): void
    {
        parent::hideContent();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, self::VALUE_ContentIdSecond);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/hideContent.csv');
    }

    /**
     * @test
     */
    public function hideContentAndMoveToDifferentPage(): void
    {
        parent::hideContent();
        parent::moveContentToDifferentPage();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, self::VALUE_ContentIdSecond);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/hideContentAndMoveToDifferentPage.csv');
    }

    /**
     * @test
     */
    public function deleteContent(): void
    {
        parent::deleteContent();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, self::VALUE_ContentIdSecond);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/deleteContent.csv');
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
        $this->assertCSVDataSet(__DIR__ . '/DataSet/deleteLocalizedContentNDeleteContent.csv');
    }

    /**
     * @test
     */
    public function copyContent(): void
    {
        parent::copyContent();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, $this->recordIds['copiedContentId']);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/copyContent.csv');
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
        $this->assertCSVDataSet(__DIR__ . '/DataSet/copyContentToLanguage.csv');
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
        $this->assertCSVDataSet(__DIR__ . '/DataSet/copyContentToLanguageFromNonDefaultLanguage.csv');
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
        $this->assertCSVDataSet(__DIR__ . '/DataSet/localizeContent.csv');
    }

    /**
     * @test
     */
    public function localizeContentAfterMovedContent(): void
    {
        parent::localizeContentAfterMovedContent();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, $this->recordIds['localizedContentId']);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/localizeContentAfterMovedContent.csv');
    }

    /**
     * @test
     */
    public function localizeContentAfterMovedInLiveContent(): void
    {
        parent::localizeContentAfterMovedInLiveContent();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, $this->recordIds['localizedContentId']);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/localizeContentAfterMovedInLiveContent.csv');
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
        $this->assertCSVDataSet(__DIR__ . '/DataSet/localizeContentFromNonDefaultLanguage.csv');
    }

    /**
     * @test
     */
    public function changeContentSorting(): void
    {
        parent::changeContentSorting();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, self::VALUE_ContentIdFirst);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/changeContentSorting.csv');
    }

    /**
     * @test
     */
    public function changeContentSortingAfterSelf(): void
    {
        parent::changeContentSortingAfterSelf();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, self::VALUE_ContentIdFirst);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/changeContentSortingAfterSelf.csv');
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
        $this->assertCSVDataSet(__DIR__ . '/DataSet/changeContentSortingNDeleteMovedRecord.csv');
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
        $this->assertCSVDataSet(__DIR__ . '/DataSet/changeContentSortingNDeleteLiveRecord.csv');
    }

    /**
     * @test
     */
    public function moveContentToDifferentPage(): void
    {
        parent::moveContentToDifferentPage();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, self::VALUE_ContentIdSecond);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/moveContentToDifferentPage.csv');
    }

    /**
     * @test
     */
    public function moveContentToDifferentPageAndChangeSorting(): void
    {
        parent::moveContentToDifferentPageAndChangeSorting();
        $this->actionService->clearWorkspaceRecords([
            self::TABLE_Content => [self::VALUE_ContentIdFirst, self::VALUE_ContentIdSecond],
        ]);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/moveContentToDifferentPageNChangeSorting.csv');
    }

    /**
     * @test
     */
    public function moveContentToDifferentPageAndHide(): void
    {
        parent::moveContentToDifferentPageAndHide();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, self::VALUE_ContentIdSecond);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/moveContentToDifferentPageAndHide.csv');
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
        $this->assertCSVDataSet(__DIR__ . '/DataSet/createPage.csv');
    }

    /**
     * @test
     */
    public function createPageAndSubPageAndSubPageContent(): void
    {
        parent::createPageAndSubPageAndSubPageContent();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Page, $this->recordIds['newPageId']);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/createPageAndSubPageAndSubPageContent.csv');
    }

    /**
     * @test
     */
    public function modifyPage(): void
    {
        parent::modifyPage();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Page, self::VALUE_PageId);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/modifyPage.csv');
    }

    /**
     * @test
     */
    public function deletePage(): void
    {
        parent::deletePage();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Page, self::VALUE_PageId);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/deletePage.csv');
    }

    /**
     * @test
     */
    public function deleteContentAndPage(): void
    {
        parent::deleteContentAndPage();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Page, self::VALUE_PageId);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/deleteContentAndPage.csv');
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
        $this->assertCSVDataSet(__DIR__ . '/DataSet/localizePageAndContentsAndDeletePageLocalization.csv');
    }

    /**
     * @test
     */
    public function localizeNestedPagesAndContents(): void
    {
        parent::localizeNestedPagesAndContents();
        // Should discard the localized parent page and its content elements, but no sub page change or default lang content element
        $this->actionService->clearWorkspaceRecord(self::TABLE_Page, $this->recordIds['localizedParentPageId']);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/localizeNestedPagesAndContents.csv');
    }

    /**
     * @test
     */
    public function copyPage(): void
    {
        parent::copyPage();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Page, $this->recordIds['newPageId']);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/copyPage.csv');
    }

    /**
     * @test
     */
    public function copyPageFreeMode(): void
    {
        parent::copyPageFreeMode();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Page, $this->recordIds['newPageId']);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/copyPageFreeMode.csv');
    }

    /**
     * @test
     */
    public function localizePage(): void
    {
        parent::localizePage();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Page, $this->recordIds['localizedPageId']);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/localizePage.csv');
    }

    /**
     * @test
     */
    public function localizePageHiddenHideAtCopyFalse(): void
    {
        parent::localizePageHiddenHideAtCopyFalse();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Page, $this->recordIds['localizedPageId']);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/localizePageHiddenHideAtCopyFalse.csv');
    }

    /**
     * @test
     */
    public function localizePageNotHiddenHideAtCopyFalse(): void
    {
        parent::localizePageNotHiddenHideAtCopyFalse();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Page, $this->recordIds['localizedPageId']);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/localizePageNotHiddenHideAtCopyFalse.csv');
    }

    /**
     * @test
     */
    public function localizePageNotHiddenHideAtCopyDisableHideAtCopyUnset(): void
    {
        parent::localizePageNotHiddenHideAtCopyDisableHideAtCopyUnset();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Page, $this->recordIds['localizedPageId']);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/localizePageNotHiddenHideAtCopyDisableHideAtCopyUnset.csv');
    }

    /**
     * @test
     */
    public function localizePageHiddenHideAtCopyDisableHideAtCopyUnset(): void
    {
        parent::localizePageHiddenHideAtCopyDisableHideAtCopyUnset();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Page, $this->recordIds['localizedPageId']);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/localizePageHiddenHideAtCopyDisableHideAtCopyUnset.csv');
    }

    /**
     * @test
     */
    public function localizePageNotHiddenHideAtCopyDisableHideAtCopySetToFalse(): void
    {
        parent::localizePageNotHiddenHideAtCopyDisableHideAtCopySetToFalse();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Page, $this->recordIds['localizedPageId']);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/localizePageNotHiddenHideAtCopyDisableHideAtCopySetToFalse.csv');
    }

    /**
     * @test
     */
    public function localizePageHiddenHideAtCopyDisableHideAtCopySetToFalse(): void
    {
        parent::localizePageHiddenHideAtCopyDisableHideAtCopySetToFalse();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Page, $this->recordIds['localizedPageId']);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/localizePageHiddenHideAtCopyDisableHideAtCopySetToFalse.csv');
    }

    /**
     * @test
     */
    public function localizePageNotHiddenHideAtCopyDisableHideAtCopySetToTrue(): void
    {
        parent::localizePageNotHiddenHideAtCopyDisableHideAtCopySetToTrue();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Page, $this->recordIds['localizedPageId']);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/localizePageNotHiddenHideAtCopyDisableHideAtCopySetToTrue.csv');
    }

    /**
     * @test
     */
    public function localizePageHiddenHideAtCopyDisableHideAtCopySetToTrue(): void
    {
        parent::localizePageHiddenHideAtCopyDisableHideAtCopySetToTrue();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Page, $this->recordIds['localizedPageId']);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/localizePageHiddenHideAtCopyDisableHideAtCopySetToTrue.csv');
    }

    /**
     * @test
     */
    public function createPageAndChangePageSorting(): void
    {
        parent::createPageAndChangePageSorting();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Page, $this->recordIds['newPageId']);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/createPageAndChangePageSorting.csv');
    }

    /**
     * @test
     */
    public function createPageAndMoveCreatedPage(): void
    {
        parent::createPageAndMoveCreatedPage();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Page, $this->recordIds['newPageId']);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/createPageAndMoveCreatedPage.csv');
    }

    /**
     * @test
     */
    public function changePageSorting(): void
    {
        parent::changePageSorting();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Page, self::VALUE_PageId);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/changePageSorting.csv');
    }

    /**
     * @test
     */
    public function changePageSortingAfterSelf(): void
    {
        parent::changePageSortingAfterSelf();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Page, self::VALUE_PageId);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/changePageSortingAfterSelf.csv');
    }

    /**
     * @test
     */
    public function movePageToDifferentPage(): void
    {
        parent::movePageToDifferentPage();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Page, self::VALUE_PageId);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/movePageToDifferentPage.csv');
    }

    /**
     * @test
     */
    public function movePageToDifferentPageTwice(): void
    {
        parent::movePageToDifferentPageTwice();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Page, self::VALUE_PageId);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/movePageToDifferentPageTwice.csv');
    }

    /**
     * @test
     */
    public function movePageLocalizedToDifferentPageTwice(): void
    {
        parent::movePageLocalizedToDifferentPageTwice();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Page, self::VALUE_PageId);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/movePageLocalizedToDifferentPageTwice.csv');
    }

    /**
     * @test
     */
    public function movePageLocalizedInLiveToDifferentPageTwice(): void
    {
        parent::movePageLocalizedInLiveToDifferentPageTwice();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Page, self::VALUE_PageId);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/movePageLocalizedInLiveToDifferentPageTwice.csv');
    }

    /**
     * @test
     */
    public function movePageLocalizedInLiveWorkspaceChangedToDifferentPageTwice(): void
    {
        parent::movePageLocalizedInLiveWorkspaceChangedToDifferentPageTwice();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Page, self::VALUE_PageId);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/movePageLocalizedInLiveWorkspaceChangedToDifferentPageTwice.csv');
    }

    /**
     * @test
     */
    public function movePageLocalizedInLiveWorkspaceDeletedToDifferentPageTwice(): void
    {
        parent::movePageLocalizedInLiveWorkspaceDeletedToDifferentPageTwice();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Page, self::VALUE_PageId);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/movePageLocalizedInLiveWorkspaceDeletedToDifferentPageTwice.csv');
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
        $this->assertCSVDataSet(__DIR__ . '/DataSet/movePageToDifferentPageNChangeSorting.csv');
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
        $this->assertCSVDataSet(__DIR__ . '/DataSet/movePageToDifferentPageNCreatePageAfterMovedPage.csv');
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
            self::TABLE_Page => [$this->recordIds['copiedPageId']],
        ]);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/createContentAndCopyDraftPage.csv');
    }

    /**
     * @test
     */
    public function createPageAndCopyDraftParentPage(): void
    {
        parent::createPageAndCopyDraftParentPage();
        $this->actionService->clearWorkspaceRecords([
            self::TABLE_Page => [$this->recordIds['newPageId'], $this->recordIds['copiedPageId']],
        ]);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/createPageAndCopyDraftParentPage.csv');
    }

    /**
     * @test
     */
    public function createNestedPagesAndCopyDraftParentPage(): void
    {
        parent::createNestedPagesAndCopyDraftParentPage();
        // Discarding only the copied parent page to see what happens with sub pages
        $this->actionService->clearWorkspaceRecord(self::TABLE_Page, $this->recordIds['copiedPageId']);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/createNestedPagesAndCopyDraftParentPage.csv');
    }

    /**
     * @test
     */
    public function createPlaceholdersAndDeleteDraftParentPage(): void
    {
        parent::createPlaceholdersAndDeleteDraftParentPage();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Page, $this->recordIds['deletedPageId']);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/createPlaceholdersAndDeleteDraftParentPage.csv');
    }
}
