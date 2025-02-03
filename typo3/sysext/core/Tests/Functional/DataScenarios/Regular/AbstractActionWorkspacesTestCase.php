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

namespace TYPO3\CMS\Core\Tests\Functional\DataScenarios\Regular;

abstract class AbstractActionWorkspacesTestCase extends AbstractActionTestCase
{
    protected const VALUE_ParentPageId = 88;
    protected const VALUE_ContentIdZero = 200;

    protected const VALUE_WorkspaceId = 1;

    protected const SCENARIO_DataSet = __DIR__ . '/DataSet/ImportDefaultWorkspaces.csv';

    protected array $coreExtensionsToLoad = ['workspaces'];

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
        $newTableIds = $this->actionService->createNewRecord(self::TABLE_Content, self::VALUE_PageId, ['header' => 'Testing #1']);
        $this->recordIds['newContentId'] = $newTableIds[self::TABLE_Content][0];
        $localizedContentId = $this->actionService->localizeRecord(self::TABLE_Content, $this->recordIds['newContentId'], self::VALUE_LanguageId);
        $this->recordIds['localizedContentId'] = $localizedContentId[self::TABLE_Content][$this->recordIds['newContentId']];
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

    public function movePageToDifferentPageAndCreatePageAfterMovedPage(): void
    {
        // @see https://forge.typo3.org/issues/33104
        // @see https://forge.typo3.org/issues/55573
        $this->actionService->moveRecord(self::TABLE_Page, self::VALUE_PageIdTarget, self::VALUE_PageIdWebsite);
        $newTableIds = $this->actionService->createNewRecord(self::TABLE_Page, -self::VALUE_PageIdTarget, ['title' => 'Testing #1', 'hidden' => 0]);
        $this->recordIds['newPageId'] = $newTableIds[self::TABLE_Page][0];
    }

    public function createContentAndCopyDraftPage(): void
    {
        $newTableIds = $this->actionService->createNewRecord(self::TABLE_Content, self::VALUE_PageId, ['header' => 'Testing #1']);
        $this->recordIds['newContentId'] = $newTableIds[self::TABLE_Content][0];
        $newTableIds = $this->actionService->copyRecord(self::TABLE_Page, self::VALUE_PageId, self::VALUE_PageIdTarget);
        $this->recordIds['copiedPageId'] = $newTableIds[self::TABLE_Page][self::VALUE_PageId];
    }

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

    public function createPageAndCopyDraftParentPage(): void
    {
        $this->backendUser->uc['copyLevels'] = 10;

        $newTableIds = $this->actionService->createNewRecord(static::TABLE_Page, static::VALUE_PageId, ['title' => 'Testing #1', 'hidden' => 0]);
        $this->recordIds['newPageId'] = $newTableIds[static::TABLE_Page][0];
        $newTableIds = $this->actionService->copyRecord(static::TABLE_Page, static::VALUE_PageId, static::VALUE_PageIdTarget);
        $this->recordIds['copiedPageId'] = $newTableIds[static::TABLE_Page][static::VALUE_PageId];
    }

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

    public function deleteContentAndCopyDraftPage(): void
    {
        $this->actionService->deleteRecord(self::TABLE_Content, self::VALUE_ContentIdSecond);
        $newTableIds = $this->actionService->copyRecord(self::TABLE_Page, self::VALUE_PageId, self::VALUE_PageIdTarget);
        $this->recordIds['copiedPageId'] = $newTableIds[self::TABLE_Page][self::VALUE_PageId];
    }

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

    public function changeContentSortingAndCopyDraftPage(): void
    {
        $this->actionService->moveRecord(self::TABLE_Content, self::VALUE_ContentIdFirst, -self::VALUE_ContentIdSecond);
        $newTableIds = $this->actionService->copyRecord(self::TABLE_Page, self::VALUE_PageId, self::VALUE_PageIdTarget);
        $this->recordIds['copiedPageId'] = $newTableIds[self::TABLE_Page][self::VALUE_PageId];
    }

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

    public function moveContentAndCopyDraftPage(): void
    {
        $this->actionService->moveRecord(self::TABLE_Content, self::VALUE_ContentIdSecond, self::VALUE_PageIdTarget);
        $this->actionService->moveRecord(self::TABLE_Content, self::VALUE_ContentIdZero, self::VALUE_PageId);
        $newTableIds = $this->actionService->copyRecord(self::TABLE_Page, self::VALUE_PageId, self::VALUE_PageIdTarget);
        $this->recordIds['copiedPageId'] = $newTableIds[self::TABLE_Page][self::VALUE_PageId];
    }

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

    public function createPlaceholdersAndDeleteDraftParentPage(): void
    {
        $this->actionService->moveRecord(self::TABLE_Page, self::VALUE_PageId, -self::VALUE_PageIdTarget);
        $this->actionService->createNewRecord(self::TABLE_Page, self::VALUE_ParentPageId, ['title' => 'Testing #1']);
        $newTableIds = $this->actionService->deleteRecord(self::TABLE_Page, self::VALUE_ParentPageId);
        $this->recordIds['deletedPageId'] = $newTableIds[self::TABLE_Page][self::VALUE_ParentPageId];
    }

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

    public function deleteContentAndPage(): void
    {
        $this->actionService->deleteRecord(self::TABLE_Content, self::VALUE_ContentIdSecond);
        $this->actionService->deleteRecord(self::TABLE_Page, self::VALUE_PageId);
    }

    public function deleteModifiedContentByLiveUid(): void
    {
        $this->actionService->modifyRecord(self::TABLE_Content, self::VALUE_ContentIdFirst, ['header' => 'Testing #1']);
        $this->actionService->deleteRecord(self::TABLE_Content, self::VALUE_ContentIdFirst);
    }

    public function deleteModifiedContentByDraftUid(): void
    {
        $this->actionService->modifyRecord(self::TABLE_Content, self::VALUE_ContentIdFirst, ['header' => 'Testing #1']);
        $this->actionService->deleteRecord(self::TABLE_Content, 321);
    }

    public function deleteMovedContentByLiveUid(): void
    {
        $this->actionService->moveRecord(self::TABLE_Content, self::VALUE_ContentIdFirst, -self::VALUE_ContentIdSecond);
        $this->actionService->deleteRecord(self::TABLE_Content, self::VALUE_ContentIdFirst);
    }

    public function deleteMovedContentByDraftUid(): void
    {
        $this->actionService->moveRecord(self::TABLE_Content, self::VALUE_ContentIdFirst, -self::VALUE_ContentIdSecond);
        $this->actionService->deleteRecord(self::TABLE_Content, 321);
    }

    public function deleteNewContentByDraftUid(): void
    {
        // This is identically with 'discard' since the newly created CE in WS is simply discarded again
        // @todo: This test should be extended to localize the new CE first, and then delete default-lang CE
        //        to be in sync with the other delete tests.
        $this->actionService->createNewRecord(self::TABLE_Content, self::VALUE_PageId, ['header' => 'Testing #1']);
        $this->actionService->deleteRecord(self::TABLE_Content, 321);
    }

    public function deleteDeletedContentByLiveUid(): void
    {
        // This is identically with 'discard' since the newly created CE in WS is simply discarded again
        $this->actionService->deleteRecord(self::TABLE_Content, self::VALUE_ContentIdFirst);
        $this->actionService->deleteRecord(self::TABLE_Content, self::VALUE_ContentIdFirst);
    }

    public function deleteDeletedContentByDraftUid(): void
    {
        // This is identically with 'discard' since the newly created CE in WS is simply discarded again
        $this->actionService->deleteRecord(self::TABLE_Content, self::VALUE_ContentIdFirst);
        $this->actionService->deleteRecord(self::TABLE_Content, 321);
    }
}
