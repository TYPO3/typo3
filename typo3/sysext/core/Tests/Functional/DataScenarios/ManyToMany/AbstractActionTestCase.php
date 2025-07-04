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

namespace TYPO3\CMS\Core\Tests\Functional\DataScenarios\ManyToMany;

use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Tests\Functional\DataScenarios\AbstractDataHandlerActionTestCase;
use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;

abstract class AbstractActionTestCase extends AbstractDataHandlerActionTestCase
{
    use SiteBasedTestTrait;

    protected const VALUE_PageId = 89;
    protected const VALUE_PageIdTarget = 90;
    protected const VALUE_ContentIdFirst = 297;
    protected const VALUE_ContentIdLast = 298;
    protected const VALUE_LanguageId = 1;
    protected const VALUE_LanguageIdSecond = 2;
    protected const VALUE_TestMMIdFirst = 28;
    protected const VALUE_TestMMIdSecond = 29;
    protected const VALUE_TestMMIdThird = 30;
    protected const VALUE_TestMMIdFourth = 31;

    protected const TABLE_Page = 'pages';
    protected const TABLE_Content = 'tt_content';
    protected const TABLE_TEST_MM = 'tx_test_mm';
    protected const TABLE_GROUP_1_ManyToMany = 'group_mm_1_relations_mm';

    protected const FIELD_GROUP_MM_1_LOCAL = 'group_mm_1_local';

    protected const FIELD_SELECT_MM_1_LOCAL = 'select_mm_1_local';

    protected const FIELD_GROUP_MM_1_FOREIGN = 'group_mm_1_foreign';

    protected const FIELD_SELECT_MM_1_FOREIGN = 'select_mm_1_foreign';

    protected const SCENARIO_DataSet = __DIR__ . '/DataSet/ImportDefault.csv';

    protected array $testExtensionsToLoad = [
        'typo3/sysext/core/Tests/Functional/Fixtures/Extensions/test_mm',
    ];

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

    public function addGroupMM1RelationOnForeignSide(): void
    {
        $this->actionService->modifyReferences(
            self::TABLE_Content,
            self::VALUE_ContentIdFirst,
            self::FIELD_GROUP_MM_1_FOREIGN,
            [self::VALUE_TestMMIdFirst, self::VALUE_TestMMIdSecond, 31]
        );
    }

    public function createTestMMAndAddGroupMM1Relation(): void
    {
        $newTableIds = $this->actionService->createNewRecord(
            self::TABLE_TEST_MM,
            0,
            ['title' => 'Surfing #1', self::FIELD_GROUP_MM_1_LOCAL => 'tt_content_' . self::VALUE_ContentIdFirst]
        );
        $this->recordIds['newGroupMM1Id'] = $newTableIds[self::TABLE_TEST_MM][0];
    }

    public function createTestMMAndContentWithGroupMM1Relation(): void
    {
        $newTableIds = $this->actionService->createNewRecords(
            self::VALUE_PageId,
            [
                self::TABLE_TEST_MM => ['pid' => 0, 'title' => 'Surfing #1'],
                self::TABLE_Content => ['header' => 'Surfing #1', self::FIELD_GROUP_MM_1_FOREIGN => '__previousUid'],
            ]
        );
        $this->recordIds['newGroupMM1Id'] = $newTableIds[self::TABLE_TEST_MM][0];
        $this->recordIds['newContentId'] = $newTableIds[self::TABLE_Content][0];
    }

    public function createTestMMAndContentWithAddedGroupMM1Relation(): void
    {
        $newTableIds = $this->actionService->createNewRecords(
            self::VALUE_PageId,
            [
                self::TABLE_TEST_MM => ['pid' => 0, 'title' => 'Surfing #1'],
                self::TABLE_Content => ['header' => 'Surfing #1'],
            ]
        );
        $this->recordIds['newGroupMM1Id'] = $newTableIds[self::TABLE_TEST_MM][0];
        $this->recordIds['newContentId'] = $newTableIds[self::TABLE_Content][0];

        $this->actionService->modifyRecord(
            self::TABLE_Content,
            $this->recordIds['newContentId'],
            [self::FIELD_GROUP_MM_1_FOREIGN => $this->recordIds['newGroupMM1Id']]
        );
    }

    public function createContentAndAddGroupMM1Relation(): void
    {
        $newTableIds = $this->actionService->createNewRecord(
            self::TABLE_Content,
            self::VALUE_PageId,
            ['header' => 'Surfing #1', self::FIELD_GROUP_MM_1_FOREIGN => self::VALUE_TestMMIdSecond]
        );
        $this->recordIds['newContentId'] = $newTableIds[self::TABLE_Content][0];
    }

    public function createContentAndTestMMWithAddedGroupMM1Relation(): void
    {
        $newTableIds = $this->actionService->createNewRecords(
            self::VALUE_PageId,
            [
                self::TABLE_Content => ['header' => 'Surfing #1'],
                self::TABLE_TEST_MM => ['pid' => 0, 'title' => 'Surfing #1', self::FIELD_GROUP_MM_1_LOCAL => 'tt_content___previousUid'],
            ]
        );
        $this->recordIds['newContentId'] = $newTableIds[self::TABLE_Content][0];
        $this->recordIds['newGroupMM1Id'] = $newTableIds[self::TABLE_TEST_MM][0];

        $this->actionService->modifyRecord(
            self::TABLE_TEST_MM,
            $this->recordIds['newGroupMM1Id'],
            [self::FIELD_GROUP_MM_1_LOCAL => 'tt_content_' . $this->recordIds['newContentId']]
        );
    }

    public function createContentAndTestMMWithGroupMM1Relation(): void
    {
        $newTableIds = $this->actionService->createNewRecords(
            self::VALUE_PageId,
            [
                self::TABLE_Content => ['header' => 'Surfing #1'],
                self::TABLE_TEST_MM => ['pid' => 0, 'title' => 'Surfing #1', self::FIELD_GROUP_MM_1_LOCAL => 'tt_content___previousUid'],
            ]
        );
        $this->recordIds['newContentId'] = $newTableIds[self::TABLE_Content][0];
        $this->recordIds['newGroupMM1Id'] = $newTableIds[self::TABLE_TEST_MM][0];
    }

    public function deleteGroupMM1RelationOnForeignSide(): void
    {
        $this->actionService->modifyReferences(
            self::TABLE_Content,
            self::VALUE_ContentIdFirst,
            self::FIELD_GROUP_MM_1_FOREIGN,
            [self::VALUE_TestMMIdFirst]
        );
    }

    public function changeGroupMM1SortingOnForeignSide(): void
    {
        $this->actionService->modifyReferences(
            self::TABLE_Content,
            self::VALUE_ContentIdFirst,
            self::FIELD_GROUP_MM_1_FOREIGN,
            [self::VALUE_TestMMIdSecond, self::VALUE_TestMMIdFirst]
        );
    }

    public function modifyTestMM(): void
    {
        $this->actionService->modifyRecord(self::TABLE_TEST_MM, self::VALUE_TestMMIdFirst, ['title' => 'Surfing #1']);
    }

    public function modifyContent(): void
    {
        $this->actionService->modifyRecord(self::TABLE_Content, self::VALUE_ContentIdFirst, ['header' => 'Surfing #1']);
    }

    public function modifyTestMMAndContent(): void
    {
        $this->actionService->modifyRecord(self::TABLE_TEST_MM, self::VALUE_TestMMIdFirst, ['title' => 'Surfing #1']);
        $this->actionService->modifyRecord(self::TABLE_Content, self::VALUE_ContentIdFirst, ['header' => 'Surfing #1']);
    }

    public function deleteContentWithMultipleRelations(): void
    {
        $this->actionService->deleteRecord(self::TABLE_Content, self::VALUE_ContentIdLast);
    }

    public function deleteContentWithMultipleRelationsAndWithoutSoftDelete(): void
    {
        unset($GLOBALS['TCA'][self::TABLE_Content]['ctrl']['delete']);
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);
        $newTableIds = $this->actionService->deleteRecord(self::TABLE_Content, self::VALUE_ContentIdLast);
        // Usually this is the record ID itself, but when in a workspace, the ID is the one from the versioned record
        $this->recordIds['deletedRecordId'] = $newTableIds[self::TABLE_Content][self::VALUE_ContentIdLast] ?? self::VALUE_ContentIdLast;
    }

    public function deleteTestMM(): void
    {
        $this->actionService->deleteRecord(self::TABLE_TEST_MM, self::VALUE_TestMMIdFirst);
    }

    public function copyContentWithRelations(): void
    {
        $newTableIds = $this->actionService->copyRecord(self::TABLE_Content, self::VALUE_ContentIdLast, self::VALUE_PageId);
        $this->recordIds['newContentId'] = $newTableIds[self::TABLE_Content][self::VALUE_ContentIdLast];
    }

    public function copyTestMM(): void
    {
        $newTableIds = $this->actionService->copyRecord(self::TABLE_TEST_MM, self::VALUE_TestMMIdFirst, 0);
        $this->recordIds['newGroupMM1Id'] = $newTableIds[self::TABLE_TEST_MM][self::VALUE_TestMMIdFirst];
    }

    public function copyContentToLanguage(): void
    {
        $newTableIds = $this->actionService->copyRecordToLanguage(self::TABLE_Content, self::VALUE_ContentIdLast, self::VALUE_LanguageId);
        $this->recordIds['newContentId'] = $newTableIds[self::TABLE_Content][self::VALUE_ContentIdLast];
    }

    /**
     * @todo: This action does not copy the relations with it (at least in workspaces), and should be re-evaluated
     */
    public function copyTestMMToLanguage(): void
    {
        $newTableIds = $this->actionService->copyRecordToLanguage(self::TABLE_TEST_MM, self::VALUE_TestMMIdFirst, self::VALUE_LanguageId);
        $this->recordIds['newGroupMM1Id'] = $newTableIds[self::TABLE_TEST_MM][self::VALUE_TestMMIdFirst];
    }

    public function localizeContent(): void
    {
        $localizedTableIds = $this->actionService->localizeRecord(self::TABLE_Content, self::VALUE_ContentIdLast, self::VALUE_LanguageId);
        $this->recordIds['localizedContentId'] = $localizedTableIds[self::TABLE_Content][self::VALUE_ContentIdLast];
    }

    public function localizeContentWithLanguageSynchronization(): void
    {
        $GLOBALS['TCA'][self::TABLE_Content]['columns'][self::FIELD_GROUP_MM_1_FOREIGN]['config']['behaviour']['allowLanguageSynchronization'] = true;
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);
        $localizedTableIds = $this->actionService->localizeRecord(self::TABLE_Content, self::VALUE_ContentIdLast, self::VALUE_LanguageId);
        $this->recordIds['localizedContentId'] = $localizedTableIds[self::TABLE_Content][self::VALUE_ContentIdLast];
    }

    public function localizeContentWithLanguageExclude(): void
    {
        $GLOBALS['TCA'][self::TABLE_Content]['columns'][self::FIELD_GROUP_MM_1_FOREIGN]['config']['l10n_mode'] = 'exclude';
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);
        $localizedTableIds = $this->actionService->localizeRecord(self::TABLE_Content, self::VALUE_ContentIdLast, self::VALUE_LanguageId);
        $this->recordIds['localizedContentId'] = $localizedTableIds[self::TABLE_Content][self::VALUE_ContentIdLast];
    }

    public function localizeContentAndAddTestMMWithLanguageSynchronization(): void
    {
        $GLOBALS['TCA'][self::TABLE_Content]['columns'][self::FIELD_GROUP_MM_1_FOREIGN]['config']['behaviour']['allowLanguageSynchronization'] = true;
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);
        $localizedTableIds = $this->actionService->localizeRecord(self::TABLE_Content, self::VALUE_ContentIdLast, self::VALUE_LanguageId);
        $this->recordIds['localizedContentId'] = $localizedTableIds[self::TABLE_Content][self::VALUE_ContentIdLast];
        $this->actionService->modifyReferences(
            self::TABLE_Content,
            self::VALUE_ContentIdLast,
            self::FIELD_GROUP_MM_1_FOREIGN,
            [self::VALUE_TestMMIdSecond, self::VALUE_TestMMIdThird, self::VALUE_TestMMIdFourth]
        );
    }

    public function localizeContentChainAndAddTestMMWithLanguageSynchronization(): void
    {
        $GLOBALS['TCA'][self::TABLE_Content]['columns'][self::FIELD_GROUP_MM_1_FOREIGN]['config']['behaviour']['allowLanguageSynchronization'] = true;
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);
        $localizedTableIds = $this->actionService->localizeRecord(self::TABLE_Content, self::VALUE_ContentIdLast, self::VALUE_LanguageId);
        $this->recordIds['localizedContentId'] = $localizedTableIds[self::TABLE_Content][self::VALUE_ContentIdLast];
        $localizedTableIds = $this->actionService->localizeRecord(self::TABLE_Content, $this->recordIds['localizedContentId'], self::VALUE_LanguageIdSecond);
        $this->recordIds['localizedContentIdSecond'] = $localizedTableIds[self::TABLE_Content][$this->recordIds['localizedContentId']];
        $this->actionService->modifyRecord(
            self::TABLE_Content,
            $this->recordIds['localizedContentIdSecond'],
            ['l10n_state' => [self::FIELD_GROUP_MM_1_FOREIGN => 'source']]
        );
        $this->actionService->modifyReferences(
            self::TABLE_Content,
            self::VALUE_ContentIdLast,
            self::FIELD_GROUP_MM_1_FOREIGN,
            [self::VALUE_TestMMIdSecond, self::VALUE_TestMMIdThird, self::VALUE_TestMMIdFourth]
        );
    }

    public function localizeTestMM(): void
    {
        $localizedTableIds = $this->actionService->localizeRecord(self::TABLE_TEST_MM, self::VALUE_TestMMIdFirst, self::VALUE_LanguageId);
        $this->recordIds['localizedSurfId'] = $localizedTableIds[self::TABLE_TEST_MM][self::VALUE_TestMMIdFirst];
    }

    public function localizeTestMMSelect1MMLocal(): void
    {
        $localizedTableIds = $this->actionService->localizeRecord(self::TABLE_TEST_MM, self::VALUE_TestMMIdThird, self::VALUE_LanguageId);
        $this->recordIds['localizedSurfId'] = $localizedTableIds[self::TABLE_TEST_MM][self::VALUE_TestMMIdThird];
    }

    public function localizeTestMMSelect1MMLocalWithExclude(): void
    {
        $GLOBALS['TCA'][self::TABLE_TEST_MM]['columns'][self::FIELD_SELECT_MM_1_LOCAL]['config']['l10n_mode'] = 'exclude';
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);
        $localizedTableIds = $this->actionService->localizeRecord(self::TABLE_TEST_MM, self::VALUE_TestMMIdThird, self::VALUE_LanguageId);
        $this->recordIds['localizedSurfId'] = $localizedTableIds[self::TABLE_TEST_MM][self::VALUE_TestMMIdThird];
    }

    public function localizeTestMMSelect1MMLocalWithLanguageSynchronization(): void
    {
        $GLOBALS['TCA'][self::TABLE_TEST_MM]['columns'][self::FIELD_SELECT_MM_1_LOCAL]['config']['behaviour']['allowLanguageSynchronization'] = true;
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);
        $localizedTableIds = $this->actionService->localizeRecord(self::TABLE_TEST_MM, self::VALUE_TestMMIdThird, self::VALUE_LanguageId);
        $this->recordIds['localizedSurfId'] = $localizedTableIds[self::TABLE_TEST_MM][self::VALUE_TestMMIdThird];
    }

    public function localizeContentSelect1MMForeign(): void
    {
        $localizedTableIds = $this->actionService->localizeRecord(self::TABLE_Content, self::VALUE_ContentIdLast, self::VALUE_LanguageId);
        $this->recordIds['localizedContentId'] = $localizedTableIds[self::TABLE_Content][self::VALUE_ContentIdLast];
    }

    public function localizeContentSelect1MMForeignWithExclude(): void
    {
        $GLOBALS['TCA'][self::TABLE_Content]['columns'][self::FIELD_SELECT_MM_1_FOREIGN]['config']['l10n_mode'] = 'exclude';
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);
        $localizedTableIds = $this->actionService->localizeRecord(self::TABLE_Content, self::VALUE_ContentIdLast, self::VALUE_LanguageId);
        $this->recordIds['localizedContentId'] = $localizedTableIds[self::TABLE_Content][self::VALUE_ContentIdLast];
    }

    public function localizeContentSelect1MMForeignWithLanguageSynchronization(): void
    {
        $GLOBALS['TCA'][self::TABLE_Content]['columns'][self::FIELD_SELECT_MM_1_FOREIGN]['config']['behaviour']['allowLanguageSynchronization'] = true;
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);
        $localizedTableIds = $this->actionService->localizeRecord(self::TABLE_Content, self::VALUE_ContentIdLast, self::VALUE_LanguageId);
        $this->recordIds['localizedContentId'] = $localizedTableIds[self::TABLE_Content][self::VALUE_ContentIdLast];
    }

    public function moveContentToDifferentPage(): void
    {
        $this->actionService->moveRecord(self::TABLE_Content, self::VALUE_ContentIdLast, self::VALUE_PageIdTarget);
    }

    public function copyPage(): void
    {
        $newTableIds = $this->actionService->copyRecord(self::TABLE_Page, self::VALUE_PageId, self::VALUE_PageIdTarget);
        $this->recordIds['newPageId'] = $newTableIds[self::TABLE_Page][self::VALUE_PageId];
        $this->recordIds['newContentIdFirst'] = $newTableIds[self::TABLE_Content][self::VALUE_ContentIdFirst];
        $this->recordIds['newContentIdLast'] = $newTableIds[self::TABLE_Content][self::VALUE_ContentIdLast];
    }
}
