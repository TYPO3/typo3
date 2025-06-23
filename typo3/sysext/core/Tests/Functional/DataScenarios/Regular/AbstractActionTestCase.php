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

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Tests\Functional\DataScenarios\AbstractDataHandlerActionTestCase;
use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;

abstract class AbstractActionTestCase extends AbstractDataHandlerActionTestCase
{
    use SiteBasedTestTrait;

    protected const VALUE_PageId = 89;
    protected const VALUE_PageIdTarget = 90;
    protected const VALUE_PageIdWebsite = 1;
    protected const VALUE_PageIdParent = 88;
    protected const VALUE_ContentLanguageAll = 201;
    protected const VALUE_ContentIdFirst = 297;
    protected const VALUE_ContentIdSecond = 298;
    protected const VALUE_ContentIdThird = 299;
    protected const VALUE_ContentIdThirdLocalized = 300;

    protected const VALUE_LanguageId = 1;
    protected const VALUE_LanguageIdSecond = 2;

    protected const TABLE_Page = 'pages';
    protected const TABLE_Content = 'tt_content';

    protected const SCENARIO_DataSet = __DIR__ . '/DataSet/ImportDefault.csv';

    protected function setUp(): void
    {
        parent::setUp();
        // Show copied pages records in frontend request
        $GLOBALS['TCA']['pages']['ctrl']['hideAtCopy'] = false;
        // Show copied tt_content records in frontend request
        $GLOBALS['TCA']['tt_content']['ctrl']['hideAtCopy'] = false;
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);
        $this->importCSVDataSet(static::SCENARIO_DataSet);
        $this->writeSiteConfiguration(
            'test',
            $this->buildSiteConfiguration(1, '/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/'),
                $this->buildLanguageConfiguration('DA', '/da/', ['EN']),
                $this->buildLanguageConfiguration('DE', '/de/', ['DA', 'EN']),
            ]
        );
        $this->setUpFrontendRootPage(1, ['EXT:core/Tests/Functional/Fixtures/Frontend/JsonRenderer.typoscript']);
    }

    public function createContents(): void
    {
        // Creating record at the beginning of the page
        $newTableIds = $this->actionService->createNewRecord(self::TABLE_Content, self::VALUE_PageId, ['header' => 'Testing #1']);
        $this->recordIds['newContentIdFirst'] = $newTableIds[self::TABLE_Content][0];
        // Creating record at the end of the page (after last one)
        $newTableIds = $this->actionService->createNewRecord(self::TABLE_Content, -self::VALUE_ContentIdSecond, ['header' => 'Testing #2']);
        $this->recordIds['newContentIdLast'] = $newTableIds[self::TABLE_Content][0];
    }

    public function createContentForLanguageAll(): void
    {
        $newTableIds = $this->actionService->createNewRecord(self::TABLE_Content, self::VALUE_PageId, ['header' => 'Language set to all', 'sys_language_uid' => -1]);
        $this->recordIds['newContentLanguageAll'] = $newTableIds[self::TABLE_Content][0];
    }

    public function modifyContent(): void
    {
        $this->actionService->modifyRecord(self::TABLE_Content, self::VALUE_ContentIdSecond, ['header' => 'Testing #1']);
    }

    public function modifyContentWithTranslations(): void
    {
        $this->actionService->modifyRecord(self::TABLE_Content, self::VALUE_ContentIdThird, ['header' => 'Testing #1']);
    }

    public function modifySoftDeletedContent(): void
    {
        $this->actionService->deleteRecord(self::TABLE_Content, self::VALUE_ContentIdSecond);
        $this->actionService->modifyRecord(self::TABLE_Content, self::VALUE_ContentIdSecond, ['header' => 'Testing #1']);
    }

    public function modifyTranslatedContent(): void
    {
        $this->actionService->modifyRecord(self::TABLE_Content, self::VALUE_ContentIdThirdLocalized, ['header' => 'Testing Translation #3']);
    }

    public function modifyTranslatedContentThenModifyDefaultLanguageContent(): void
    {
        $this->actionService->modifyRecord(self::TABLE_Content, self::VALUE_ContentIdThirdLocalized, ['header' => 'Testing Translation #3']);
        $this->actionService->modifyRecord(self::TABLE_Content, self::VALUE_ContentIdThird, ['header' => 'Testing #3']);
    }

    public function modifyTranslatedContentThenMoveDefaultLanguageContent(): void
    {
        $this->actionService->modifyRecord(self::TABLE_Content, self::VALUE_ContentIdThirdLocalized, ['header' => 'Testing Translation #3']);
        $this->actionService->moveRecord(self::TABLE_Content, self::VALUE_ContentIdThird, -self::VALUE_ContentIdFirst);
    }

    public function modifyDefaultContentToLanguageAll(): void
    {
        $this->actionService->modifyRecord(self::TABLE_Content, self::VALUE_ContentIdThird, ['sys_language_uid' => '-1']);
    }

    public function hideContent(): void
    {
        $this->actionService->modifyRecord(self::TABLE_Content, self::VALUE_ContentIdSecond, ['hidden' => '1']);
    }

    public function copyContent(): void
    {
        $copiedTableIds = $this->actionService->copyRecord(self::TABLE_Content, self::VALUE_ContentIdSecond, self::VALUE_PageId);
        $this->recordIds['copiedContentId'] = $copiedTableIds[self::TABLE_Content][self::VALUE_ContentIdSecond];
    }

    public function copyContentToLanguage(): void
    {
        $copiedTableIds = $this->actionService->copyRecordToLanguage(self::TABLE_Content, self::VALUE_ContentIdSecond, self::VALUE_LanguageId);
        $this->recordIds['localizedContentId'] = $copiedTableIds[self::TABLE_Content][self::VALUE_ContentIdSecond];
    }

    public function copyContentToLanguageWithLanguageSynchronization(): void
    {
        $GLOBALS['TCA']['tt_content']['columns']['header']['config']['behaviour']['allowLanguageSynchronization'] = true;
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);
        $copiedTableIds = $this->actionService->copyRecordToLanguage(self::TABLE_Content, self::VALUE_ContentIdSecond, self::VALUE_LanguageId);
        $this->recordIds['localizedContentId'] = $copiedTableIds[self::TABLE_Content][self::VALUE_ContentIdSecond];
        $this->actionService->modifyRecord(self::TABLE_Content, self::VALUE_ContentIdSecond, ['header' => 'Testing #1']);
    }

    public function copyContentToLanguageWithLocalizationExclude(): void
    {
        $GLOBALS['TCA']['tt_content']['columns']['header']['l10n_mode'] = 'exclude';
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);
        $copiedTableIds = $this->actionService->copyRecordToLanguage(self::TABLE_Content, self::VALUE_ContentIdSecond, self::VALUE_LanguageId);
        $this->recordIds['localizedContentId'] = $copiedTableIds[self::TABLE_Content][self::VALUE_ContentIdSecond];
        $this->actionService->modifyRecord(self::TABLE_Content, self::VALUE_ContentIdSecond, ['header' => 'Testing #1']);
    }

    public function copyContentToLanguageFromNonDefaultLanguage(): void
    {
        $copiedTableIds = $this->actionService->copyRecordToLanguage(self::TABLE_Content, self::VALUE_ContentIdThirdLocalized, self::VALUE_LanguageIdSecond);
        $this->recordIds['localizedContentId'] = $copiedTableIds[self::TABLE_Content][self::VALUE_ContentIdThirdLocalized];
    }

    public function copyLocalizedContent(): void
    {
        // When a record is copied in the backend, this data is always passed along
        $recordData = [
            'colPos' => 0, // target colPos
            'sys_language_uid' => 0, // target language
        ];
        $copiedTableIds = $this->actionService->copyRecord(self::TABLE_Content, self::VALUE_ContentIdFirst, self::VALUE_PageId, $recordData);
        $this->recordIds['copiedContentId'] = $copiedTableIds[self::TABLE_Content][self::VALUE_ContentIdFirst];
    }
    public function copyLanguageAllContent(): void
    {
        $recordData = [
            'colPos' => 0, // target colPos
            'sys_language_uid' => 0, // target language – for a -1 element, this can be 0 or -1 depending on paste position
        ];
        $copiedTableIds = $this->actionService->copyRecord(self::TABLE_Content, self::VALUE_ContentLanguageAll, self::VALUE_PageId, $recordData);
        $this->recordIds['copiedContentId'] = $copiedTableIds[self::TABLE_Content][self::VALUE_ContentLanguageAll];
    }

    public function copyLocalizedContentToLocalizedPage(): void
    {
        // Create translated page first
        $this->actionService->copyRecordToLanguage(self::TABLE_Page, self::VALUE_PageIdTarget, self::VALUE_LanguageId);
        $this->actionService->copyRecordToLanguage(self::TABLE_Page, self::VALUE_PageIdTarget, self::VALUE_LanguageIdSecond);

        $copiedTableIds = $this->actionService->copyRecord(self::TABLE_Content, self::VALUE_ContentIdFirst, self::VALUE_PageIdTarget);
        $this->recordIds['copiedContentId'] = $copiedTableIds[self::TABLE_Content][self::VALUE_ContentIdFirst];
    }

    public function copyLocalizedContentToPartiallyLocalizedPage(): void
    {
        // Create translated page first
        $this->actionService->copyRecordToLanguage(self::TABLE_Page, self::VALUE_PageIdTarget, self::VALUE_LanguageId);

        $copiedTableIds = $this->actionService->copyRecord(self::TABLE_Content, self::VALUE_ContentIdFirst, self::VALUE_PageIdTarget);
        $this->recordIds['copiedContentId'] = $copiedTableIds[self::TABLE_Content][self::VALUE_ContentIdFirst];
    }

    public function copyLocalizedContentToNonTranslatedPage(): void
    {
        $copiedTableIds = $this->actionService->copyRecord(self::TABLE_Content, self::VALUE_ContentIdFirst, self::VALUE_PageIdTarget);
        $this->recordIds['copiedContentId'] = $copiedTableIds[self::TABLE_Content][self::VALUE_ContentIdFirst];
    }

    public function localizeContent(): void
    {
        $localizedTableIds = $this->actionService->localizeRecord(self::TABLE_Content, self::VALUE_ContentIdSecond, self::VALUE_LanguageId);
        $this->recordIds['localizedContentId'] = $localizedTableIds[self::TABLE_Content][self::VALUE_ContentIdSecond];
    }

    public function localizeContentWithHideAtCopy(): void
    {
        $GLOBALS['TCA'][self::TABLE_Content]['ctrl']['hideAtCopy'] = true;
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);
        $localizedTableIds = $this->actionService->localizeRecord(self::TABLE_Content, self::VALUE_ContentIdSecond, self::VALUE_LanguageId);
        $this->recordIds['localizedContentId'] = $localizedTableIds[self::TABLE_Content][self::VALUE_ContentIdSecond];
        $this->actionService->modifyRecord(self::TABLE_Content, $this->recordIds['localizedContentId'], ['hidden' => 0]);
    }

    public function localizeContentWithLocalizationExclude(): void
    {
        $GLOBALS['TCA']['tt_content']['columns']['header']['l10n_mode'] = 'exclude';
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);
        $localizedTableIds = $this->actionService->localizeRecord(self::TABLE_Content, self::VALUE_ContentIdSecond, self::VALUE_LanguageId);
        $this->recordIds['localizedContentId'] = $localizedTableIds[self::TABLE_Content][self::VALUE_ContentIdSecond];
        $this->actionService->modifyRecord(self::TABLE_Content, self::VALUE_ContentIdSecond, ['header' => 'Testing #1']);
    }

    public function localizeContentWithLanguageSynchronization(): void
    {
        $GLOBALS['TCA']['tt_content']['columns']['header']['config']['behaviour']['allowLanguageSynchronization'] = true;
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);
        $localizedTableIds = $this->actionService->localizeRecord(self::TABLE_Content, self::VALUE_ContentIdSecond, self::VALUE_LanguageId);
        $this->recordIds['localizedContentId'] = $localizedTableIds[self::TABLE_Content][self::VALUE_ContentIdSecond];
        $this->actionService->modifyRecord(self::TABLE_Content, self::VALUE_ContentIdSecond, ['header' => 'Testing #1']);
    }

    public function localizeContentWithLanguageSynchronizationHavingNullValue(): void
    {
        $GLOBALS['TCA']['tt_content']['columns']['bodytext']['config']['nullable'] = true;
        $GLOBALS['TCA']['tt_content']['columns']['bodytext']['config']['behaviour']['allowLanguageSynchronization'] = true;
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);
        $this->actionService->modifyRecord(self::TABLE_Content, self::VALUE_ContentIdSecond, ['bodytext' => null]);
        $GLOBALS['TCA']['tt_content']['columns']['header']['config']['behaviour']['allowLanguageSynchronization'] = true;
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);
        $localizedTableIds = $this->actionService->localizeRecord(self::TABLE_Content, self::VALUE_ContentIdSecond, self::VALUE_LanguageId);
        $this->recordIds['localizedContentId'] = $localizedTableIds[self::TABLE_Content][self::VALUE_ContentIdSecond];
        $this->actionService->modifyRecord(self::TABLE_Content, self::VALUE_ContentIdSecond, ['header' => 'Testing #1']);
    }

    public function localizeContentFromNonDefaultLanguage(): void
    {
        $localizedTableIds = $this->actionService->localizeRecord(self::TABLE_Content, self::VALUE_ContentIdThirdLocalized, self::VALUE_LanguageIdSecond);
        $this->recordIds['localizedContentId'] = $localizedTableIds[self::TABLE_Content][self::VALUE_ContentIdThirdLocalized];
    }

    public function localizeContentFromNonDefaultLanguageWithLanguageSynchronizationDefault(): void
    {
        $GLOBALS['TCA']['tt_content']['columns']['header']['config']['behaviour']['allowLanguageSynchronization'] = true;
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);
        $localizedTableIds = $this->actionService->localizeRecord(self::TABLE_Content, self::VALUE_ContentIdThirdLocalized, self::VALUE_LanguageIdSecond);
        $this->recordIds['localizedContentId'] = $localizedTableIds[self::TABLE_Content][self::VALUE_ContentIdThirdLocalized];
        $this->actionService->modifyRecord(self::TABLE_Content, self::VALUE_ContentIdThird, ['header' => 'Testing #1']);
    }

    public function localizeContentFromNonDefaultLanguageWithLanguageSynchronizationSource(): void
    {
        $GLOBALS['TCA']['tt_content']['columns']['header']['config']['behaviour']['allowLanguageSynchronization'] = true;
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);
        $localizedTableIds = $this->actionService->localizeRecord(self::TABLE_Content, self::VALUE_ContentIdThirdLocalized, self::VALUE_LanguageIdSecond);
        $this->recordIds['localizedContentId'] = $localizedTableIds[self::TABLE_Content][self::VALUE_ContentIdThirdLocalized];
        $this->actionService->modifyRecord(self::TABLE_Content, $this->recordIds['localizedContentId'], ['l10n_state' => ['header' => 'source']]);
        $this->actionService->modifyRecord(self::TABLE_Content, self::VALUE_ContentIdThird, ['header' => 'Testing #1']);
    }

    public function createLocalizedContent(): void
    {
        $newContentIdDefault = StringUtility::getUniqueId('NEW');
        $newContentIdLocalized = StringUtility::getUniqueId('NEW');
        $dataMap = [
            self::TABLE_Content => [
                $newContentIdDefault => ['pid' => self::VALUE_PageId, 'header' => 'Testing'],
                $newContentIdLocalized => ['pid' => self::VALUE_PageId, 'header' => 'Localized Testing', 'sys_language_uid' => self::VALUE_LanguageId, 'l18n_parent' => $newContentIdDefault, 'l10n_source' => $newContentIdDefault],
            ],
        ];
        $this->actionService->invoke($dataMap, []);
        $this->recordIds['newContentIdDefault'] = $this->actionService->getDataHandler()->substNEWwithIDs[$newContentIdDefault];
        $this->recordIds['newContentIdLocalized'] = $this->actionService->getDataHandler()->substNEWwithIDs[$newContentIdLocalized];
    }

    public function createLocalizedContentWithLanguageSynchronization(): void
    {
        $GLOBALS['TCA']['tt_content']['columns']['header']['config']['behaviour']['allowLanguageSynchronization'] = true;
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);
        $newContentIdDefault = StringUtility::getUniqueId('NEW');
        $newContentIdLocalized = StringUtility::getUniqueId('NEW');
        $dataMap = [
            self::TABLE_Content => [
                $newContentIdDefault => ['pid' => self::VALUE_PageId, 'header' => 'Testing'],
                $newContentIdLocalized => ['pid' => self::VALUE_PageId, 'header' => 'Localized Testing', 'sys_language_uid' => self::VALUE_LanguageId, 'l18n_parent' => $newContentIdDefault, 'l10n_source' => $newContentIdDefault],
            ],
        ];
        $this->actionService->invoke($dataMap, []);
        $this->recordIds['newContentIdDefault'] = $this->actionService->getDataHandler()->substNEWwithIDs[$newContentIdDefault];
        $this->recordIds['newContentIdLocalized'] = $this->actionService->getDataHandler()->substNEWwithIDs[$newContentIdLocalized];
    }

    public function createLocalizedContentWithLocalizationExclude(): void
    {
        $GLOBALS['TCA']['tt_content']['columns']['header']['l10n_mode'] = 'exclude';
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);
        $newContentIdDefault = StringUtility::getUniqueId('NEW');
        $newContentIdLocalized = StringUtility::getUniqueId('NEW');
        $dataMap = [
            self::TABLE_Content => [
                $newContentIdDefault => ['pid' => self::VALUE_PageId, 'header' => 'Testing'],
                $newContentIdLocalized => ['pid' => self::VALUE_PageId, 'header' => 'Localized Testing', 'sys_language_uid' => self::VALUE_LanguageId, 'l18n_parent' => $newContentIdDefault, 'l10n_source' => $newContentIdDefault],
            ],
        ];
        $this->actionService->invoke($dataMap, []);
        $this->recordIds['newContentIdDefault'] = $this->actionService->getDataHandler()->substNEWwithIDs[$newContentIdDefault];
        $this->recordIds['newContentIdLocalized'] = $this->actionService->getDataHandler()->substNEWwithIDs[$newContentIdLocalized];
    }

    public function changeContentSorting(): void
    {
        $this->actionService->moveRecord(self::TABLE_Content, self::VALUE_ContentIdFirst, -self::VALUE_ContentIdSecond);
    }

    public function changeContentSortingAfterSelf(): void
    {
        $this->actionService->moveRecord(self::TABLE_Content, self::VALUE_ContentIdFirst, -self::VALUE_ContentIdFirst);
    }

    public function moveContentToDifferentPage(): void
    {
        $this->actionService->moveRecord(self::TABLE_Content, self::VALUE_ContentIdSecond, self::VALUE_PageIdTarget);
    }

    public function moveLanguageAllContentToDifferentPageInto(): void
    {
        $this->actionService->moveRecord(self::TABLE_Content, self::VALUE_ContentLanguageAll, self::VALUE_PageId);
    }

    public function moveLanguageAllContentToDifferentPageAfter(): void
    {
        $this->actionService->moveRecord(self::TABLE_Content, self::VALUE_ContentLanguageAll, -self::VALUE_ContentIdFirst);
    }

    public function hideContentAndMoveToDifferentPage(): void
    {
        $this->actionService->modifyRecord(self::TABLE_Content, self::VALUE_ContentIdSecond, ['hidden' => '1']);
        $this->actionService->moveRecord(self::TABLE_Content, self::VALUE_ContentIdSecond, self::VALUE_PageIdTarget);
    }

    public function movePasteContentToDifferentPage(): void
    {
        $this->actionService->moveRecord(self::TABLE_Content, self::VALUE_ContentIdSecond, self::VALUE_PageIdTarget, ['header' => 'Testing #1']);
    }

    public function moveContentToDifferentPageAndChangeSorting(): void
    {
        $this->actionService->moveRecord(self::TABLE_Content, self::VALUE_ContentIdSecond, self::VALUE_PageIdTarget);
        $this->actionService->moveRecord(self::TABLE_Content, self::VALUE_ContentIdFirst, -self::VALUE_ContentIdSecond);
    }

    public function moveContentToDifferentPageAndHide(): void
    {
        $this->actionService->moveRecord(self::TABLE_Content, self::VALUE_ContentIdSecond, self::VALUE_PageIdTarget, ['hidden' => '1']);
    }

    public function moveLocalizedContentToDifferentPage(): void
    {
        // Create translated page first
        $this->actionService->copyRecordToLanguage(self::TABLE_Page, self::VALUE_PageIdTarget, self::VALUE_LanguageId);
        $this->actionService->moveRecord(self::TABLE_Content, self::VALUE_ContentIdThird, self::VALUE_PageIdTarget);
    }

    public function createPage(): void
    {
        $newTableIds = $this->actionService->createNewRecord(self::TABLE_Page, self::VALUE_PageId, ['title' => 'Testing #1', 'hidden' => 0, 'nav_title' => 'Nav Testing #1']);
        $this->recordIds['newPageId'] = $newTableIds[self::TABLE_Page][0];
    }

    public function createPageAndSubPageAndSubPageContent(): void
    {
        $newTableIds = $this->actionService->createNewRecord(self::TABLE_Page, self::VALUE_PageId, ['title' => 'Testing #1', 'hidden' => 0]);
        $this->recordIds['newPageId'] = $newTableIds[self::TABLE_Page][0];
        $newTableIds = $this->actionService->createNewRecord(self::TABLE_Page, $this->recordIds['newPageId'], ['title' => 'Testing #1 #1', 'hidden' => 0]);
        $this->recordIds['newSubPageId'] = $newTableIds[self::TABLE_Page][0];
        $newTableIds = $this->actionService->createNewRecord(self::TABLE_Content, $this->recordIds['newSubPageId'], ['header' => 'Testing #1 #1', 'hidden' => 0]);
        $this->recordIds['newSubPageContentId'] = $newTableIds[self::TABLE_Content][0];
    }

    /**
     * This test creates a page on pid=88 (unlike other tests) and moves the new draft page on that exact level,
     * in order to only modify the "sorting" and not the "pid" setting.
     */
    public function createPageAndChangePageSorting(): void
    {
        $newTableIds = $this->actionService->createNewRecord(self::TABLE_Page, self::VALUE_PageIdParent, ['title' => 'Testing #1', 'hidden' => 0, 'nav_title' => 'Nav Testing #1']);
        $this->recordIds['newPageId'] = $newTableIds[self::TABLE_Page][0];
        $this->actionService->moveRecord(self::TABLE_Page, $this->recordIds['newPageId'], -self::VALUE_PageId);
    }

    /**
     * This change creates a page on pid=89 and moves the page one level up (= we check the pid value of both placeholder + versioned record).
     */
    public function createPageAndMoveCreatedPage(): void
    {
        $newTableIds = $this->actionService->createNewRecord(self::TABLE_Page, self::VALUE_PageId, ['title' => 'Testing #1', 'hidden' => 0, 'nav_title' => 'Nav Testing #1']);
        $this->recordIds['newPageId'] = $newTableIds[self::TABLE_Page][0];
        $this->actionService->moveRecord(self::TABLE_Page, $this->recordIds['newPageId'], -self::VALUE_PageId);
    }

    public function createPageAndContentWithTcaDefaults(): void
    {
        $newTableIds = $this->actionService->createNewRecords(
            self::VALUE_PageId,
            [
                self::TABLE_Page => ['title' => 'Testing #1'],
                self::TABLE_Content => ['pid' => '__previousUid', 'header' => 'Testing #1'],
            ]
        );
        $this->recordIds['newPageId'] = $newTableIds[self::TABLE_Page][0];
        $this->recordIds['newContentId'] = $newTableIds[self::TABLE_Content][0];
    }

    public function modifyPage(): void
    {
        $this->actionService->modifyRecord(self::TABLE_Page, self::VALUE_PageId, ['title' => 'Testing #1']);
    }

    public function modifyTranslatedPage(): void
    {
        $this->actionService->modifyRecord(self::TABLE_Page, 91, ['title' => 'Testing Translated #1']);
    }

    public function modifyTranslatedPageThenModifyPage(): void
    {
        $this->actionService->modifyRecord(self::TABLE_Page, 91, ['title' => 'Testing Translated #1']);
        $this->actionService->modifyRecord(self::TABLE_Page, self::VALUE_PageId, ['title' => 'Testing #1']);
    }

    public function copyPage(): void
    {
        $newTableIds = $this->actionService->copyRecord(self::TABLE_Page, self::VALUE_PageId, self::VALUE_PageIdTarget);
        $this->recordIds['newPageId'] = $newTableIds[self::TABLE_Page][self::VALUE_PageId];
        $this->recordIds['newContentIdFirst'] = $newTableIds[self::TABLE_Content][self::VALUE_ContentIdFirst];
        $this->recordIds['newContentIdLast'] = $newTableIds[self::TABLE_Content][self::VALUE_ContentIdSecond];
    }
    public function copyPageRecursively(): void
    {
        $this->backendUser->uc['copyLevels'] = 10;
        // Create translated page for the original page first because the initial setup does not have this
        $copiedTableIds1 = $this->actionService->copyRecordToLanguage(self::TABLE_Page, self::VALUE_PageIdParent, self::VALUE_LanguageId);
        $copiedTableIds2 = $this->actionService->copyRecordToLanguage(self::TABLE_Page, self::VALUE_PageIdParent, self::VALUE_LanguageIdSecond);
        $newTableIds = $this->actionService->copyRecord(self::TABLE_Page, self::VALUE_PageIdParent, self::VALUE_PageIdWebsite);
        $this->recordIds['newPageId'] = $newTableIds[self::TABLE_Page][self::VALUE_PageIdParent];
        $this->recordIds['newContentIdFirst'] = $newTableIds[self::TABLE_Content][self::VALUE_ContentIdFirst];
        $this->recordIds['newContentIdLast'] = $newTableIds[self::TABLE_Content][self::VALUE_ContentIdSecond];
        $this->recordIds['localizedPageId1'] = $copiedTableIds1[self::TABLE_Page][self::VALUE_PageIdParent];
        $this->recordIds['localizedPageId2'] = $copiedTableIds2[self::TABLE_Page][self::VALUE_PageIdParent];
    }

    public function changePageSorting(): void
    {
        $this->actionService->moveRecord(self::TABLE_Page, self::VALUE_PageId, -self::VALUE_PageIdTarget);
    }

    public function changePageSortingAfterSelf(): void
    {
        $this->actionService->moveRecord(self::TABLE_Page, self::VALUE_PageId, -self::VALUE_PageId);
    }

    public function movePageToDifferentPage(): void
    {
        $this->actionService->moveRecord(self::TABLE_Page, self::VALUE_PageId, self::VALUE_PageIdTarget);
    }

    public function movePageToDifferentPageTwice(): void
    {
        $this->actionService->moveRecord(self::TABLE_Page, self::VALUE_PageId, self::VALUE_PageIdTarget);
        $this->actionService->moveRecord(self::TABLE_Page, self::VALUE_PageId, self::VALUE_PageIdWebsite);
    }

    public function movePageToDifferentPageAndChangeSorting(): void
    {
        $this->actionService->moveRecord(self::TABLE_Page, self::VALUE_PageIdTarget, self::VALUE_PageIdWebsite);
        $this->actionService->moveRecord(self::TABLE_Page, self::VALUE_PageId, -self::VALUE_PageIdTarget);
    }

    public function deleteContent(): void
    {
        $this->actionService->deleteRecord(self::TABLE_Content, self::VALUE_ContentIdSecond);
    }

    public function deletePage(): void
    {
        $this->actionService->deleteRecord(self::TABLE_Page, self::VALUE_PageId);
    }

    public function deleteThenHardDeletePage(): void
    {
        // Soft-delete a default language page
        $this->actionService->deleteRecord(self::TABLE_Page, self::VALUE_PageId);
        // Now hard delete that page. Recycler can trigger this.
        /** @var DataHandler $dataHandler */
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([], []);
        $dataHandler->deleteAction(self::TABLE_Page, self::VALUE_PageId, true, true);
    }

    public function deleteThenHardDeletePageWithSubpages(): void
    {
        // Soft-delete a default language page
        $this->actionService->deleteRecord(self::TABLE_Page, self::VALUE_PageIdParent);
        // Now hard delete that page. Recycler can trigger this.
        /** @var DataHandler $dataHandler */
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([], []);
        $dataHandler->deleteAction(self::TABLE_Page, self::VALUE_PageIdParent, true, true);
    }

    public function deleteThenHardDeleteLocalizedPage(): void
    {
        // Soft-delete a localized page
        $this->actionService->deleteRecord(self::TABLE_Page, 91);
        // Now hard delete that localized page. Recycler can trigger this.
        /** @var DataHandler $dataHandler */
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([], []);
        $dataHandler->deleteAction(self::TABLE_Page, 91, true, true);
    }
    public function deleteThenRecreateThenHardDeleteLocalizedPage(): void
    {
        // Soft-delete the localized page. This sets attached localized content elements deleted=1, too.
        $this->actionService->deleteRecord(self::TABLE_Page, 91);
        // Localize the page again to have both a soft-deleted and an "active" localization in that language.
        $copiedTableIds = $this->actionService->copyRecordToLanguage(self::TABLE_Page, self::VALUE_PageId, self::VALUE_LanguageId);
        $newLocalizedPageId = $copiedTableIds['pages'][self::VALUE_PageId];
        // Create a default language content element and localize it.
        $newTableIds = $this->actionService->createNewRecord(self::TABLE_Content, self::VALUE_PageId, ['header' => 'Testing #1']);
        $newContentElementId = $newTableIds['tt_content'][0];
        $this->actionService->localizeRecord(self::TABLE_Content, $newContentElementId, self::VALUE_LanguageId);
        // Now hard delete the previously soft-deleted localized page. Recycler can trigger this.
        /** @var DataHandler $dataHandler */
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([], []);
        $dataHandler->deleteAction(self::TABLE_Page, 91, true, true);
    }

    public function deleteLocalizedContentAndDeleteContent(): void
    {
        $this->actionService->deleteRecord(self::TABLE_Content, self::VALUE_ContentIdThirdLocalized);
        $this->actionService->deleteRecord(self::TABLE_Content, self::VALUE_ContentIdThird);
    }
}
