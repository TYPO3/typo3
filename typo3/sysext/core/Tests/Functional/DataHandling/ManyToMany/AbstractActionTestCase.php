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

namespace TYPO3\CMS\Core\Tests\Functional\DataHandling\ManyToMany;

use TYPO3\CMS\Core\Tests\Functional\DataHandling\AbstractDataHandlerActionTestCase;

/**
 * Functional test for the DataHandler
 */
abstract class AbstractActionTestCase extends AbstractDataHandlerActionTestCase
{
    const VALUE_PageId = 89;
    const VALUE_PageIdTarget = 90;
    const VALUE_ContentIdFirst = 297;
    const VALUE_ContentIdLast = 298;
    const VALUE_LanguageId = 1;
    const VALUE_LanguageIdSecond = 2;
    const VALUE_CategoryIdFirst = 28;
    const VALUE_CategoryIdSecond = 29;
    const VALUE_CategoryIdThird = 30;
    const VALUE_CategoryIdFourth = 31;

    const TABLE_Page = 'pages';
    const TABLE_Content = 'tt_content';
    const TABLE_Category = 'sys_category';
    const TABLE_ContentCategory_ManyToMany = 'sys_category_record_mm';

    const FIELD_Categories = 'categories';

    /**
     * @var string
     */
    protected $scenarioDataSetDirectory = 'typo3/sysext/core/Tests/Functional/DataHandling/ManyToMany/DataSet/';

    protected function setUp(): void
    {
        parent::setUp();
        $this->importScenarioDataSet('ImportDefault');

        $this->setUpFrontendSite(1, $this->siteLanguageConfiguration);
        $this->setUpFrontendRootPage(1, ['typo3/sysext/core/Tests/Functional/Fixtures/Frontend/JsonRenderer.typoscript']);
    }

    public function addCategoryRelation(): void
    {
        $this->actionService->modifyReferences(
            self::TABLE_Content,
            self::VALUE_ContentIdFirst,
            'categories',
            [self::VALUE_CategoryIdFirst, self::VALUE_CategoryIdSecond, 31]
        );
    }

    public function createCategoryAndAddRelation(): void
    {
        $newTableIds = $this->actionService->createNewRecord(
            self::TABLE_Category,
            0,
            ['title' => 'Testing #1', 'items' => 'tt_content_' . self::VALUE_ContentIdFirst]
        );
        $this->recordIds['newCategoryId'] = $newTableIds[self::TABLE_Category][0];
    }

    public function deleteCategoryRelation(): void
    {
        $this->actionService->modifyReferences(
            self::TABLE_Content,
            self::VALUE_ContentIdFirst,
            'categories',
            [self::VALUE_CategoryIdFirst]
        );
    }

    public function changeCategoryRelationSorting(): void
    {
        $this->actionService->modifyReferences(
            self::TABLE_Content,
            self::VALUE_ContentIdFirst,
            'categories',
            [self::VALUE_CategoryIdSecond, self::VALUE_CategoryIdFirst]
        );
    }

    public function modifyCategoryOfRelation(): void
    {
        $this->actionService->modifyRecord(self::TABLE_Category, self::VALUE_CategoryIdFirst, ['title' => 'Testing #1']);
    }

    public function modifyContentOfRelation(): void
    {
        $this->actionService->modifyRecord(self::TABLE_Content, self::VALUE_ContentIdFirst, ['header' => 'Testing #1']);
    }

    public function modifyBothsOfRelation(): void
    {
        $this->actionService->modifyRecord(self::TABLE_Category, self::VALUE_CategoryIdFirst, ['title' => 'Testing #1']);
        $this->actionService->modifyRecord(self::TABLE_Content, self::VALUE_ContentIdFirst, ['header' => 'Testing #1']);
    }

    public function deleteContentOfRelation(): void
    {
        $this->actionService->deleteRecord(self::TABLE_Content, self::VALUE_ContentIdLast);
    }

    public function deleteCategoryOfRelation(): void
    {
        $this->actionService->deleteRecord(self::TABLE_Category, self::VALUE_CategoryIdFirst);
    }

    public function copyContentOfRelation(): void
    {
        $newTableIds = $this->actionService->copyRecord(self::TABLE_Content, self::VALUE_ContentIdLast, self::VALUE_PageId);
        $this->recordIds['newContentId'] = $newTableIds[self::TABLE_Content][self::VALUE_ContentIdLast];
    }

    public function copyCategoryOfRelation(): void
    {
        $newTableIds = $this->actionService->copyRecord(self::TABLE_Category, self::VALUE_CategoryIdFirst, 0);
        $this->recordIds['newCategoryId'] = $newTableIds[self::TABLE_Category][self::VALUE_CategoryIdFirst];
    }

    /**
     * See DataSet/copyContentToLanguageOfRelation.csv
     * @todo: does not exist in workspaces
     */
    public function copyContentToLanguageOfRelation(): void
    {
        $this->actionService->copyRecordToLanguage(self::TABLE_Content, self::VALUE_ContentIdLast, self::VALUE_LanguageId);
    }

    /**
     * See DataSet/copyCategoryToLanguageOfRelation.csv
     * @todo: does not exist in workspaces
     */
    public function copyCategoryToLanguageOfRelation(): void
    {
        $this->actionService->copyRecordToLanguage(self::TABLE_Category, self::VALUE_CategoryIdFirst, self::VALUE_LanguageId);
    }

    public function localizeContentOfRelation(): void
    {
        $localizedTableIds = $this->actionService->localizeRecord(self::TABLE_Content, self::VALUE_ContentIdLast, self::VALUE_LanguageId);
        $this->recordIds['localizedContentId'] = $localizedTableIds[self::TABLE_Content][self::VALUE_ContentIdLast];
    }

    /**
     * @todo: does not exist in workspaces
     */
    public function localizeContentOfRelationWithLanguageSynchronization(): void
    {
        $GLOBALS['TCA'][self::TABLE_Content]['columns'][self::FIELD_Categories]['config']['behaviour']['allowLanguageSynchronization'] = true;
        $localizedTableIds = $this->actionService->localizeRecord(self::TABLE_Content, self::VALUE_ContentIdLast, self::VALUE_LanguageId);
        $this->recordIds['localizedContentId'] = $localizedTableIds[self::TABLE_Content][self::VALUE_ContentIdLast];
    }

    /**
     * @todo: does not exist in workspaces
     */
    public function localizeContentOfRelationAndAddCategoryWithLanguageSynchronization(): void
    {
        self::localizeContentOfRelationWithLanguageSynchronization();
        $this->actionService->modifyReferences(
            self::TABLE_Content,
            self::VALUE_ContentIdLast,
            self::FIELD_Categories,
            [self::VALUE_CategoryIdSecond, self::VALUE_CategoryIdThird, self::VALUE_CategoryIdFourth]
        );
    }

    /**
     * @todo: does not exist in workspaces
     */
    public function localizeContentChainOfRelationAndAddCategoryWithLanguageSynchronization(): void
    {
        self::localizeContentOfRelationWithLanguageSynchronization();
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

    public function localizeCategoryOfRelation(): void
    {
        $localizedTableIds = $this->actionService->localizeRecord(self::TABLE_Category, self::VALUE_CategoryIdFirst, self::VALUE_LanguageId);
        $this->recordIds['localizedCategoryId'] = $localizedTableIds[self::TABLE_Category][self::VALUE_CategoryIdFirst];
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
