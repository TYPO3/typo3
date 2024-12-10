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

namespace TYPO3\CMS\Core\Tests\Functional\DataScenarios\CategoryManyToMany;

use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Tests\Functional\DataScenarios\AbstractDataHandlerActionTestCase;
use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;

abstract class AbstractActionTestCase extends AbstractDataHandlerActionTestCase
{
    use SiteBasedTestTrait;

    protected const VALUE_PageId = 88;
    protected const VALUE_TargetPageId = 89;
    protected const VALUE_CategoryPageId = 0;
    protected const VALUE_ContentIdFirst = 297;
    protected const VALUE_ContentIdLast = 298;
    protected const VALUE_CategoryIdFirst = 28;
    protected const VALUE_CategoryIdSecond = 29;
    protected const VALUE_CategoryIdThird = 30;
    protected const VALUE_CategoryIdFourth = 31;

    protected const VALUE_LanguageId = 1;
    protected const VALUE_LanguageIdSecond = 2;

    protected const TABLE_Page = 'pages';
    protected const TABLE_Content = 'tt_content';
    protected const TABLE_Category = 'sys_category';

    protected const FIELD_Categories = 'categories';

    protected const SCENARIO_DataSet = __DIR__ . '/DataSet/ImportDefault.csv';

    protected function setUp(): void
    {
        parent::setUp();
        // Show copied pages records in frontend request
        $GLOBALS['TCA']['pages']['ctrl']['hideAtCopy'] = false;
        // Show copied tt_content records in frontend request
        $GLOBALS['TCA']['tt_content']['ctrl']['hideAtCopy'] = false;
        // Prepend label for localized sys_category records
        $GLOBALS['TCA']['sys_category']['columns']['title']['l10n_mode'] = 'prefixLangTitle';
        // Prepend label for copied sys_category records
        $GLOBALS['TCA']['sys_category']['ctrl']['prependAtCopy'] = 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.prependAtCopy';
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

    public function addCategoryRelation(): void
    {
        $this->actionService->modifyReferences(
            self::TABLE_Content,
            self::VALUE_ContentIdFirst,
            self::FIELD_Categories,
            [self::VALUE_CategoryIdFirst, self::VALUE_CategoryIdSecond, self::VALUE_CategoryIdFourth]
        );
    }

    public function addCategoryRelations(): void
    {
        $this->actionService->modifyReferences(
            self::TABLE_Content,
            self::VALUE_ContentIdLast,
            self::FIELD_Categories,
            [self::VALUE_CategoryIdSecond, self::VALUE_CategoryIdThird, self::VALUE_CategoryIdFourth, self::VALUE_CategoryIdFirst]
        );
    }

    public function createAndAddCategoryRelation(): void
    {
        $newTableIds = $this->actionService->createNewRecord(
            self::TABLE_Category,
            self::VALUE_CategoryPageId,
            [
                'title' => 'Category B.A',
                'parent' => self::VALUE_CategoryIdSecond,
            ]
        );

        $this->recordIds['newCategoryId'] = $newTableIds[self::TABLE_Category][0];

        $this->actionService->modifyReferences(
            self::TABLE_Content,
            self::VALUE_ContentIdFirst,
            self::FIELD_Categories,
            [self::VALUE_CategoryIdFirst, self::VALUE_CategoryIdSecond, $this->recordIds['newCategoryId']]
        );

        $this->actionService->modifyReferences(
            self::TABLE_Content,
            self::VALUE_ContentIdLast,
            self::FIELD_Categories,
            [self::VALUE_CategoryIdSecond, self::VALUE_CategoryIdThird, $this->recordIds['newCategoryId']]
        );
    }

    public function createAndReplaceCategoryRelation(): void
    {
        $newTableIds = $this->actionService->createNewRecord(
            self::TABLE_Category,
            self::VALUE_CategoryPageId,
            [
                'title' => 'Category B.A',
                'parent' => self::VALUE_CategoryIdSecond,
            ]
        );

        $this->recordIds['newCategoryId'] = $newTableIds[self::TABLE_Category][0];

        $this->actionService->modifyReferences(
            self::TABLE_Content,
            self::VALUE_ContentIdFirst,
            self::FIELD_Categories,
            [$this->recordIds['newCategoryId']]
        );
    }

    public function modifyReferencingContentElement(): void
    {
        $this->actionService->modifyRecord(
            self::TABLE_Content,
            self::VALUE_ContentIdFirst,
            ['header' => 'Testing #1']
        );
    }

    public function modifyContentOfRelatedCategory(): void
    {
        $this->actionService->modifyRecord(
            self::TABLE_Category,
            self::VALUE_CategoryIdSecond,
            ['title' => 'Testing #1']
        );
    }

    public function modifyBothOfRelation(): void
    {
        $this->actionService->modifyRecord(self::TABLE_Category, self::VALUE_CategoryIdFirst, ['title' => 'Testing #1']);
        $this->actionService->modifyRecord(self::TABLE_Content, self::VALUE_ContentIdFirst, ['header' => 'Testing #1']);
    }

    public function moveContentAndCategoryRelationToDifferentPage(): void
    {
        $this->actionService->moveRecord(self::TABLE_Content, self::VALUE_ContentIdFirst, self::VALUE_TargetPageId);
        $this->actionService->moveRecord(self::TABLE_Category, self::VALUE_CategoryIdFirst, self::VALUE_TargetPageId);
    }

    public function changeCategoryRelationSorting(): void
    {
        $this->actionService->modifyReferences(
            self::TABLE_Content,
            self::VALUE_ContentIdFirst,
            self::FIELD_Categories,
            [self::VALUE_CategoryIdSecond, self::VALUE_CategoryIdFirst]
        );
    }

    public function changeContentAndCategorySorting(): void
    {
        $this->actionService->moveRecord(self::TABLE_Content, self::VALUE_ContentIdLast, self::VALUE_PageId);
        $this->actionService->moveRecord(self::TABLE_Category, self::VALUE_CategoryIdFirst, -self::VALUE_CategoryIdSecond);
    }

    public function deleteCategoryRelation(): void
    {
        $this->actionService->modifyReferences(
            self::TABLE_Content,
            self::VALUE_ContentIdFirst,
            self::FIELD_Categories,
            [self::VALUE_CategoryIdFirst]
        );
    }

    public function deleteCategoryRelations(): void
    {
        $this->actionService->modifyReferences(
            self::TABLE_Content,
            self::VALUE_ContentIdFirst,
            self::FIELD_Categories,
            []
        );
    }

    public function deleteContentOfRelation(): void
    {
        $this->actionService->deleteRecord(self::TABLE_Content, self::VALUE_ContentIdLast);
    }

    public function copyContentOfRelation(): void
    {
        $newTableIds = $this->actionService->copyRecord(self::TABLE_Content, self::VALUE_ContentIdFirst, self::VALUE_PageId);
        $this->recordIds['newContentId'] = $newTableIds[self::TABLE_Content][self::VALUE_ContentIdFirst];
    }

    public function copyCategoryOfRelation(): void
    {
        $newTableIds = $this->actionService->copyRecord(self::TABLE_Category, self::VALUE_CategoryIdFirst, 0);
        $this->recordIds['newCategoryId'] = $newTableIds[self::TABLE_Category][self::VALUE_CategoryIdFirst];
    }

    /**
     * See DataSet/copyContentToLanguageOfRelation.csv
     */
    public function copyContentToLanguageOfRelation(): void
    {
        $newTableIds = $this->actionService->copyRecordToLanguage(self::TABLE_Content, self::VALUE_ContentIdFirst, self::VALUE_LanguageId);
        $this->recordIds['newContentId'] = $newTableIds[self::TABLE_Content][self::VALUE_ContentIdFirst];
    }

    /**
     * See DataSet/copyCategoryToLanguageOfRelation.csv
     * @todo: This action does not copy the relations with it (at least in workspaces), and should be re-evaluated
     */
    public function copyCategoryToLanguageOfRelation(): void
    {
        $newTableIds = $this->actionService->copyRecordToLanguage(self::TABLE_Category, self::VALUE_CategoryIdFirst, self::VALUE_LanguageId);
        $this->recordIds['newCategoryId'] = $newTableIds[self::TABLE_Category][self::VALUE_CategoryIdFirst];
    }

    public function copyPage(): void
    {
        $newTableIds = $this->actionService->copyRecord(self::TABLE_Page, self::VALUE_PageId, self::VALUE_TargetPageId);
        $this->recordIds['newPageId'] = $newTableIds[self::TABLE_Page][self::VALUE_PageId];
        $this->recordIds['newContentIdFirst'] = $newTableIds[self::TABLE_Content][self::VALUE_ContentIdFirst];
        $this->recordIds['newContentIdLast'] = $newTableIds[self::TABLE_Content][self::VALUE_ContentIdLast];
    }

    public function localizeCategoryOfRelation(): void
    {
        $localizedTableIds = $this->actionService->localizeRecord(self::TABLE_Category, self::VALUE_CategoryIdFirst, self::VALUE_LanguageId);
        $this->recordIds['localizedCategoryId'] = $localizedTableIds[self::TABLE_Category][self::VALUE_CategoryIdFirst];
    }

    public function localizeContentOfRelation(): void
    {
        $localizedTableIds = $this->actionService->localizeRecord(self::TABLE_Content, self::VALUE_ContentIdLast, self::VALUE_LanguageId);
        $this->recordIds['localizedContentId'] = $localizedTableIds[self::TABLE_Content][self::VALUE_ContentIdLast];
    }

    public function localizeContentOfRelationAndAddCategoryWithLanguageSynchronization(): void
    {
        $GLOBALS['TCA'][self::TABLE_Content]['columns'][self::FIELD_Categories]['config']['behaviour']['allowLanguageSynchronization'] = true;
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);
        $localizedTableIds = $this->actionService->localizeRecord(self::TABLE_Content, self::VALUE_ContentIdLast, self::VALUE_LanguageId);
        $this->recordIds['localizedContentId'] = $localizedTableIds[self::TABLE_Content][self::VALUE_ContentIdLast];
        $this->actionService->modifyReferences(
            self::TABLE_Content,
            self::VALUE_ContentIdLast,
            self::FIELD_Categories,
            [self::VALUE_CategoryIdSecond, self::VALUE_CategoryIdThird, self::VALUE_CategoryIdFourth]
        );
    }

    public function localizeContentChainOfRelationAndAddCategoryWithLanguageSynchronization(): void
    {
        $GLOBALS['TCA'][self::TABLE_Content]['columns'][self::FIELD_Categories]['config']['behaviour']['allowLanguageSynchronization'] = true;
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);
        $localizedTableIds = $this->actionService->localizeRecord(self::TABLE_Content, self::VALUE_ContentIdLast, self::VALUE_LanguageId);
        $this->recordIds['localizedContentId'] = $localizedTableIds[self::TABLE_Content][self::VALUE_ContentIdLast];
        $localizedTableIds = $this->actionService->localizeRecord(self::TABLE_Content, $this->recordIds['localizedContentId'], self::VALUE_LanguageIdSecond);
        $this->recordIds['localizedContentIdSecond'] = $localizedTableIds[self::TABLE_Content][$this->recordIds['localizedContentId']];
        $this->actionService->modifyRecord(
            self::TABLE_Content,
            $this->recordIds['localizedContentIdSecond'],
            ['l10n_state' => [self::FIELD_Categories => 'source']]
        );
        $this->actionService->modifyReferences(
            self::TABLE_Content,
            self::VALUE_ContentIdLast,
            self::FIELD_Categories,
            [self::VALUE_CategoryIdSecond, self::VALUE_CategoryIdThird, self::VALUE_CategoryIdFourth]
        );
    }

    public function localizeContentOfRelationWithLanguageSynchronization(): void
    {
        $GLOBALS['TCA'][self::TABLE_Content]['columns'][self::FIELD_Categories]['config']['behaviour']['allowLanguageSynchronization'] = true;
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);
        $localizedTableIds = $this->actionService->localizeRecord(self::TABLE_Content, self::VALUE_ContentIdLast, self::VALUE_LanguageId);
        $this->recordIds['localizedContentId'] = $localizedTableIds[self::TABLE_Content][self::VALUE_ContentIdLast];
    }

    public function localizeContentOfRelationWithLanguageExclude(): void
    {
        $GLOBALS['TCA'][self::TABLE_Content]['columns'][self::FIELD_Categories]['config']['l10n_mode'] = 'exclude';
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);
        $localizedTableIds = $this->actionService->localizeRecord(self::TABLE_Content, self::VALUE_ContentIdLast, self::VALUE_LanguageId);
        $this->recordIds['localizedContentId'] = $localizedTableIds[self::TABLE_Content][self::VALUE_ContentIdLast];
    }

    public function moveContentOfRelationToDifferentPage(): void
    {
        $this->actionService->moveRecord(self::TABLE_Content, self::VALUE_ContentIdLast, self::VALUE_TargetPageId);
    }

    public function modifyCategoryOfRelation(): void
    {
        $this->actionService->modifyRecord(self::TABLE_Category, self::VALUE_CategoryIdFirst, ['title' => 'Testing #1']);
    }

    public function deleteCategoryOfRelation(): void
    {
        $this->actionService->deleteRecord(self::TABLE_Category, self::VALUE_CategoryIdFirst);
    }
}
