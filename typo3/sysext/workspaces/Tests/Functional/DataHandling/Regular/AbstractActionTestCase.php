<?php
namespace TYPO3\CMS\Workspaces\Tests\Functional\DataHandling\Regular;

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

/**
 * Functional test for the DataHandler
 */
abstract class AbstractActionTestCase extends \TYPO3\CMS\Core\Tests\Functional\DataHandling\AbstractDataHandlerActionTestCase
{
    const VALUE_ParentPageId = 88;
    const VALUE_PageId = 89;
    const VALUE_PageIdTarget = 90;
    const VALUE_PageIdWebsite = 1;
    const VALUE_ContentIdZero = 296;
    const VALUE_ContentIdFirst = 297;
    const VALUE_ContentIdSecond = 298;
    const VALUE_ContentIdThird = 299;
    const VALUE_ContentIdThirdLocalized = 300;
    const VALUE_LanguageId = 1;
    const VALUE_WorkspaceId = 1;

    const TABLE_Page = 'pages';
    const TABLE_PageOverlay = 'pages_language_overlay';
    const TABLE_Content = 'tt_content';

    /**
     * @var array
     */
    protected $coreExtensionsToLoad = array(
        'fluid',
        'version',
        'workspaces',
    );

    /**
     * @var string
     */
    protected $scenarioDataSetDirectory = 'typo3/sysext/workspaces/Tests/Functional/DataHandling/Regular/DataSet/';

    protected function setUp()
    {
        parent::setUp();
        $this->importScenarioDataSet('LiveDefaultPages');
        $this->importScenarioDataSet('LiveDefaultElements');
        $this->importScenarioDataSet('ReferenceIndex');

        $this->setUpFrontendRootPage(1, array('typo3/sysext/core/Tests/Functional/Fixtures/Frontend/JsonRenderer.ts'));
        $this->backendUser->workspace = self::VALUE_WorkspaceId;
    }

    /**
     * Content records
     */

    /**
     * @see DataSet/Assertion/createContentRecords.csv
     */
    public function createContents()
    {
        // Creating record at the beginning of the page
        $newTableIds = $this->actionService->createNewRecord(self::TABLE_Content, self::VALUE_PageId, array('header' => 'Testing #1'));
        $this->recordIds['newContentIdFirst'] = $newTableIds[self::TABLE_Content][0];
        // Creating record at the end of the page (after last one)
        $newTableIds = $this->actionService->createNewRecord(self::TABLE_Content, -self::VALUE_ContentIdSecond, array('header' => 'Testing #2'));
        $this->recordIds['newContentIdLast'] = $newTableIds[self::TABLE_Content][0];
    }

    /**
     * @see DataSet/Assertion/createContentRecordAndDiscardCreatedContentRecord.csv
     */
    public function createContentAndDiscardCreatedContent()
    {
        $newTableIds = $this->actionService->createNewRecord(self::TABLE_Content, self::VALUE_PageId, array('header' => 'Testing #1'));
        $this->recordIds['newContentId'] = $newTableIds[self::TABLE_Content][0];
        $versionedNewContentId = $this->actionService->getDataHandler()->getAutoVersionId(self::TABLE_Content, $this->recordIds['newContentId']);
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, $versionedNewContentId);
    }

    /**
     * @see DataSet/Assertion/createAndCopyContentRecordAndDiscardCopiedContentRecord.csv
     */
    public function createAndCopyContentAndDiscardCopiedContent()
    {
        $newTableIds = $this->actionService->createNewRecord(self::TABLE_Content, self::VALUE_PageId, array('header' => 'Testing #1'));
        $this->recordIds['newContentId'] = $newTableIds[self::TABLE_Content][0];
        $copiedTableIds = $this->actionService->copyRecord(self::TABLE_Content, $this->recordIds['newContentId'], self::VALUE_PageId);
        $this->recordIds['copiedContentId'] = $copiedTableIds[self::TABLE_Content][$this->recordIds['newContentId']];
        $versionedCopiedContentId = $this->actionService->getDataHandler()->getAutoVersionId(self::TABLE_Content, $this->recordIds['copiedContentId']);
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, $versionedCopiedContentId);
    }

    /**
     * @see DataSet/Assertion/modifyContentRecord.csv
     */
    public function modifyContent()
    {
        $this->actionService->modifyRecord(self::TABLE_Content, self::VALUE_ContentIdSecond, array('header' => 'Testing #1'));
    }

    /**
     * @see DataSet/Assertion/deleteContentRecord.csv
     */
    public function deleteContent()
    {
        $this->actionService->deleteRecord(self::TABLE_Content, self::VALUE_ContentIdSecond);
    }

    /**
     * @see DataSet/deleteLocalizedContentNDeleteContent.csv
     */
    public function deleteLocalizedContentAndDeleteContent()
    {
        $this->actionService->deleteRecord(self::TABLE_Content, self::VALUE_ContentIdThirdLocalized);
        $this->actionService->deleteRecord(self::TABLE_Content, self::VALUE_ContentIdThird);
    }

    /**
     * @see DataSet/Assertion/copyContentRecord.csv
     */
    public function copyContent()
    {
        $copiedTableIds = $this->actionService->copyRecord(self::TABLE_Content, self::VALUE_ContentIdSecond, self::VALUE_PageId);
        $this->recordIds['copiedContentId'] = $copiedTableIds[self::TABLE_Content][self::VALUE_ContentIdSecond];
    }

    /**
     * @see DataSet/Assertion/localizeContentRecord.csv
     */
    public function localizeContent()
    {
        $localizedTableIds = $this->actionService->localizeRecord(self::TABLE_Content, self::VALUE_ContentIdSecond, self::VALUE_LanguageId);
        $this->recordIds['localizedContentId'] = $localizedTableIds[self::TABLE_Content][self::VALUE_ContentIdSecond];
    }

    /**
     * @see DataSet/Assertion/changeContentRecordSorting.csv
     */
    public function changeContentSorting()
    {
        $this->actionService->moveRecord(self::TABLE_Content, self::VALUE_ContentIdFirst, -self::VALUE_ContentIdSecond);
    }

    /**
     * @see DataSet/changeContentSortingNDeleteMovedRecord.csv
     */
    public function changeContentSortingAndDeleteMovedRecord()
    {
        $this->actionService->moveRecord(self::TABLE_Content, self::VALUE_ContentIdFirst, -self::VALUE_ContentIdSecond);
        $this->actionService->deleteRecord(self::TABLE_Content, self::VALUE_ContentIdFirst);
    }

    /**
     * @see DataSet/changeContentSortingNDeleteLiveRecord.csv
     */
    public function changeContentSortingAndDeleteLiveRecord()
    {
        $this->actionService->moveRecord(self::TABLE_Content, self::VALUE_ContentIdFirst, -self::VALUE_ContentIdSecond);
        // Switch to live workspace
        $this->backendUser->workspace = 0;
        $this->actionService->deleteRecord(self::TABLE_Content, self::VALUE_ContentIdFirst);
        // Switch back to draft workspace
        $this->backendUser->workspace = static::VALUE_WorkspaceId;
    }

    /**
     * @see DataSet/Assertion/moveContentRecordToDifferentPage.csv
     */
    public function moveContentToDifferentPage()
    {
        $this->actionService->moveRecord(self::TABLE_Content, self::VALUE_ContentIdSecond, self::VALUE_PageIdTarget);
    }

    /**
     * @see DataSet/Assertion/moveContentRecordToDifferentPageAndChangeSorting.csv
     */
    public function moveContentToDifferentPageAndChangeSorting()
    {
        $this->actionService->moveRecord(self::TABLE_Content, self::VALUE_ContentIdSecond, self::VALUE_PageIdTarget);
        $this->actionService->moveRecord(self::TABLE_Content, self::VALUE_ContentIdFirst, -self::VALUE_ContentIdSecond);
    }

    /**
     * Page records
     */

    /**
     * @see DataSet/Assertion/createPageRecord.csv
     */
    public function createPage()
    {
        $newTableIds = $this->actionService->createNewRecord(self::TABLE_Page, self::VALUE_PageId, array('title' => 'Testing #1', 'hidden' => 0));
        $this->recordIds['newPageId'] = $newTableIds[self::TABLE_Page][0];
    }

    /**
     * @see DataSet/Assertion/modifyPageRecord.csv
     */
    public function modifyPage()
    {
        $this->actionService->modifyRecord(self::TABLE_Page, self::VALUE_PageId, array('title' => 'Testing #1'));
    }

    /**
     * @see DataSet/Assertion/deletePageRecord.csv
     */
    public function deletePage()
    {
        $this->actionService->deleteRecord(self::TABLE_Page, self::VALUE_PageId);
    }

    /**
     * @see DataSet/Assertion/copyPageRecord.csv
     */
    public function copyPage()
    {
        $newTableIds = $this->actionService->copyRecord(self::TABLE_Page, self::VALUE_PageId, self::VALUE_PageIdTarget);
        $this->recordIds['newPageId'] = $newTableIds[self::TABLE_Page][self::VALUE_PageId];
        $this->recordIds['newContentIdFirst'] = $newTableIds[self::TABLE_Content][self::VALUE_ContentIdFirst];
        $this->recordIds['newContentIdLast'] = $newTableIds[self::TABLE_Content][self::VALUE_ContentIdSecond];
    }

    /**
     * @see DataSet/Assertion/localizePageRecord.csv
     */
    public function localizePage()
    {
        $localizedTableIds = $this->actionService->localizeRecord(self::TABLE_Page, self::VALUE_PageId, self::VALUE_LanguageId);
        $this->recordIds['localizedPageOverlayId'] = $localizedTableIds[self::TABLE_PageOverlay][self::VALUE_PageId];
    }

    /**
     * @see DataSet/Assertion/changePageRecordSorting.csv
     */
    public function changePageSorting()
    {
        $this->actionService->moveRecord(self::TABLE_Page, self::VALUE_PageId, -self::VALUE_PageIdTarget);
    }

    /**
     * @see DataSet/Assertion/movePageRecordToDifferentPage.csv
     */
    public function movePageToDifferentPage()
    {
        $this->actionService->moveRecord(self::TABLE_Page, self::VALUE_PageId, self::VALUE_PageIdTarget);
    }

    /**
     * @see DataSet/Assertion/movePageRecordToDifferentPageAndChangeSorting.csv
     */
    public function movePageToDifferentPageAndChangeSorting()
    {
        $this->actionService->moveRecord(self::TABLE_Page, self::VALUE_PageIdTarget, self::VALUE_PageIdWebsite);
        $this->actionService->moveRecord(self::TABLE_Page, self::VALUE_PageId, -self::VALUE_PageIdTarget);
    }

    /**
     * @see DataSet/Assertion/movePageRecordToDifferentPageAndCreatePageRecordAfterMovedPageRecord.csv
     * @see http://forge.typo3.org/issues/33104
     * @see http://forge.typo3.org/issues/55573
     */
    public function movePageToDifferentPageAndCreatePageAfterMovedPage()
    {
        $this->actionService->moveRecord(self::TABLE_Page, self::VALUE_PageIdTarget, self::VALUE_PageIdWebsite);
        $newTableIds = $this->actionService->createNewRecord(self::TABLE_Page, -self::VALUE_PageIdTarget, array('title' => 'Testing #1', 'hidden' => 0));
        $this->recordIds['newPageId'] = $newTableIds[self::TABLE_Page][0];
    }

    /**
     * Creates a content element and copies the page in draft workspace.
     */
    public function createContentAndCopyDraftPage()
    {
        $newTableIds = $this->actionService->createNewRecord(self::TABLE_Content, self::VALUE_PageId, array('header' => 'Testing #1'));
        $this->recordIds['newContentId'] = $newTableIds[self::TABLE_Content][0];
        $newTableIds = $this->actionService->copyRecord(self::TABLE_Page, self::VALUE_PageId, self::VALUE_PageIdTarget);
        $this->recordIds['copiedPageId'] = $newTableIds[self::TABLE_Page][self::VALUE_PageId];
    }

    /**
     * Creates a content element and copies the page in live workspace.
     */
    public function createContentAndCopyLivePage()
    {
        $newTableIds = $this->actionService->createNewRecord(self::TABLE_Content, self::VALUE_PageId, array('header' => 'Testing #1'));
        $this->recordIds['newContentId'] = $newTableIds[self::TABLE_Content][0];

        // Switch to live workspace
        $this->backendUser->workspace = 0;

        $newTableIds = $this->actionService->copyRecord(self::TABLE_Page, self::VALUE_PageId, self::VALUE_PageIdTarget);
        $this->recordIds['copiedPageId'] = $newTableIds[self::TABLE_Page][self::VALUE_PageId];

        // Switch back to draft workspace
        $this->backendUser->workspace = static::VALUE_WorkspaceId;
    }

    /**
     * Creates a page in a draft workspace and copies the parent page in draft workspace.
     */
    public function createPageAndCopyDraftParentPage()
    {
        $this->backendUser->uc['copyLevels'] = 10;

        $newTableIds = $this->actionService->createNewRecord(static::TABLE_Page, static::VALUE_PageId, array('title' => 'Testing #1', 'hidden' => 0));
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

        $newTableIds = $this->actionService->createNewRecord(static::TABLE_Page, static::VALUE_PageId, array('title' => 'Testing #1', 'hidden' => 0));
        $this->recordIds['newPageId'] = $newTableIds[static::TABLE_Page][0];

        // Switch to live workspace
        $this->backendUser->workspace = 0;

        $newTableIds = $this->actionService->copyRecord(static::TABLE_Page, static::VALUE_PageId, static::VALUE_PageIdTarget);
        $this->recordIds['copiedPageId'] = $newTableIds[static::TABLE_Page][static::VALUE_PageId];

        // Switch back to draft workspace
        $this->backendUser->workspace = static::VALUE_WorkspaceId;
    }

    /**
     * Creates nested pages in a draft workspace and copies the parent page in draft workspace.
     */
    public function createNestedPagesAndCopyDraftParentPage()
    {
        $this->backendUser->uc['copyLevels'] = 10;

        $newTableIds = $this->actionService->createNewRecord(static::TABLE_Page, static::VALUE_PageId, array('title' => 'Testing #1', 'hidden' => 0));
        $this->recordIds['newPageIdFirst'] = $newTableIds[static::TABLE_Page][0];
        $newTableIds = $this->actionService->createNewRecord(static::TABLE_Page, $this->recordIds['newPageIdFirst'], array('title' => 'Testing #2', 'hidden' => 0));
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

        $newTableIds = $this->actionService->createNewRecord(static::TABLE_Page, static::VALUE_PageId, array('title' => 'Testing #1', 'hidden' => 0));
        $this->recordIds['newPageIdFirst'] = $newTableIds[static::TABLE_Page][0];
        $newTableIds = $this->actionService->createNewRecord(static::TABLE_Page, $this->recordIds['newPageIdFirst'], array('title' => 'Testing #2', 'hidden' => 0));
        $this->recordIds['newPageIdSecond'] = $newTableIds[static::TABLE_Page][0];

        // Switch to live workspace
        $this->backendUser->workspace = 0;

        $newTableIds = $this->actionService->copyRecord(static::TABLE_Page, static::VALUE_PageId, static::VALUE_PageIdTarget);
        $this->recordIds['copiedPageId'] = $newTableIds[static::TABLE_Page][static::VALUE_PageId];
        $this->recordIds['copiedPageIdFirst'] = $newTableIds[static::TABLE_Page][$this->recordIds['newPageIdFirst']];
        $this->recordIds['copiedPageIdSecond'] = $newTableIds[static::TABLE_Page][$this->recordIds['newPageIdSecond']];

        // Switch back to draft workspace
        $this->backendUser->workspace = static::VALUE_WorkspaceId;
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
        $this->backendUser->workspace = 0;

        $newTableIds = $this->actionService->copyRecord(self::TABLE_Page, self::VALUE_PageId, self::VALUE_PageIdTarget);
        $this->recordIds['copiedPageId'] = $newTableIds[self::TABLE_Page][self::VALUE_PageId];

        // Switch back to draft workspace
        $this->backendUser->workspace = static::VALUE_WorkspaceId;
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
        $this->backendUser->workspace = 0;

        $newTableIds = $this->actionService->copyRecord(self::TABLE_Page, self::VALUE_PageId, self::VALUE_PageIdTarget);
        $this->recordIds['copiedPageId'] = $newTableIds[self::TABLE_Page][self::VALUE_PageId];

        // Switch back to draft workspace
        $this->backendUser->workspace = static::VALUE_WorkspaceId;
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
        $this->backendUser->workspace = 0;

        $newTableIds = $this->actionService->copyRecord(self::TABLE_Page, self::VALUE_PageId, self::VALUE_PageIdTarget);
        $this->recordIds['copiedPageId'] = $newTableIds[self::TABLE_Page][self::VALUE_PageId];

        // Switch back to draft workspace
        $this->backendUser->workspace = static::VALUE_WorkspaceId;
    }

    /**
     * Creates new and move placeholders for pages and deleted the parent page in draft workspace.
     */
    public function createPlaceholdersAndDeleteDraftParentPage()
    {
        $this->backendUser->uc['recursiveDelete'] = true;

        $this->actionService->moveRecord(self::TABLE_Page, self::VALUE_PageId, -self::VALUE_PageIdTarget);
        $this->actionService->createNewRecord(self::TABLE_Page, self::VALUE_ParentPageId, array('title' => 'Testing #1'));
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
        $this->actionService->createNewRecord(self::TABLE_Page, self::VALUE_ParentPageId, array('title' => 'Testing #1'));

        // Switch to live workspace
        $this->backendUser->workspace = 0;

        $this->actionService->deleteRecord(self::TABLE_Page, self::VALUE_ParentPageId);

        // Switch back to draft workspace
        $this->backendUser->workspace = static::VALUE_WorkspaceId;
    }
}
