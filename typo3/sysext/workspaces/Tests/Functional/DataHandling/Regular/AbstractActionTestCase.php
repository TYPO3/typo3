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

namespace TYPO3\CMS\Workspaces\Tests\Functional\DataHandling\Regular;

/**
 * Functional test for the DataHandler
 */
abstract class AbstractActionTestCase extends \TYPO3\CMS\Core\Tests\Functional\DataHandling\Regular\AbstractActionTestCase
{
    const VALUE_ParentPageId = 88;
    const VALUE_ContentIdZero = 296;

    const VALUE_ContentIdTenth = 310;
    const VALUE_ContentIdTenthLocalized = 311;
    const VALUE_ContentIdTenthLocalized2 = 312;

    const VALUE_WorkspaceId = 1;

    /**
     * @var array
     */
    protected $coreExtensionsToLoad = [
        'fluid',
        'workspaces',
    ];

    /**
     * @var string
     */
    protected $scenarioDataSetDirectory = 'typo3/sysext/workspaces/Tests/Functional/DataHandling/Regular/DataSet/';

    protected function setUp(): void
    {
        parent::setUp();
        $this->importScenarioDataSet('LivePageFreeModeElements');
        $this->importScenarioDataSet('VersionDefaultElements');
        $this->importScenarioDataSet('ReferenceIndex');
        $this->setWorkspaceId(self::VALUE_WorkspaceId);
    }

    /**
     * Content records
     */

    /**
     * See DataSet/createContentRecordAndDiscardCreatedContentRecord.csv
     */
    public function createContentAndDiscardCreatedContent()
    {
        $newTableIds = $this->actionService->createNewRecord(self::TABLE_Content, self::VALUE_PageId, ['header' => 'Testing #1']);
        $this->recordIds['newContentId'] = $newTableIds[self::TABLE_Content][0];
        $versionedNewContentId = $this->actionService->getDataHandler()->getAutoVersionId(self::TABLE_Content, $this->recordIds['newContentId']);
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, $versionedNewContentId);
    }

    /**
     * See DataSet/createAndCopyContentRecordAndDiscardCopiedContentRecord.csv
     */
    public function createAndCopyContentAndDiscardCopiedContent()
    {
        $newTableIds = $this->actionService->createNewRecord(self::TABLE_Content, self::VALUE_PageId, ['header' => 'Testing #1']);
        $this->recordIds['newContentId'] = $newTableIds[self::TABLE_Content][0];
        $copiedTableIds = $this->actionService->copyRecord(self::TABLE_Content, $this->recordIds['newContentId'], self::VALUE_PageId);
        $this->recordIds['copiedContentId'] = $copiedTableIds[self::TABLE_Content][$this->recordIds['newContentId']];
        $versionedCopiedContentId = $this->actionService->getDataHandler()->getAutoVersionId(self::TABLE_Content, $this->recordIds['copiedContentId']);
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, $versionedCopiedContentId);
    }

    /**
     * See DataSet/changeContentSortingNDeleteMovedRecord.csv
     */
    public function changeContentSortingAndDeleteMovedRecord()
    {
        $this->actionService->moveRecord(self::TABLE_Content, self::VALUE_ContentIdFirst, -self::VALUE_ContentIdSecond);
        $this->actionService->deleteRecord(self::TABLE_Content, self::VALUE_ContentIdFirst);
    }

    /**
     * See DataSet/changeContentSortingNDeleteLiveRecord.csv
     */
    public function changeContentSortingAndDeleteLiveRecord()
    {
        $this->actionService->moveRecord(self::TABLE_Content, self::VALUE_ContentIdFirst, -self::VALUE_ContentIdSecond);
        // Switch to live workspace
        $this->setWorkspaceId(0);
        $this->actionService->deleteRecord(self::TABLE_Content, self::VALUE_ContentIdFirst);
        // Switch back to draft workspace
        $this->setWorkspaceId(self::VALUE_WorkspaceId);
    }

    /**
     * Page records
     */

    /**
     * See DataSet/deleteContentAndPage.csv
     */
    public function deleteContentAndPage()
    {
        $this->actionService->deleteRecord(self::TABLE_Content, self::VALUE_ContentIdSecond);
        $this->actionService->deleteRecord(self::TABLE_Page, self::VALUE_PageId);
    }

    /**
     * See DataSet/copyPageFreeMode.csv
     */
    public function copyPageFreeMode()
    {
        $newTableIds = $this->actionService->copyRecord(self::TABLE_Page, self::VALUE_PageIdTarget, self::VALUE_PageIdTarget);
        $this->recordIds['newPageId'] = $newTableIds[self::TABLE_Page][self::VALUE_PageIdTarget];
        $this->recordIds['newContentIdTenth'] = $newTableIds[self::TABLE_Content][self::VALUE_ContentIdTenth];
        $this->recordIds['newContentIdTenthLocalized'] = $newTableIds[self::TABLE_Content][self::VALUE_ContentIdTenthLocalized];
        $this->recordIds['newContentIdTenthLocalized2'] = $newTableIds[self::TABLE_Content][self::VALUE_ContentIdTenthLocalized2];
    }

    /**
     * See DataSet/movePageRecordToDifferentPageAndCreatePageRecordAfterMovedPageRecord.csv
     * @see https://forge.typo3.org/issues/33104
     * @see https://forge.typo3.org/issues/55573
     */
    public function movePageToDifferentPageAndCreatePageAfterMovedPage()
    {
        $this->actionService->moveRecord(self::TABLE_Page, self::VALUE_PageIdTarget, self::VALUE_PageIdWebsite);
        $newTableIds = $this->actionService->createNewRecord(self::TABLE_Page, -self::VALUE_PageIdTarget, ['title' => 'Testing #1', 'hidden' => 0]);
        $this->recordIds['newPageId'] = $newTableIds[self::TABLE_Page][0];
    }

    /**
     * Creates a content element and copies the page in draft workspace.
     */
    public function createContentAndCopyDraftPage()
    {
        $newTableIds = $this->actionService->createNewRecord(self::TABLE_Content, self::VALUE_PageId, ['header' => 'Testing #1']);
        $this->recordIds['newContentId'] = $newTableIds[self::TABLE_Content][0];
        $newTableIds = $this->actionService->copyRecord(self::TABLE_Page, self::VALUE_PageId, self::VALUE_PageIdTarget);
        $this->recordIds['copiedPageId'] = $newTableIds[self::TABLE_Page][self::VALUE_PageId];
    }

    /**
     * Creates a content element and copies the page in live workspace.
     */
    public function createContentAndCopyLivePage()
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
    public function createPageAndCopyDraftParentPage()
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
    public function createPageAndCopyLiveParentPage()
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
    public function createNestedPagesAndCopyDraftParentPage()
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
    public function createNestedPagesAndCopyLiveParentPage()
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
        $this->recordIds['copiedPageIdFirst'] = $newTableIds[static::TABLE_Page][$this->recordIds['newPageIdFirst']];
        $this->recordIds['copiedPageIdSecond'] = $newTableIds[static::TABLE_Page][$this->recordIds['newPageIdSecond']];

        // Switch back to draft workspace
        $this->setWorkspaceId(self::VALUE_WorkspaceId);
    }

    /**
     * Deletes a content element and copies the page in draft workspace
     */
    public function deleteContentAndCopyDraftPage()
    {
        $this->actionService->deleteRecord(self::TABLE_Content, self::VALUE_ContentIdSecond);
        $newTableIds = $this->actionService->copyRecord(self::TABLE_Page, self::VALUE_PageId, self::VALUE_PageIdTarget);
        $this->recordIds['copiedPageId'] = $newTableIds[self::TABLE_Page][self::VALUE_PageId];
    }

    /**
     * Deletes a content element and copies the page in live workspace
     */
    public function deleteContentAndCopyLivePage()
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
    public function changeContentSortingAndCopyDraftPage()
    {
        $this->actionService->moveRecord(self::TABLE_Content, self::VALUE_ContentIdFirst, -self::VALUE_ContentIdSecond);
        $newTableIds = $this->actionService->copyRecord(self::TABLE_Page, self::VALUE_PageId, self::VALUE_PageIdTarget);
        $this->recordIds['copiedPageId'] = $newTableIds[self::TABLE_Page][self::VALUE_PageId];
    }

    /**
     * Changes content sorting and copies the page in live workspace.
     */
    public function changeContentSortingAndCopyLivePage()
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
    public function moveContentAndCopyDraftPage()
    {
        $this->actionService->moveRecord(self::TABLE_Content, self::VALUE_ContentIdSecond, self::VALUE_PageIdTarget);
        $this->actionService->moveRecord(self::TABLE_Content, self::VALUE_ContentIdZero, self::VALUE_PageId);
        $newTableIds = $this->actionService->copyRecord(self::TABLE_Page, self::VALUE_PageId, self::VALUE_PageIdTarget);
        $this->recordIds['copiedPageId'] = $newTableIds[self::TABLE_Page][self::VALUE_PageId];
    }

    /**
     * Moves content either from and to the current page and copies the page in draft workspace.
     */
    public function moveContentAndCopyLivePage()
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
    public function createPlaceholdersAndDeleteDraftParentPage()
    {
        $this->backendUser->uc['recursiveDelete'] = true;

        $this->actionService->moveRecord(self::TABLE_Page, self::VALUE_PageId, -self::VALUE_PageIdTarget);
        $this->actionService->createNewRecord(self::TABLE_Page, self::VALUE_ParentPageId, ['title' => 'Testing #1']);
        $newTableIds = $this->actionService->deleteRecord(self::TABLE_Page, self::VALUE_ParentPageId);
        $this->recordIds['deletedPageId'] = $newTableIds[self::TABLE_Page][self::VALUE_ParentPageId];
    }

    /**
     * Creates new and move placeholders for pages and deleted the parent page in live workspace.
     */
    public function createPlaceholdersAndDeleteLiveParentPage()
    {
        $this->backendUser->uc['recursiveDelete'] = true;

        $this->actionService->moveRecord(self::TABLE_Page, self::VALUE_PageId, -self::VALUE_PageIdTarget);
        $this->actionService->createNewRecord(self::TABLE_Page, self::VALUE_ParentPageId, ['title' => 'Testing #1']);

        // Switch to live workspace
        $this->setWorkspaceId(0);

        $this->actionService->deleteRecord(self::TABLE_Page, self::VALUE_ParentPageId);

        // Switch back to draft workspace
        $this->setWorkspaceId(self::VALUE_WorkspaceId);
    }
}
