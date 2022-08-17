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

namespace TYPO3\CMS\Core\Tests\Functional\DataScenarios\RegularLocalize;

use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Tests\Functional\DataScenarios\AbstractDataHandlerActionTestCase;
use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Core\Utility\GeneralUtility;

abstract class AbstractActionTestCase extends AbstractDataHandlerActionTestCase
{
    use SiteBasedTestTrait;

    protected const VALUE_PageIdParent = 88;
    protected const VALUE_PageId = 89;
    protected const VALUE_PageIdTarget = 90;
    protected const VALUE_PageIdWebsite = 1;
    protected const VALUE_ContentIdParent = 296;
    protected const VALUE_ContentIdFirst = 297;
    protected const VALUE_ContentIdSecond = 298;
    protected const VALUE_ContentIdThird = 299;
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

    /**
     * Test for issue https://forge.typo3.org/issues/83079 - sorting of 'localization of localization' should
     * use the sort value of the source record for the first localized record, and sort value of 'previous'
     * record of target language for subsequent records.
     */
    public function localizeContentFromNonDefaultLanguageWithAllContentElements(): void
    {
        // Run test without translations: We want to create all the lang 1 and lang 2 content elements
        // with one DH call in one go per language.
        $this->setWorkspaceId(0);
        if (defined('static::VALUE_WorkspaceId') > 0) {
            $this->setWorkspaceId(static::VALUE_WorkspaceId);
        }

        // @todo: This should be extracted as localizeRecords() in addition to localizeRecord() to ActionService
        // Create localization of the 3 default language content elements
        $commandMap = [
            'tt_content' => [
                self::VALUE_ContentIdFirst => [ 'localize' => self::VALUE_LanguageId],
                self::VALUE_ContentIdSecond => [ 'localize' => self::VALUE_LanguageId],
                self::VALUE_ContentIdThird => [ 'localize' => self::VALUE_LanguageId],
            ],
        ];
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([], $commandMap);
        $dataHandler->process_cmdmap();
        // uid's of lang 1 localized elements
        $mappingArray = $dataHandler->copyMappingArray_merged;

        // Localize again, with uid's of second language as source ("translation of translation")
        $commandMap = [
            'tt_content' => [
                $mappingArray['tt_content'][self::VALUE_ContentIdFirst] => ['localize' => self::VALUE_LanguageIdSecond],
                $mappingArray['tt_content'][self::VALUE_ContentIdSecond] => ['localize' => self::VALUE_LanguageIdSecond],
                $mappingArray['tt_content'][self::VALUE_ContentIdThird] => ['localize' => self::VALUE_LanguageIdSecond],
            ],
        ];
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([], $commandMap);
        $dataHandler->process_cmdmap();
    }

    public function localizePage(): void
    {
        $localizedTableIds = $this->actionService->localizeRecord(self::TABLE_Page, self::VALUE_PageId, self::VALUE_LanguageId);
        $this->recordIds['localizedPageId'] = $localizedTableIds[self::TABLE_Page][self::VALUE_PageId];
    }

    public function localizeAndCopyPage(): void
    {
        $newTableIds = $this->actionService->copyRecord(self::TABLE_Page, self::VALUE_PageId, self::VALUE_PageIdTarget);
        $this->recordIds['newPageId'] = $newTableIds[self::TABLE_Page][self::VALUE_PageId];
        $this->recordIds['newContentIdFirst'] = $newTableIds[self::TABLE_Content][self::VALUE_ContentIdFirst];
        $this->recordIds['newContentIdLast'] = $newTableIds[self::TABLE_Content][self::VALUE_ContentIdSecond];

        $localizedTableIds = $this->actionService->localizeRecord(self::TABLE_Page, self::VALUE_PageId, self::VALUE_LanguageId);
        $this->recordIds['localizedPageId'] = $localizedTableIds[self::TABLE_Page][self::VALUE_PageId];
    }

    public function localizePageAndUpdateRecordWithMinorChangesInFullRetrievedRecord(): void
    {
        $localizedTableIds = $this->actionService->localizeRecord(self::TABLE_Page, self::VALUE_PageId, self::VALUE_LanguageId);
        $this->recordIds['localizedPageId'] = $localizedTableIds[self::TABLE_Page][self::VALUE_PageId];
        $this->actionService->modifyRecord(self::TABLE_Page, $this->recordIds['localizedPageId'], ['title' => 'Testing #1']);
        $record = $this->getConnectionPool()->getConnectionForTable(self::TABLE_Page)
            ->select(['*'], self::TABLE_Page, ['uid' => $this->recordIds['localizedPageId']])
            ->fetchAssociative();
        // cleanup some fields from record
        unset($record['uid'], $record['pid'], $record['l10n_diffsource']);
        $record['l10n_state'] = \json_decode($record['l10n_state']);
        // modify record
        $modifiedRecord = array_replace($record, ['title' => 'Testing #2']);
        $this->actionService->modifyRecord(self::TABLE_Page, $this->recordIds['localizedPageId'], $modifiedRecord);
    }

    public function localizePageWithLanguageSynchronization(): void
    {
        unset($GLOBALS['TCA'][self::TABLE_Page]['columns']['title']['l10n_mode']);
        $GLOBALS['TCA'][self::TABLE_Page]['columns']['title']['config']['behaviour']['allowLanguageSynchronization'] = true;
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);
        $localizedTableIds = $this->actionService->localizeRecord(self::TABLE_Page, self::VALUE_PageId, self::VALUE_LanguageId);
        $this->recordIds['localizedPageId'] = $localizedTableIds[self::TABLE_Page][self::VALUE_PageId];
        $this->actionService->modifyRecord(self::TABLE_Page, self::VALUE_PageId, ['title' => 'Testing #1']);
    }

    public function localizePageAndContentsAndDeletePageLocalization(): void
    {
        $localizedTableIds = $this->actionService->localizeRecord(self::TABLE_Page, self::VALUE_PageId, self::VALUE_LanguageId);
        $this->recordIds['localizedPageId'] = $localizedTableIds[self::TABLE_Page][self::VALUE_PageId];
        $localizedTableIds = $this->actionService->localizeRecord(self::TABLE_Content, self::VALUE_ContentIdSecond, self::VALUE_LanguageId);
        $this->recordIds['localizedContentId'] = $localizedTableIds[self::TABLE_Content][self::VALUE_ContentIdSecond];
        // Deleting the localized page should also delete its localized records
        $this->actionService->deleteRecord(self::TABLE_Page, $this->recordIds['localizedPageId']);
    }

    public function localizeNestedPagesAndContents(): void
    {
        $localizedTableIds = $this->actionService->localizeRecord(self::TABLE_Page, self::VALUE_PageIdParent, self::VALUE_LanguageId);
        $this->recordIds['localizedParentPageId'] = $localizedTableIds[self::TABLE_Page][self::VALUE_PageIdParent];
        $localizedTableIds = $this->actionService->localizeRecord(self::TABLE_Content, self::VALUE_ContentIdParent, self::VALUE_LanguageId);
        $this->recordIds['localizedParentContentId'] = $localizedTableIds[self::TABLE_Content][self::VALUE_ContentIdParent];
        $localizedTableIds = $this->actionService->localizeRecord(self::TABLE_Page, self::VALUE_PageId, self::VALUE_LanguageId);
        $this->recordIds['localizedPageId'] = $localizedTableIds[self::TABLE_Page][self::VALUE_PageId];
        $localizedTableIds = $this->actionService->localizeRecord(self::TABLE_Content, self::VALUE_ContentIdSecond, self::VALUE_LanguageId);
        $this->recordIds['localizedContentId'] = $localizedTableIds[self::TABLE_Content][self::VALUE_ContentIdSecond];
        // Have another localized content element on page 88 to verify it's translation is also properly discarded in workspaces
        $this->actionService->copyRecord(self::TABLE_Content, self::VALUE_ContentIdParent, self::VALUE_PageIdParent);
    }

    public function localizePageNotHiddenHideAtCopyFalse(): void
    {
        $GLOBALS['TCA'][self::TABLE_Page]['ctrl']['hideAtCopy'] = false;
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);

        $localizedTableIds = $this->actionService->localizeRecord(self::TABLE_Page, self::VALUE_PageId, self::VALUE_LanguageId);
        $this->recordIds['localizedPageId'] = $localizedTableIds[self::TABLE_Page][self::VALUE_PageId];
    }

    public function localizePageHiddenHideAtCopyFalse(): void
    {
        $GLOBALS['TCA'][self::TABLE_Page]['ctrl']['hideAtCopy'] = false;
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);

        // @todo Add hidden page to importDefault.csv to make this database change superfluous.
        $this->getConnectionPool()->getConnectionForTable(self::TABLE_Page)->update(
            self::TABLE_Page,
            [
                'hidden' => 1,
            ],
            [
                'uid' => self::VALUE_PageId,
            ]
        );

        $localizedTableIds = $this->actionService->localizeRecord(self::TABLE_Page, self::VALUE_PageId, self::VALUE_LanguageId);
        $this->recordIds['localizedPageId'] = $localizedTableIds[self::TABLE_Page][self::VALUE_PageId];
    }

    public function localizePageNotHiddenHideAtCopyDisableHideAtCopyUnset(): void
    {
        // This is the default, but set it to be expressive for this test.
        $GLOBALS['TCA'][self::TABLE_Page]['ctrl']['hideAtCopy'] = true;
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);

        $localizedTableIds = $this->actionService->localizeRecord(self::TABLE_Page, self::VALUE_PageId, self::VALUE_LanguageId);
        $this->recordIds['localizedPageId'] = $localizedTableIds[self::TABLE_Page][self::VALUE_PageId];
    }

    public function localizePageHiddenHideAtCopyDisableHideAtCopyUnset(): void
    {
        // This is the default, but set it to be expressive for this test.
        $GLOBALS['TCA'][self::TABLE_Page]['ctrl']['hideAtCopy'] = true;
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);

        // @todo Add hidden page to importDefault.csv to make this database change superfluous.
        $this->getConnectionPool()->getConnectionForTable(self::TABLE_Page)->update(
            self::TABLE_Page,
            [
                'hidden' => 1,
            ],
            [
                'uid' => self::VALUE_PageId,
            ]
        );

        $localizedTableIds = $this->actionService->localizeRecord(self::TABLE_Page, self::VALUE_PageId, self::VALUE_LanguageId);
        $this->recordIds['localizedPageId'] = $localizedTableIds[self::TABLE_Page][self::VALUE_PageId];
    }

    public function localizePageNotHiddenHideAtCopyDisableHideAtCopySetToFalse(): void
    {
        // This is the default, but set it to be expressive for this test.
        $GLOBALS['TCA'][self::TABLE_Page]['ctrl']['hideAtCopy'] = true;
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);

        $this->getConnectionPool()->getConnectionForTable(self::TABLE_Page)->update(
            self::TABLE_Page,
            [
                'TSconfig' => 'TCEMAIN.table.pages.disableHideAtCopy = 0',
            ],
            [
                'uid' => self::VALUE_PageIdWebsite,
            ]
        );

        $localizedTableIds = $this->actionService->localizeRecord(self::TABLE_Page, self::VALUE_PageId, self::VALUE_LanguageId);
        $this->recordIds['localizedPageId'] = $localizedTableIds[self::TABLE_Page][self::VALUE_PageId];
    }

    public function localizePageHiddenHideAtCopyDisableHideAtCopySetToFalse(): void
    {
        // This is the default, but set it to be expressive for this test.
        $GLOBALS['TCA'][self::TABLE_Page]['ctrl']['hideAtCopy'] = true;
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);

        // @todo Add hidden page to importDefault.csv to make this database change superfluous.
        $this->getConnectionPool()->getConnectionForTable(self::TABLE_Page)->update(
            self::TABLE_Page,
            [
                'hidden' => 1,
            ],
            [
                'uid' => self::VALUE_PageId,
            ]
        );
        $this->getConnectionPool()->getConnectionForTable(self::TABLE_Page)->update(
            self::TABLE_Page,
            [
                'TSconfig' => 'TCEMAIN.table.pages.disableHideAtCopy = 0',
            ],
            [
                'uid' => self::VALUE_PageIdWebsite,
            ]
        );

        $localizedTableIds = $this->actionService->localizeRecord(self::TABLE_Page, self::VALUE_PageId, self::VALUE_LanguageId);
        $this->recordIds['localizedPageId'] = $localizedTableIds[self::TABLE_Page][self::VALUE_PageId];
    }

    public function localizePageNotHiddenHideAtCopyDisableHideAtCopySetToTrue(): void
    {
        // This is the default, but set it to be expressive for this test.
        $GLOBALS['TCA'][self::TABLE_Page]['ctrl']['hideAtCopy'] = true;
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);

        $this->getConnectionPool()->getConnectionForTable(self::TABLE_Page)->update(
            self::TABLE_Page,
            [
                'TSconfig' => 'TCEMAIN.table.pages.disableHideAtCopy = 1',
            ],
            [
                'uid' => self::VALUE_PageIdWebsite,
            ]
        );
        $GLOBALS['TCA'][self::TABLE_Page]['ctrl']['hideAtCopy'] = true;
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);
        $localizedTableIds = $this->actionService->localizeRecord(self::TABLE_Page, self::VALUE_PageId, self::VALUE_LanguageId);
        $this->recordIds['localizedPageId'] = $localizedTableIds[self::TABLE_Page][self::VALUE_PageId];
    }

    public function localizePageHiddenHideAtCopyDisableHideAtCopySetToTrue(): void
    {
        // This is the default, but set it to be expressive for this test.
        $GLOBALS['TCA'][self::TABLE_Page]['ctrl']['hideAtCopy'] = true;
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);

        // @todo Add hidden page to importDefault.csv to make this database change superfluous.
        $this->getConnectionPool()->getConnectionForTable(self::TABLE_Page)->update(
            self::TABLE_Page,
            [
                'hidden' => 1,
            ],
            [
                'uid' => self::VALUE_PageId,
            ]
        );
        $this->getConnectionPool()->getConnectionForTable(self::TABLE_Page)->update(
            self::TABLE_Page,
            [
                'TSconfig' => 'TCEMAIN.table.pages.disableHideAtCopy = 1',
            ],
            [
                'uid' => self::VALUE_PageIdWebsite,
            ]
        );

        $localizedTableIds = $this->actionService->localizeRecord(self::TABLE_Page, self::VALUE_PageId, self::VALUE_LanguageId);
        $this->recordIds['localizedPageId'] = $localizedTableIds[self::TABLE_Page][self::VALUE_PageId];
    }

    /**
     * Create page localization, then move default language page to different pages twice.
     * Verifies the page localization is moved together with the default language page.
     * In workspaces, the page localization will be a "new" overlay that is moved around.
     */
    public function movePageLocalizedToDifferentPageTwice(): void
    {
        // Localize page first. In workspaces, this localization is created within ws, creating a "new" t3ver_state=1 record
        $this->actionService->localizeRecord(self::TABLE_Page, self::VALUE_PageId, self::VALUE_LanguageId);
        $this->actionService->moveRecord(self::TABLE_Page, self::VALUE_PageId, self::VALUE_PageIdTarget);
        $this->actionService->moveRecord(self::TABLE_Page, self::VALUE_PageId, self::VALUE_PageIdWebsite);
    }

    /**
     * Create page localization in live, then move default language page in workspaces to different pages twice.
     * Verifies the page localization is moved together with the default language page.
     * This should create "move" an overlay for the localization.
     *
     * No ext:core implementation of this test since it is identical with
     * moveLocalizedPageToDifferentPageTwice() in non-workspace
     */
    public function movePageLocalizedInLiveToDifferentPageTwice(): void
    {
        $this->setWorkspaceId(0);
        $this->actionService->localizeRecord(self::TABLE_Page, self::VALUE_PageId, self::VALUE_LanguageId);
        $this->setWorkspaceId(static::VALUE_WorkspaceId);
        $this->actionService->moveRecord(self::TABLE_Page, self::VALUE_PageId, self::VALUE_PageIdTarget);
        $this->actionService->moveRecord(self::TABLE_Page, self::VALUE_PageId, self::VALUE_PageIdWebsite);
    }

    /**
     * Create page localization in live, then change the localization in workspace,
     * then move default language page in workspaces to different pages twice.
     * Verifies the page localization is moved together with the default language page, and that the
     * "changed" t3ver_state=0 record is turned into a move placeholder when default language page is moved.
     *
     * No ext:core implementation of this test since it is identical with
     * moveLocalizedPageToDifferentPageTwice() in non-workspace
     */
    public function movePageLocalizedInLiveWorkspaceChangedToDifferentPageTwice(): void
    {
        $this->setWorkspaceId(0);
        $localizedTableIds = $this->actionService->localizeRecord(self::TABLE_Page, self::VALUE_PageId, self::VALUE_LanguageId);
        $this->recordIds['localizedPageId'] = $localizedTableIds[self::TABLE_Page][self::VALUE_PageId];
        $this->setWorkspaceId(static::VALUE_WorkspaceId);
        $this->actionService->modifyRecord(self::TABLE_Page, $this->recordIds['localizedPageId'], ['title' => 'Testing #1']);
        $this->actionService->moveRecord(self::TABLE_Page, self::VALUE_PageId, self::VALUE_PageIdTarget);
        $this->actionService->moveRecord(self::TABLE_Page, self::VALUE_PageId, self::VALUE_PageIdWebsite);
    }

    /**
     * Create page localization in live, then delete the localization in workspace,
     * then move default language page in workspaces to different pages twice.
     * Verifies the page localization is moved together with the default language page.
     *
     * @todo The "deleted" t3ver_state=2 record is turned into a move placeholder so the "marked for delete" information is lost.
     *
     * No ext:core implementation of this test since it is identical with
     * moveLocalizedPageToDifferentPageTwice() in non-workspace
     */
    public function movePageLocalizedInLiveWorkspaceDeletedToDifferentPageTwice(): void
    {
        $this->setWorkspaceId(0);
        $localizedTableIds = $this->actionService->localizeRecord(self::TABLE_Page, self::VALUE_PageId, self::VALUE_LanguageId);
        $this->recordIds['localizedPageId'] = $localizedTableIds[self::TABLE_Page][self::VALUE_PageId];
        $this->setWorkspaceId(static::VALUE_WorkspaceId);
        $this->actionService->deleteRecord(self::TABLE_Page, $this->recordIds['localizedPageId']);
        $this->actionService->moveRecord(self::TABLE_Page, self::VALUE_PageId, self::VALUE_PageIdTarget);
        $this->actionService->moveRecord(self::TABLE_Page, self::VALUE_PageId, self::VALUE_PageIdWebsite);
    }
}
