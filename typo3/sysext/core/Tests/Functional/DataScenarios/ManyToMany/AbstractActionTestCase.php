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
    protected const VALUE_SurfIdFirst = 28;
    protected const VALUE_SurfIdSecond = 29;
    protected const VALUE_SurfIdThird = 30;
    protected const VALUE_SurfIdFourth = 31;

    protected const TABLE_Page = 'pages';
    protected const TABLE_Content = 'tt_content';
    protected const TABLE_Surf = 'tx_test_mm_surf';
    protected const TABLE_ContentSurf_ManyToMany = 'surf_content_mm';

    protected const FIELD_Relations = 'relations';

    protected const FIELD_Posts = 'posts';

    protected const FIELD_Surfing = 'surfing';

    protected const FIELD_Surfers = 'surfers';

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

    public function addSurfRelation(): void
    {
        $this->actionService->modifyReferences(
            self::TABLE_Content,
            self::VALUE_ContentIdFirst,
            self::FIELD_Surfing,
            [self::VALUE_SurfIdFirst, self::VALUE_SurfIdSecond, 31]
        );
    }

    public function createSurfAndAddRelation(): void
    {
        $newTableIds = $this->actionService->createNewRecord(
            self::TABLE_Surf,
            0,
            ['title' => 'Surfing #1', self::FIELD_Relations => 'tt_content_' . self::VALUE_ContentIdFirst]
        );
        $this->recordIds['newSurfId'] = $newTableIds[self::TABLE_Surf][0];
    }

    public function deleteSurfRelation(): void
    {
        $this->actionService->modifyReferences(
            self::TABLE_Content,
            self::VALUE_ContentIdFirst,
            self::FIELD_Surfing,
            [self::VALUE_SurfIdFirst]
        );
    }

    public function changeSurfRelationSorting(): void
    {
        $this->actionService->modifyReferences(
            self::TABLE_Content,
            self::VALUE_ContentIdFirst,
            self::FIELD_Surfing,
            [self::VALUE_SurfIdSecond, self::VALUE_SurfIdFirst]
        );
    }

    public function modifySurfOfRelation(): void
    {
        $this->actionService->modifyRecord(self::TABLE_Surf, self::VALUE_SurfIdFirst, ['title' => 'Surfing #1']);
    }

    public function modifyContentOfRelation(): void
    {
        $this->actionService->modifyRecord(self::TABLE_Content, self::VALUE_ContentIdFirst, ['header' => 'Surfing #1']);
    }

    public function modifyBothsOfRelation(): void
    {
        $this->actionService->modifyRecord(self::TABLE_Surf, self::VALUE_SurfIdFirst, ['title' => 'Surfing #1']);
        $this->actionService->modifyRecord(self::TABLE_Content, self::VALUE_ContentIdFirst, ['header' => 'Surfing #1']);
    }

    public function deleteContentOfRelation(): void
    {
        $this->actionService->deleteRecord(self::TABLE_Content, self::VALUE_ContentIdLast);
    }

    public function deleteContentOfRelationWithoutSoftDelete(): void
    {
        unset($GLOBALS['TCA'][self::TABLE_Content]['ctrl']['delete']);
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);
        $newTableIds = $this->actionService->deleteRecord(self::TABLE_Content, self::VALUE_ContentIdLast);
        // Usually this is the record ID itself, but when in a workspace, the ID is the one from the versioned record
        $this->recordIds['deletedRecordId'] = $newTableIds[self::TABLE_Content][self::VALUE_ContentIdLast] ?? self::VALUE_ContentIdLast;
    }

    public function deleteSurfOfRelation(): void
    {
        $this->actionService->deleteRecord(self::TABLE_Surf, self::VALUE_SurfIdFirst);
    }

    public function copyContentOfRelation(): void
    {
        $newTableIds = $this->actionService->copyRecord(self::TABLE_Content, self::VALUE_ContentIdLast, self::VALUE_PageId);
        $this->recordIds['newContentId'] = $newTableIds[self::TABLE_Content][self::VALUE_ContentIdLast];
    }

    public function copySurfOfRelation(): void
    {
        $newTableIds = $this->actionService->copyRecord(self::TABLE_Surf, self::VALUE_SurfIdFirst, 0);
        $this->recordIds['newSurfId'] = $newTableIds[self::TABLE_Surf][self::VALUE_SurfIdFirst];
    }

    /**
     * See DataSet/copyContentToLanguageOfRelation.csv
     */
    public function copyContentToLanguageOfRelation(): void
    {
        $newTableIds = $this->actionService->copyRecordToLanguage(self::TABLE_Content, self::VALUE_ContentIdLast, self::VALUE_LanguageId);
        $this->recordIds['newContentId'] = $newTableIds[self::TABLE_Content][self::VALUE_ContentIdLast];
    }

    /**
     * See DataSet/copySurfToLanguageOfRelation.csv
     * @todo: This action does not copy the relations with it (at least in workspaces), and should be re-evaluated
     */
    public function copySurfToLanguageOfRelation(): void
    {
        $newTableIds = $this->actionService->copyRecordToLanguage(self::TABLE_Surf, self::VALUE_SurfIdFirst, self::VALUE_LanguageId);
        $this->recordIds['newSurfId'] = $newTableIds[self::TABLE_Surf][self::VALUE_SurfIdFirst];
    }

    public function localizeContentOfRelation(): void
    {
        $localizedTableIds = $this->actionService->localizeRecord(self::TABLE_Content, self::VALUE_ContentIdLast, self::VALUE_LanguageId);
        $this->recordIds['localizedContentId'] = $localizedTableIds[self::TABLE_Content][self::VALUE_ContentIdLast];
    }

    public function localizeContentOfRelationWithLanguageSynchronization(): void
    {
        $GLOBALS['TCA'][self::TABLE_Content]['columns'][self::FIELD_Surfing]['config']['behaviour']['allowLanguageSynchronization'] = true;
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);
        $localizedTableIds = $this->actionService->localizeRecord(self::TABLE_Content, self::VALUE_ContentIdLast, self::VALUE_LanguageId);
        $this->recordIds['localizedContentId'] = $localizedTableIds[self::TABLE_Content][self::VALUE_ContentIdLast];
    }

    public function localizeContentOfRelationWithLanguageExclude(): void
    {
        $GLOBALS['TCA'][self::TABLE_Content]['columns'][self::FIELD_Surfing]['config']['l10n_mode'] = 'exclude';
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);
        $localizedTableIds = $this->actionService->localizeRecord(self::TABLE_Content, self::VALUE_ContentIdLast, self::VALUE_LanguageId);
        $this->recordIds['localizedContentId'] = $localizedTableIds[self::TABLE_Content][self::VALUE_ContentIdLast];
    }

    public function localizeContentOfRelationAndAddSurfWithLanguageSynchronization(): void
    {
        $GLOBALS['TCA'][self::TABLE_Content]['columns'][self::FIELD_Surfing]['config']['behaviour']['allowLanguageSynchronization'] = true;
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);
        $localizedTableIds = $this->actionService->localizeRecord(self::TABLE_Content, self::VALUE_ContentIdLast, self::VALUE_LanguageId);
        $this->recordIds['localizedContentId'] = $localizedTableIds[self::TABLE_Content][self::VALUE_ContentIdLast];
        $this->actionService->modifyReferences(
            self::TABLE_Content,
            self::VALUE_ContentIdLast,
            self::FIELD_Surfing,
            [self::VALUE_SurfIdSecond, self::VALUE_SurfIdThird, self::VALUE_SurfIdFourth]
        );
    }

    public function localizeContentChainOfRelationAndAddSurfWithLanguageSynchronization(): void
    {
        $GLOBALS['TCA'][self::TABLE_Content]['columns'][self::FIELD_Surfing]['config']['behaviour']['allowLanguageSynchronization'] = true;
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);
        $localizedTableIds = $this->actionService->localizeRecord(self::TABLE_Content, self::VALUE_ContentIdLast, self::VALUE_LanguageId);
        $this->recordIds['localizedContentId'] = $localizedTableIds[self::TABLE_Content][self::VALUE_ContentIdLast];
        $localizedTableIds = $this->actionService->localizeRecord(self::TABLE_Content, $this->recordIds['localizedContentId'], self::VALUE_LanguageIdSecond);
        $this->recordIds['localizedContentIdSecond'] = $localizedTableIds[self::TABLE_Content][$this->recordIds['localizedContentId']];
        $this->actionService->modifyRecord(
            self::TABLE_Content,
            $this->recordIds['localizedContentIdSecond'],
            ['l10n_state' => [self::FIELD_Surfing => 'source']]
        );
        $this->actionService->modifyReferences(
            self::TABLE_Content,
            self::VALUE_ContentIdLast,
            self::FIELD_Surfing,
            [self::VALUE_SurfIdSecond, self::VALUE_SurfIdThird, self::VALUE_SurfIdFourth]
        );
    }

    public function localizeSurfOfRelation(): void
    {
        $localizedTableIds = $this->actionService->localizeRecord(self::TABLE_Surf, self::VALUE_SurfIdFirst, self::VALUE_LanguageId);
        $this->recordIds['localizedSurfId'] = $localizedTableIds[self::TABLE_Surf][self::VALUE_SurfIdFirst];
    }

    public function localizeLocalDefaultSurfer(): void
    {
        $localizedTableIds = $this->actionService->localizeRecord(self::TABLE_Surf, self::VALUE_SurfIdThird, self::VALUE_LanguageId);
        $this->recordIds['localizedSurfId'] = $localizedTableIds[self::TABLE_Surf][self::VALUE_SurfIdThird];
    }

    public function localizeLocalDefaultSurferWithExclude(): void
    {
        $GLOBALS['TCA'][self::TABLE_Surf]['columns'][self::FIELD_Posts]['config']['l10n_mode'] = 'exclude';
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);
        $localizedTableIds = $this->actionService->localizeRecord(self::TABLE_Surf, self::VALUE_SurfIdThird, self::VALUE_LanguageId);
        $this->recordIds['localizedSurfId'] = $localizedTableIds[self::TABLE_Surf][self::VALUE_SurfIdThird];
    }

    public function localizeLocalDefaultSurferWithLanguageSynchronization(): void
    {
        $GLOBALS['TCA'][self::TABLE_Surf]['columns'][self::FIELD_Posts]['config']['behaviour']['allowLanguageSynchronization'] = true;
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);
        $localizedTableIds = $this->actionService->localizeRecord(self::TABLE_Surf, self::VALUE_SurfIdThird, self::VALUE_LanguageId);
        $this->recordIds['localizedSurfId'] = $localizedTableIds[self::TABLE_Surf][self::VALUE_SurfIdThird];
    }

    public function localizeForeignDefaultPost(): void
    {
        $localizedTableIds = $this->actionService->localizeRecord(self::TABLE_Content, self::VALUE_ContentIdLast, self::VALUE_LanguageId);
        $this->recordIds['localizedContentId'] = $localizedTableIds[self::TABLE_Content][self::VALUE_ContentIdLast];
    }

    public function localizeForeignDefaultPostWithExclude(): void
    {
        $GLOBALS['TCA'][self::TABLE_Content]['columns'][self::FIELD_Surfers]['config']['l10n_mode'] = 'exclude';
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);
        $localizedTableIds = $this->actionService->localizeRecord(self::TABLE_Content, self::VALUE_ContentIdLast, self::VALUE_LanguageId);
        $this->recordIds['localizedContentId'] = $localizedTableIds[self::TABLE_Content][self::VALUE_ContentIdLast];
    }

    public function localizeForeignDefaultPostWithLanguageSynchronization(): void
    {
        $GLOBALS['TCA'][self::TABLE_Content]['columns'][self::FIELD_Surfers]['config']['behaviour']['allowLanguageSynchronization'] = true;
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);
        $localizedTableIds = $this->actionService->localizeRecord(self::TABLE_Content, self::VALUE_ContentIdLast, self::VALUE_LanguageId);
        $this->recordIds['localizedContentId'] = $localizedTableIds[self::TABLE_Content][self::VALUE_ContentIdLast];
    }

    public function moveContentOfRelationToDifferentPage(): void
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
