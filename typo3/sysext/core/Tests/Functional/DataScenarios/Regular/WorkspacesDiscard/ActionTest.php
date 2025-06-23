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

namespace TYPO3\CMS\Core\Tests\Functional\DataScenarios\Regular\WorkspacesDiscard;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\ReferenceIndex;
use TYPO3\CMS\Core\Tests\Functional\DataScenarios\Regular\AbstractActionWorkspacesTestCase;
use TYPO3\TestingFramework\Core\Functional\Framework\Constraint\RequestSection\DoesNotHaveRecordConstraint;
use TYPO3\TestingFramework\Core\Functional\Framework\Constraint\RequestSection\HasRecordConstraint;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequestContext;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\ResponseContent;

final class ActionTest extends AbstractActionWorkspacesTestCase
{
    #[Test]
    public function verifyCleanReferenceIndex(): void
    {
        // Fix refindex, then compare with import csv again to verify nothing changed.
        // This is to make sure the import csv is 'clean' - important for the other tests.
        $this->get(ReferenceIndex::class)->updateIndex(false);
        $this->assertCSVDataSet(self::SCENARIO_DataSet);
    }

    #[Test]
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

    #[Test]
    public function createContentAndCopyContent(): void
    {
        parent::createContentAndCopyContent();
        // discard copied content
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, $this->recordIds['versionedCopiedContentId']);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/createContentAndCopyContent.csv');
    }

    #[Test]
    public function createContentAndLocalize(): void
    {
        parent::createContentAndLocalize();
        // discard default language content
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, $this->recordIds['newContentId']);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/createContentAndLocalize.csv');
    }

    #[Test]
    public function modifyContent(): void
    {
        parent::modifyContent();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, self::VALUE_ContentIdSecond);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/modifyContent.csv');
    }

    #[Test]
    public function modifyContentWithTranslations(): void
    {
        parent::modifyContentWithTranslations();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, self::VALUE_ContentIdThird);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/modifyContentWithTranslations.csv');
    }

    #[Test]
    public function modifySoftDeletedContent(): void
    {
        parent::modifySoftDeletedContent();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, self::VALUE_ContentIdSecond);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/modifySoftDeletedContent.csv');
    }

    #[Test]
    public function modifyTranslatedContent(): void
    {
        parent::modifyTranslatedContent();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, self::VALUE_ContentIdThirdLocalized);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/modifyTranslatedContent.csv');
    }

    #[Test]
    public function modifyTranslatedContentThenModifyDefaultLanguageContent(): void
    {
        parent::modifyTranslatedContentThenModifyDefaultLanguageContent();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, self::VALUE_ContentIdThirdLocalized);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/modifyTranslatedContentThenModifyDefaultLanguageContent.csv');
    }

    #[Test]
    public function modifyTranslatedContentThenMoveDefaultLanguageContent(): void
    {
        parent::modifyTranslatedContentThenMoveDefaultLanguageContent();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, self::VALUE_ContentIdThirdLocalized);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/modifyTranslatedContentThenMoveDefaultLanguageContent.csv');
    }

    #[Test]
    public function hideContent(): void
    {
        parent::hideContent();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, self::VALUE_ContentIdSecond);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/hideContent.csv');
    }

    #[Test]
    public function hideContentAndMoveToDifferentPage(): void
    {
        parent::hideContentAndMoveToDifferentPage();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, self::VALUE_ContentIdSecond);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/hideContentAndMoveToDifferentPage.csv');
    }

    #[Test]
    public function copyContent(): void
    {
        parent::copyContent();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, $this->recordIds['copiedContentId']);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/copyContent.csv');
    }

    #[Test]
    public function copyContentToLanguage(): void
    {
        parent::copyContentToLanguage();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, $this->recordIds['localizedContentId']);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/copyContentToLanguage.csv');
    }

    #[Test]
    public function copyContentToLanguageFromNonDefaultLanguage(): void
    {
        parent::copyContentToLanguageFromNonDefaultLanguage();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, $this->recordIds['localizedContentId']);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/copyContentToLanguageFromNonDefaultLanguage.csv');
    }

    #[Test]
    public function copyLocalizedContent(): void
    {
        parent::copyLocalizedContent();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, $this->recordIds['copiedContentId']);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/copyLocalizedContent.csv');
    }

    #[Test]
    public function copyLocalizedContentToLocalizedPage(): void
    {
        parent::copyLocalizedContentToLocalizedPage();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, $this->recordIds['copiedContentId']);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/copyLocalizedContentToLocalizedPage.csv');
    }

    #[Test]
    public function copyLocalizedContentToNonTranslatedPage(): void
    {
        parent::copyLocalizedContentToNonTranslatedPage();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, $this->recordIds['copiedContentId']);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/copyLocalizedContentToNonTranslatedPage.csv');
    }

    #[Test]
    public function copyLocalizedContentToPartiallyLocalizedPage(): void
    {
        parent::copyLocalizedContentToPartiallyLocalizedPage();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, $this->recordIds['copiedContentId']);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/copyLocalizedContentToPartiallyLocalizedPage.csv');
    }

    #[Test]
    public function localizeContent(): void
    {
        parent::localizeContent();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, $this->recordIds['localizedContentId']);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/localizeContent.csv');
    }

    #[Test]
    public function localizeContentWithLocalizationExclude(): void
    {
        parent::localizeContentWithLocalizationExclude();
        // @todo: currently two records are created, and need to be discarded separately
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, $this->recordIds['localizedContentId']);
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, $this->recordIds['localizedContentId'] + 1);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/localizeContentWExclude.csv');
    }

    #[Test]
    public function localizeContentFromNonDefaultLanguage(): void
    {
        parent::localizeContentFromNonDefaultLanguage();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, $this->recordIds['localizedContentId']);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/localizeContentFromNonDefaultLanguage.csv');
    }

    #[Test]
    public function changeContentSorting(): void
    {
        parent::changeContentSorting();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, self::VALUE_ContentIdFirst);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/changeContentSorting.csv');
    }

    #[Test]
    public function changeContentSortingAfterSelf(): void
    {
        parent::changeContentSortingAfterSelf();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, self::VALUE_ContentIdFirst);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/changeContentSortingAfterSelf.csv');
    }

    #[Test]
    public function changeContentSortingAndDeleteLiveRecord(): void
    {
        parent::changeContentSortingAndDeleteLiveRecord();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, self::VALUE_ContentIdFirst);
        // Note the deleted=1 records are NOT discarded. This is ok since deleted=1 means "not seen in backend",
        // so it is also ignored by the discard operation.
        $this->assertCSVDataSet(__DIR__ . '/DataSet/changeContentSortingNDeleteLiveRecord.csv');
    }

    #[Test]
    public function moveContentToDifferentPage(): void
    {
        parent::moveContentToDifferentPage();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, self::VALUE_ContentIdSecond);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/moveContentToDifferentPage.csv');
    }

    #[Test]
    public function moveLanguageAllContentToDifferentPageIntoSiteModeFallback(): void
    {
        // Inherit site configuration from setUp(): DA fallback to EN
        parent::moveLanguageAllContentToDifferentPageInto();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, self::VALUE_ContentLanguageAll);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/moveLanguageAllContentToDifferentPageIntoSiteModeFallback.csv');
    }

    #[Test]
    public function moveLanguageAllContentToDifferentPageIntoSiteModeFree(): void
    {
        // Set up "danish" to not have overlays: "free" mode
        $this->writeSiteConfiguration(
            'test',
            $this->buildSiteConfiguration(1, '/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/'),
                $this->buildLanguageConfiguration('DA', '/da/', [], 'free'),
                $this->buildLanguageConfiguration('DE', '/de/', ['DA', 'EN']),
            ]
        );
        parent::moveLanguageAllContentToDifferentPageInto();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, self::VALUE_ContentLanguageAll);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/moveLanguageAllContentToDifferentPageIntoSiteModeFree.csv');
    }

    #[Test]
    public function moveLanguageAllContentToDifferentPageIntoSiteModeStrict(): void
    {
        // Set up "danish" to "strict" mode
        $this->writeSiteConfiguration(
            'test',
            $this->buildSiteConfiguration(1, '/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/'),
                $this->buildLanguageConfiguration('DA', '/da/'),
                $this->buildLanguageConfiguration('DE', '/de/', ['DA', 'EN']),
            ]
        );
        parent::moveLanguageAllContentToDifferentPageInto();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, self::VALUE_ContentLanguageAll);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/moveLanguageAllContentToDifferentPageIntoSiteModeStrict.csv');
    }

    #[Test]
    public function moveLanguageAllContentToDifferentPageAfterSiteModeFallback(): void
    {
        // Inherit site configuration from setUp(): DA fallback to EN
        parent::moveLanguageAllContentToDifferentPageAfter();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, self::VALUE_ContentLanguageAll);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/moveLanguageAllContentToDifferentPageAfterSiteModeFallback.csv');
    }

    #[Test]
    public function moveLanguageAllContentToDifferentPageAfterSiteModeFree(): void
    {
        // Set up "danish" to not have overlays: "free" mode
        $this->writeSiteConfiguration(
            'test',
            $this->buildSiteConfiguration(1, '/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/'),
                $this->buildLanguageConfiguration('DA', '/da/', [], 'free'),
                $this->buildLanguageConfiguration('DE', '/de/', ['DA', 'EN']),
            ]
        );
        parent::moveLanguageAllContentToDifferentPageAfter();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, self::VALUE_ContentLanguageAll);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/moveLanguageAllContentToDifferentPageAfterSiteModeFree.csv');
    }

    #[Test]
    public function moveLanguageAllContentToDifferentPageAfterSiteModeStrict(): void
    {
        // Set up "danish" to "strict" mode
        $this->writeSiteConfiguration(
            'test',
            $this->buildSiteConfiguration(1, '/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/'),
                $this->buildLanguageConfiguration('DA', '/da/'),
                $this->buildLanguageConfiguration('DE', '/de/', ['DA', 'EN']),
            ]
        );
        parent::moveLanguageAllContentToDifferentPageAfter();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, self::VALUE_ContentLanguageAll);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/moveLanguageAllContentToDifferentPageAfterSiteModeStrict.csv');
    }

    #[Test]
    public function moveContentToDifferentPageAndChangeSorting(): void
    {
        parent::moveContentToDifferentPageAndChangeSorting();
        $this->actionService->clearWorkspaceRecords([
            self::TABLE_Content => [self::VALUE_ContentIdFirst, self::VALUE_ContentIdSecond],
        ]);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/moveContentToDifferentPageNChangeSorting.csv');
    }

    #[Test]
    public function moveContentToDifferentPageAndHide(): void
    {
        parent::moveContentToDifferentPageAndHide();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, self::VALUE_ContentIdSecond);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/moveContentToDifferentPageAndHide.csv');
    }

    #[Test]
    public function moveLocalizedContentToDifferentPage(): void
    {
        parent::moveLocalizedContentToDifferentPage();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, self::VALUE_ContentIdThird);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/moveLocalizedContentToDifferentPage.csv');

        // Check if the regular page contains the original record again
        $response = $this->executeFrontendSubRequest(
            (new InternalRequest())->withPageId(self::VALUE_PageId),
            (new InternalRequestContext())->withBackendUserId(self::VALUE_BackendUserId)->withWorkspaceId(self::VALUE_WorkspaceId)
        );
        $responseSectionsSource = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSectionsSource, (new HasRecordConstraint())
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Regular Element #3'));

        $response = $this->executeFrontendSubRequest(
            (new InternalRequest())->withPageId(self::VALUE_PageId)->withLanguageId(self::VALUE_LanguageId),
            (new InternalRequestContext())->withBackendUserId(self::VALUE_BackendUserId)->withWorkspaceId(self::VALUE_WorkspaceId)
        );
        $responseSectionsSource = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSectionsSource, (new HasRecordConstraint())
            ->setTable(self::TABLE_Content)->setField('header')->setValues('[Translate to Dansk:] Regular Element #3'));

        // Check if the target page does not contain the moved record
        $response = $this->executeFrontendSubRequest(
            (new InternalRequest())->withPageId(self::VALUE_PageIdTarget),
            (new InternalRequestContext())->withBackendUserId(self::VALUE_BackendUserId)->withWorkspaceId(self::VALUE_WorkspaceId)
        );
        $responseSectionsTarget = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSectionsTarget, (new DoesNotHaveRecordConstraint())
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Regular Element #3'));

        // Also test the translated page, and make sure the translated record is also discarded
        $response = $this->executeFrontendSubRequest(
            (new InternalRequest())->withPageId(self::VALUE_PageIdTarget)->withLanguageId(self::VALUE_LanguageId),
            (new InternalRequestContext())->withBackendUserId(self::VALUE_BackendUserId)->withWorkspaceId(self::VALUE_WorkspaceId)
        );
        $responseSectionsTarget = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSectionsTarget, (new DoesNotHaveRecordConstraint())
            ->setTable(self::TABLE_Content)->setField('header')->setValues('[Translate to Dansk:] Regular Element #3'));
    }

    #[Test]
    public function createPage(): void
    {
        parent::createPage();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Page, $this->recordIds['newPageId']);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/createPage.csv');
    }

    #[Test]
    public function createPageAndSubPageAndSubPageContent(): void
    {
        parent::createPageAndSubPageAndSubPageContent();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Page, $this->recordIds['newPageId']);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/createPageAndSubPageAndSubPageContent.csv');
    }

    #[Test]
    public function modifyPage(): void
    {
        parent::modifyPage();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Page, self::VALUE_PageId);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/modifyPage.csv');
    }

    #[Test]
    public function modifyTranslatedPage(): void
    {
        parent::modifyTranslatedPage();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Page, 91);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/modifyTranslatedPage.csv');
    }

    #[Test]
    public function modifyTranslatedPageThenModifyPage(): void
    {
        parent::modifyTranslatedPageThenModifyPage();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Page, 91);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/modifyTranslatedPageThenModifyPage.csv');
    }

    #[Test]
    public function copyPage(): void
    {
        parent::copyPage();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Page, $this->recordIds['newPageId']);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/copyPage.csv');
    }

    #[Test]
    public function copyPageRecursively(): void
    {
        parent::copyPageRecursively();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Page, $this->recordIds['newPageId']);
        $this->actionService->clearWorkspaceRecord(self::TABLE_Page, $this->recordIds['localizedPageId1']);
        $this->actionService->clearWorkspaceRecord(self::TABLE_Page, $this->recordIds['localizedPageId2']);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/copyPageRecursively.csv');
    }

    #[Test]
    public function createPageAndChangePageSorting(): void
    {
        parent::createPageAndChangePageSorting();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Page, $this->recordIds['newPageId']);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/createPageAndChangePageSorting.csv');
    }

    #[Test]
    public function createPageAndMoveCreatedPage(): void
    {
        parent::createPageAndMoveCreatedPage();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Page, $this->recordIds['newPageId']);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/createPageAndMoveCreatedPage.csv');
    }

    #[Test]
    public function changePageSorting(): void
    {
        parent::changePageSorting();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Page, self::VALUE_PageId);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/changePageSorting.csv');
    }

    #[Test]
    public function changePageSortingAfterSelf(): void
    {
        parent::changePageSortingAfterSelf();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Page, self::VALUE_PageId);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/changePageSortingAfterSelf.csv');
    }

    #[Test]
    public function movePageToDifferentPage(): void
    {
        parent::movePageToDifferentPage();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Page, self::VALUE_PageId);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/movePageToDifferentPage.csv');
    }

    #[Test]
    public function movePageToDifferentPageTwice(): void
    {
        parent::movePageToDifferentPageTwice();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Page, self::VALUE_PageId);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/movePageToDifferentPageTwice.csv');
    }

    #[Test]
    public function movePageToDifferentPageAndChangeSorting(): void
    {
        parent::movePageToDifferentPageAndChangeSorting();
        $this->actionService->clearWorkspaceRecords([
            self::TABLE_Page => [self::VALUE_PageId, self::VALUE_PageIdTarget],
        ]);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/movePageToDifferentPageNChangeSorting.csv');
    }

    #[Test]
    public function movePageToDifferentPageAndCreatePageAfterMovedPage(): void
    {
        parent::movePageToDifferentPageAndCreatePageAfterMovedPage();
        $this->actionService->clearWorkspaceRecords([
            self::TABLE_Page => [self::VALUE_PageIdTarget, $this->recordIds['newPageId']],
        ]);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/movePageToDifferentPageNCreatePageAfterMovedPage.csv');
    }

    #[Test]
    public function createContentAndCopyDraftPage(): void
    {
        parent::createContentAndCopyDraftPage();
        $this->actionService->clearWorkspaceRecords([
            self::TABLE_Content => [$this->recordIds['newContentId']],
            self::TABLE_Page => [$this->recordIds['copiedPageId']],
        ]);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/createContentAndCopyDraftPage.csv');
    }

    #[Test]
    public function createPageAndCopyDraftParentPage(): void
    {
        parent::createPageAndCopyDraftParentPage();
        $this->actionService->clearWorkspaceRecords([
            self::TABLE_Page => [$this->recordIds['newPageId'], $this->recordIds['copiedPageId']],
        ]);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/createPageAndCopyDraftParentPage.csv');
    }

    #[Test]
    public function createNestedPagesAndCopyDraftParentPage(): void
    {
        parent::createNestedPagesAndCopyDraftParentPage();
        // Discarding only the copied parent page to see what happens with sub pages
        $this->actionService->clearWorkspaceRecord(self::TABLE_Page, $this->recordIds['copiedPageId']);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/createNestedPagesAndCopyDraftParentPage.csv');
    }

    #[Test]
    public function createPlaceholdersAndDeleteDraftParentPage(): void
    {
        parent::createPlaceholdersAndDeleteDraftParentPage();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Page, $this->recordIds['deletedPageId']);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/createPlaceholdersAndDeleteDraftParentPage.csv');
    }

    /**
     * Test does not make sense in Modify, Publish and PublishAll
     */
    #[Test]
    public function deletingDefaultLanguageElementDiscardsConnectedLocalizedElement(): void
    {
        // Localize 'Regular Element #2' (289) in workspace "connected mode"
        $this->actionService->localizeRecord(self::TABLE_Content, self::VALUE_ContentIdSecond, self::VALUE_LanguageId);

        // And now *delete* the default language content element 'Regular Element #2' (289) in *live*,
        // which should *discard* the above localized content element in workspaces again.
        $this->setWorkspaceId(0);
        $this->actionService->deleteRecord(self::TABLE_Content, self::VALUE_ContentIdSecond);
        $this->setWorkspaceId(self::VALUE_WorkspaceId);

        $this->assertCSVDataSet(__DIR__ . '/DataSet/deletingDefaultLanguageElementDiscardsConnectedLocalizedElement.csv');
    }

    /**
     * Similar to above, but with a translation chain: Element 2 is first translated to language 1, then
     * translated to language 2 again. Both records should be discarded when discarding live element.
     *
     * Test does not make sense in Modify, Publish and PublishAll.
     */
    #[Test]
    public function deletingDefaultLanguageElementDiscardsConnectedLocalizedElementChain(): void
    {
        // Localize 'Regular Element #2' (289) in workspace "connected mode"
        $newRecordIds = $this->actionService->localizeRecord(self::TABLE_Content, self::VALUE_ContentIdSecond, self::VALUE_LanguageId);
        $localizedRecordId = $newRecordIds['tt_content'][self::VALUE_ContentIdSecond];
        // Localize 'Regular Element #2' (289) in workspace "connected mode" to language 2 as 'translation of translation':
        // l10n_parent still points to 289, but l10n_source points to 321.
        $this->actionService->localizeRecord(self::TABLE_Content, $localizedRecordId, self::VALUE_LanguageIdSecond);

        // And now *delete* the default language content element 'Regular Element #2' (289) in *live*,
        // which should *discard* the above localized content elements in workspaces again.
        $this->setWorkspaceId(0);
        $this->actionService->deleteRecord(self::TABLE_Content, self::VALUE_ContentIdSecond);
        $this->setWorkspaceId(self::VALUE_WorkspaceId);

        $this->assertCSVDataSet(__DIR__ . '/DataSet/deletingDefaultLanguageElementDiscardsConnectedLocalizedElementChain.csv');
    }

    #[Test]
    public function deleteContent(): void
    {
        parent::deleteContent();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, self::VALUE_ContentIdSecond);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/deleteContent.csv');
    }

    #[Test]
    public function deleteLocalizedContentAndDeleteContent(): void
    {
        parent::deleteLocalizedContentAndDeleteContent();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, self::VALUE_ContentIdThird);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/deleteLocalizedContentNDeleteContent.csv');
    }

    #[Test]
    public function deleteContentAndPage(): void
    {
        parent::deleteContentAndPage();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Page, self::VALUE_PageId);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/deleteContentAndPage.csv');
    }

    #[Test]
    public function deletePage(): void
    {
        parent::deletePage();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Page, self::VALUE_PageId);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/deletePage.csv');
    }

    #[Test]
    public function deleteMovedContentByLiveUid(): void
    {
        parent::deleteMovedContentByLiveUid();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, self::VALUE_ContentIdFirst);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/deleteMovedContentByLiveUid.csv');
    }

    #[Test]
    public function deleteMovedContentByDraftUid(): void
    {
        parent::deleteMovedContentByDraftUid();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, self::VALUE_ContentIdFirst);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/deleteMovedContentByDraftUid.csv');
    }
}
