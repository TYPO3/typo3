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

namespace TYPO3\CMS\Workspaces\Tests\Functional\DataHandling\Regular;

/**
 * Functional test for the DataHandler
 */
abstract class AbstractActionTestCase extends \TYPO3\CMS\Core\Tests\Functional\DataHandling\Regular\AbstractActionTestCase
{
    protected const VALUE_ParentPageId = 88;
    protected const VALUE_ContentIdZero = 296;

    protected const VALUE_ContentIdTenth = 310;
    protected const VALUE_ContentIdTenthLocalized = 311;
    protected const VALUE_ContentIdTenthLocalized2 = 312;

    protected const VALUE_WorkspaceId = 1;

    protected const SCENARIO_DataSet = __DIR__ . '/DataSet/ImportDefault.csv';

    protected $coreExtensionsToLoad = ['workspaces'];

    public function createContentAndCopyContent(): void
    {
        $newTableIds = $this->actionService->createNewRecord(self::TABLE_Content, self::VALUE_PageId, ['header' => 'Testing #1']);
        $this->recordIds['newContentId'] = $newTableIds[self::TABLE_Content][0];
        $copiedTableIds = $this->actionService->copyRecord(self::TABLE_Content, $this->recordIds['newContentId'], self::VALUE_PageId);
        $this->recordIds['copiedContentId'] = $copiedTableIds[self::TABLE_Content][$this->recordIds['newContentId']];
        $this->recordIds['versionedCopiedContentId'] = $this->actionService->getDataHandler()->getAutoVersionId(self::TABLE_Content, $this->recordIds['copiedContentId']);
    }

    public function createContentAndLocalize(): void
    {
        // Create translated page first
        $this->actionService->copyRecordToLanguage(self::TABLE_Page, self::VALUE_PageId, self::VALUE_LanguageId);
        $newTableIds = $this->actionService->createNewRecord(self::TABLE_Content, self::VALUE_PageId, ['header' => 'Testing #1']);
        $this->recordIds['newContentId'] = $newTableIds[self::TABLE_Content][0];
        $localizedContentId = $this->actionService->localizeRecord(self::TABLE_Content, $this->recordIds['newContentId'], self::VALUE_LanguageId);
        $this->recordIds['localizedContentId'] = $localizedContentId[self::TABLE_Content][$this->recordIds['newContentId']];
    }

    public function changeContentSortingAndDeleteMovedRecord(): void
    {
        $this->actionService->moveRecord(self::TABLE_Content, self::VALUE_ContentIdFirst, -self::VALUE_ContentIdSecond);
        $this->actionService->deleteRecord(self::TABLE_Content, self::VALUE_ContentIdFirst);
    }

    public function changeContentSortingAndDeleteLiveRecord(): void
    {
        $this->actionService->moveRecord(self::TABLE_Content, self::VALUE_ContentIdFirst, -self::VALUE_ContentIdSecond);
        // Switch to live workspace
        $this->setWorkspaceId(0);
        $this->actionService->deleteRecord(self::TABLE_Content, self::VALUE_ContentIdFirst);
        // Switch back to draft workspace
        $this->setWorkspaceId(self::VALUE_WorkspaceId);
    }

    public function deleteContentAndPage(): void
    {
        $this->actionService->deleteRecord(self::TABLE_Content, self::VALUE_ContentIdSecond);
        $this->actionService->deleteRecord(self::TABLE_Page, self::VALUE_PageId);
    }

    public function copyPageFreeMode(): void
    {
        $newTableIds = $this->actionService->copyRecord(self::TABLE_Page, self::VALUE_PageIdTarget, self::VALUE_PageIdTarget);
        $this->recordIds['newPageId'] = $newTableIds[self::TABLE_Page][self::VALUE_PageIdTarget];
        $this->recordIds['newContentIdTenth'] = $newTableIds[self::TABLE_Content][self::VALUE_ContentIdTenth];
        $this->recordIds['newContentIdTenthLocalized'] = $newTableIds[self::TABLE_Content][self::VALUE_ContentIdTenthLocalized];
        $this->recordIds['newContentIdTenthLocalized2'] = $newTableIds[self::TABLE_Content][self::VALUE_ContentIdTenthLocalized2];
    }

    /**
     * @see https://forge.typo3.org/issues/33104
     * @see https://forge.typo3.org/issues/55573
     */
    public function movePageToDifferentPageAndCreatePageAfterMovedPage(): void
    {
        $this->actionService->moveRecord(self::TABLE_Page, self::VALUE_PageIdTarget, self::VALUE_PageIdWebsite);
        $newTableIds = $this->actionService->createNewRecord(self::TABLE_Page, -self::VALUE_PageIdTarget, ['title' => 'Testing #1', 'hidden' => 0]);
        $this->recordIds['newPageId'] = $newTableIds[self::TABLE_Page][0];
    }

    /**
     * Creates a content element and copies the page in draft workspace.
     */
    public function createContentAndCopyDraftPage(): void
    {
        $newTableIds = $this->actionService->createNewRecord(self::TABLE_Content, self::VALUE_PageId, ['header' => 'Testing #1']);
        $this->recordIds['newContentId'] = $newTableIds[self::TABLE_Content][0];
        $newTableIds = $this->actionService->copyRecord(self::TABLE_Page, self::VALUE_PageId, self::VALUE_PageIdTarget);
        $this->recordIds['copiedPageId'] = $newTableIds[self::TABLE_Page][self::VALUE_PageId];
    }

    /**
     * Creates a content element and copies the page in live workspace.
     */
    public function createContentAndCopyLivePage(): void
    {
        $newTableIds = $this->actionService->createNewRecord(self::TABLE_Content, self::VALUE_PageId, ['header' => 'Testing #1']);
        $this->recordIds['newContentId'] = $newTableIds[self::TABLE_Content][0];

        // Switch to live workspace
        $this->setWorkspaceId(0);

        $newTableIds = $this->actionService->copyRecord(self::TABLE_Page, self::VALUE_PageId, self::VALUE_PageIdTarget);
        $this->recordIds['copiedPageId'] = $newTableIds[self::TABLE_Page][self::VALUE_PageId];

        // Switch back to draft workspace
        $this->setWorkspaceId(self::VALUE_WorkspaceId);
    }

    /**
     * Creates a page in a draft workspace and copies the parent page in draft workspace.
     */
    public function createPageAndCopyDraftParentPage(): void
    {
        $this->backendUser->uc['copyLevels'] = 10;

        $newTableIds = $this->actionService->createNewRecord(static::TABLE_Page, static::VALUE_PageId, ['title' => 'Testing #1', 'hidden' => 0]);
        $this->recordIds['newPageId'] = $newTableIds[static::TABLE_Page][0];
        $newTableIds = $this->actionService->copyRecord(static::TABLE_Page, static::VALUE_PageId, static::VALUE_PageIdTarget);
        $this->recordIds['copiedPageId'] = $newTableIds[static::TABLE_Page][static::VALUE_PageId];
    }

    /**
     * Creates a page in a draft workspace and copies the parent page in live workspace.
     */
    public function createPageAndCopyLiveParentPage(): void
    {
        $this->backendUser->uc['copyLevels'] = 10;

        $newTableIds = $this->actionService->createNewRecord(static::TABLE_Page, static::VALUE_PageId, ['title' => 'Testing #1', 'hidden' => 0]);
        $this->recordIds['newPageId'] = $newTableIds[static::TABLE_Page][0];

        // Switch to live workspace
        $this->setWorkspaceId(0);

        $newTableIds = $this->actionService->copyRecord(static::TABLE_Page, static::VALUE_PageId, static::VALUE_PageIdTarget);
        $this->recordIds['copiedPageId'] = $newTableIds[static::TABLE_Page][static::VALUE_PageId];

        // Switch back to draft workspace
        $this->setWorkspaceId(self::VALUE_WorkspaceId);
    }

    /**
     * Creates nested pages in a draft workspace and copies the parent page in draft workspace.
     */
    public function createNestedPagesAndCopyDraftParentPage(): void
    {
        $this->backendUser->uc['copyLevels'] = 10;

        $newTableIds = $this->actionService->createNewRecord(static::TABLE_Page, static::VALUE_PageId, ['title' => 'Testing #1', 'hidden' => 0]);
        $this->recordIds['newPageIdFirst'] = $newTableIds[static::TABLE_Page][0];
        $newTableIds = $this->actionService->createNewRecord(static::TABLE_Page, $this->recordIds['newPageIdFirst'], ['title' => 'Testing #2', 'hidden' => 0]);
        $this->recordIds['newPageIdSecond'] = $newTableIds[static::TABLE_Page][0];
        $newTableIds = $this->actionService->copyRecord(static::TABLE_Page, static::VALUE_PageId, static::VALUE_PageIdTarget);
        $this->recordIds['copiedPageId'] = $newTableIds[static::TABLE_Page][static::VALUE_PageId];
        $this->recordIds['copiedPageIdFirst'] = $newTableIds[static::TABLE_Page][$this->recordIds['newPageIdFirst']];
        $this->recordIds['copiedPageIdSecond'] = $newTableIds[static::TABLE_Page][$this->recordIds['newPageIdSecond']];
    }

    /**
     * Creates nested pages in a draft workspace and copies the parent page in live workspace.
     */
    public function createNestedPagesAndCopyLiveParentPage(): void
    {
        $this->backendUser->uc['copyLevels'] = 10;

        $newTableIds = $this->actionService->createNewRecord(static::TABLE_Page, static::VALUE_PageId, ['title' => 'Testing #1', 'hidden' => 0]);
        $this->recordIds['newPageIdFirst'] = $newTableIds[static::TABLE_Page][0];
        $newTableIds = $this->actionService->createNewRecord(static::TABLE_Page, $this->recordIds['newPageIdFirst'], ['title' => 'Testing #2', 'hidden' => 0]);
        $this->recordIds['newPageIdSecond'] = $newTableIds[static::TABLE_Page][0];

        // Switch to live workspace
        $this->setWorkspaceId(0);

        $newTableIds = $this->actionService->copyRecord(static::TABLE_Page, static::VALUE_PageId, static::VALUE_PageIdTarget);
        $this->recordIds['copiedPageId'] = $newTableIds[static::TABLE_Page][static::VALUE_PageId];

        // Switch back to draft workspace
        $this->setWorkspaceId(self::VALUE_WorkspaceId);
    }

    /**
     * Deletes a content element and copies the page in draft workspace
     */
    public function deleteContentAndCopyDraftPage(): void
    {
        $this->actionService->deleteRecord(self::TABLE_Content, self::VALUE_ContentIdSecond);
        $newTableIds = $this->actionService->copyRecord(self::TABLE_Page, self::VALUE_PageId, self::VALUE_PageIdTarget);
        $this->recordIds['copiedPageId'] = $newTableIds[self::TABLE_Page][self::VALUE_PageId];
    }

    /**
     * Deletes a content element and copies the page in live workspace
     */
    public function deleteContentAndCopyLivePage(): void
    {
        $this->actionService->deleteRecord(self::TABLE_Content, self::VALUE_ContentIdSecond);

        // Switch to live workspace
        $this->setWorkspaceId(0);

        $newTableIds = $this->actionService->copyRecord(self::TABLE_Page, self::VALUE_PageId, self::VALUE_PageIdTarget);
        $this->recordIds['copiedPageId'] = $newTableIds[self::TABLE_Page][self::VALUE_PageId];

        // Switch back to draft workspace
        $this->setWorkspaceId(self::VALUE_WorkspaceId);
    }

    /**
     * Changes content sorting and copies the page in draft workspace.
     */
    public function changeContentSortingAndCopyDraftPage(): void
    {
        $this->actionService->moveRecord(self::TABLE_Content, self::VALUE_ContentIdFirst, -self::VALUE_ContentIdSecond);
        $newTableIds = $this->actionService->copyRecord(self::TABLE_Page, self::VALUE_PageId, self::VALUE_PageIdTarget);
        $this->recordIds['copiedPageId'] = $newTableIds[self::TABLE_Page][self::VALUE_PageId];
    }

    /**
     * Changes content sorting and copies the page in live workspace.
     */
    public function changeContentSortingAndCopyLivePage(): void
    {
        $this->actionService->moveRecord(self::TABLE_Content, self::VALUE_ContentIdFirst, -self::VALUE_ContentIdSecond);

        // Switch to live workspace
        $this->setWorkspaceId(0);

        $newTableIds = $this->actionService->copyRecord(self::TABLE_Page, self::VALUE_PageId, self::VALUE_PageIdTarget);
        $this->recordIds['copiedPageId'] = $newTableIds[self::TABLE_Page][self::VALUE_PageId];

        // Switch back to draft workspace
        $this->setWorkspaceId(self::VALUE_WorkspaceId);
    }

    /**
     * Moves content either from and to the current page and copies the page in draft workspace.
     */
    public function moveContentAndCopyDraftPage(): void
    {
        $this->actionService->moveRecord(self::TABLE_Content, self::VALUE_ContentIdSecond, self::VALUE_PageIdTarget);
        $this->actionService->moveRecord(self::TABLE_Content, self::VALUE_ContentIdZero, self::VALUE_PageId);
        $newTableIds = $this->actionService->copyRecord(self::TABLE_Page, self::VALUE_PageId, self::VALUE_PageIdTarget);
        $this->recordIds['copiedPageId'] = $newTableIds[self::TABLE_Page][self::VALUE_PageId];
    }

    /**
     * Moves content either from and to the current page and copies the page in draft workspace.
     */
    public function moveContentAndCopyLivePage(): void
    {
        $this->actionService->moveRecord(self::TABLE_Content, self::VALUE_ContentIdSecond, self::VALUE_PageIdTarget);
        $this->actionService->moveRecord(self::TABLE_Content, self::VALUE_ContentIdZero, self::VALUE_PageId);

        // Switch to live workspace
        $this->setWorkspaceId(0);

        $newTableIds = $this->actionService->copyRecord(self::TABLE_Page, self::VALUE_PageId, self::VALUE_PageIdTarget);
        $this->recordIds['copiedPageId'] = $newTableIds[self::TABLE_Page][self::VALUE_PageId];

        // Switch back to draft workspace
        $this->setWorkspaceId(self::VALUE_WorkspaceId);
    }

    /**
     * Creates new and move placeholders for pages and deleted the parent page in draft workspace.
     */
    public function createPlaceholdersAndDeleteDraftParentPage(): void
    {
        $this->actionService->moveRecord(self::TABLE_Page, self::VALUE_PageId, -self::VALUE_PageIdTarget);
        $this->actionService->createNewRecord(self::TABLE_Page, self::VALUE_ParentPageId, ['title' => 'Testing #1']);
        $newTableIds = $this->actionService->deleteRecord(self::TABLE_Page, self::VALUE_ParentPageId);
        $this->recordIds['deletedPageId'] = $newTableIds[self::TABLE_Page][self::VALUE_ParentPageId];
    }

    /**
     * Creates new and move placeholders for pages and deletes the parent page in live workspace.
     */
    public function createPlaceholdersAndDeleteLiveParentPage(): void
    {
        $this->actionService->moveRecord(self::TABLE_Page, self::VALUE_PageId, -self::VALUE_PageIdTarget);
        $this->actionService->createNewRecord(self::TABLE_Page, self::VALUE_ParentPageId, ['title' => 'Testing #1']);

        // Switch to live workspace
        $this->setWorkspaceId(0);

        $this->actionService->deleteRecord(self::TABLE_Page, self::VALUE_ParentPageId);

        // Switch back to draft workspace
        $this->setWorkspaceId(self::VALUE_WorkspaceId);
    }

    /**
     * This is an additional workspace related scenario derived from localizeContentAfterMovedContent(), where
     * the moving content element around is done in live only localizations are done in workspace.
     *
     * @see localizeContentAfterMovedContent
     */
    public function localizeContentAfterMovedInLiveContent(): void
    {
        $this->setWorkspaceId(0);
        // Create translated page first
        $this->actionService->copyRecordToLanguage(self::TABLE_Page, self::VALUE_PageId, self::VALUE_LanguageId);
        // Default language element 310 on page 90 that has two 'free mode' localizations is moved to page 89.
        // Note the two localizations are NOT moved along with the default language element, due to free mode.
        // Note l10n_source of first localization 311 is kept and still points to 310, even though 310 is moved to different page.
        $this->actionService->moveRecord(self::TABLE_Content, self::VALUE_ContentIdFreeMode, self::VALUE_PageId);
        $this->setWorkspaceId(self::VALUE_WorkspaceId);
        // Create new record after (relative to) previously moved one.
        $newTableIds = $this->actionService->createNewRecord(self::TABLE_Content, -self::VALUE_ContentIdFreeMode, ['header' => 'Testing #1']);
        $this->recordIds['newContentIdLast'] = $newTableIds[self::TABLE_Content][0];
        // Localize this new record
        $localizedTableIds = $this->actionService->localizeRecord(self::TABLE_Content, $newTableIds[self::TABLE_Content][0], self::VALUE_LanguageId);
        $this->recordIds['localizedContentId'] = $localizedTableIds[self::TABLE_Content][$newTableIds[self::TABLE_Content][0]];
    }
}
