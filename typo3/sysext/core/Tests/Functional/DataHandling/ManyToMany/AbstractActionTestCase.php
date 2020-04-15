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
        $this->importScenarioDataSet('LiveDefaultPages');
        $this->importScenarioDataSet('LiveDefaultElements');

        $this->setUpFrontendSite(1, $this->siteLanguageConfiguration);
        $this->setUpFrontendRootPage(1, ['typo3/sysext/core/Tests/Functional/Fixtures/Frontend/JsonRenderer.typoscript']);
    }

    /**
     * MM Relations
     */

    /**
     * See DataSet/addCategoryRelation.csv
     */
    public function addCategoryRelation()
    {
        $this->actionService->modifyReferences(
            self::TABLE_Content,
            self::VALUE_ContentIdFirst,
            'categories',
            [self::VALUE_CategoryIdFirst, self::VALUE_CategoryIdSecond, 31]
        );
    }

    /**
     * See DataSet/deleteCategoryRelation.csv
     */
    public function deleteCategoryRelation()
    {
        $this->actionService->modifyReferences(
            self::TABLE_Content,
            self::VALUE_ContentIdFirst,
            'categories',
            [self::VALUE_CategoryIdFirst]
        );
    }

    /**
     * See DataSet/changeCategoryRelationSorting.csv
     */
    public function changeCategoryRelationSorting()
    {
        $this->actionService->modifyReferences(
            self::TABLE_Content,
            self::VALUE_ContentIdFirst,
            'categories',
            [self::VALUE_CategoryIdSecond, self::VALUE_CategoryIdFirst]
        );
    }

    /**
     * See DataSet/modifyCategoryRecordOfCategoryRelation.csv
     */
    public function modifyCategoryOfRelation()
    {
        $this->actionService->modifyRecord(self::TABLE_Category, self::VALUE_CategoryIdFirst, ['title' => 'Testing #1']);
    }

    /**
     * See DataSet/modifyContentRecordOfCategoryRelation.csv
     */
    public function modifyContentOfRelation()
    {
        $this->actionService->modifyRecord(self::TABLE_Content, self::VALUE_ContentIdFirst, ['header' => 'Testing #1']);
    }

    /**
     * See DataSet/modifyBothRecordsOfCategoryRelation.csv
     */
    public function modifyBothsOfRelation()
    {
        $this->actionService->modifyRecord(self::TABLE_Category, self::VALUE_CategoryIdFirst, ['title' => 'Testing #1']);
        $this->actionService->modifyRecord(self::TABLE_Content, self::VALUE_ContentIdFirst, ['header' => 'Testing #1']);
    }

    /**
     * See DataSet/deleteContentRecordOfCategoryRelation.csv
     */
    public function deleteContentOfRelation()
    {
        $this->actionService->deleteRecord(self::TABLE_Content, self::VALUE_ContentIdLast);
    }

    /**
     * See DataSet/deleteCategoryRecordOfCategoryRelation.csv
     */
    public function deleteCategoryOfRelation()
    {
        $this->actionService->deleteRecord(self::TABLE_Category, self::VALUE_CategoryIdFirst);
    }

    /**
     * See DataSet/copyContentRecordOfCategoryRelation.csv
     */
    public function copyContentOfRelation()
    {
        $newTableIds = $this->actionService->copyRecord(self::TABLE_Content, self::VALUE_ContentIdLast, self::VALUE_PageId);
        $this->recordIds['newContentId'] = $newTableIds[self::TABLE_Content][self::VALUE_ContentIdLast];
    }

    /**
     * See DataSet/copyCategoryRecordOfCategoryRelation.csv
     */
    public function copyCategoryOfRelation()
    {
        $newTableIds = $this->actionService->copyRecord(self::TABLE_Category, self::VALUE_CategoryIdFirst, 0);
        $this->recordIds['newCategoryId'] = $newTableIds[self::TABLE_Category][self::VALUE_CategoryIdFirst];
    }

    /**
     * See DataSet/copyContentToLanguageOfRelation.csv
     */
    public function copyContentToLanguageOfRelation()
    {
        $this->actionService->copyRecordToLanguage(self::TABLE_Content, self::VALUE_ContentIdLast, self::VALUE_LanguageId);
    }

    /**
     * See DataSet/copyCategoryToLanguageOfRelation.csv
     */
    public function copyCategoryToLanguageOfRelation()
    {
        $this->actionService->copyRecordToLanguage(self::TABLE_Category, self::VALUE_CategoryIdFirst, self::VALUE_LanguageId);
    }

    /**
     * See DataSet/localizeContentRecordOfCategoryRelation.csv
     */
    public function localizeContentOfRelation()
    {
        $localizedTableIds = $this->actionService->localizeRecord(self::TABLE_Content, self::VALUE_ContentIdLast, self::VALUE_LanguageId);
        $this->recordIds['localizedContentId'] = $localizedTableIds[self::TABLE_Content][self::VALUE_ContentIdLast];
    }

    public function localizeContentOfRelationWithLanguageSynchronization()
    {
        $GLOBALS['TCA'][self::TABLE_Content]['columns'][self::FIELD_Categories]['config']['behaviour']['allowLanguageSynchronization'] = true;
        $localizedTableIds = $this->actionService->localizeRecord(self::TABLE_Content, self::VALUE_ContentIdLast, self::VALUE_LanguageId);
        $this->recordIds['localizedContentId'] = $localizedTableIds[self::TABLE_Content][self::VALUE_ContentIdLast];
    }

    public function localizeContentOfRelationAndAddCategoryWithLanguageSynchronization()
    {
        self::localizeContentOfRelationWithLanguageSynchronization();
        $this->actionService->modifyReferences(
            self::TABLE_Content,
            self::VALUE_ContentIdLast,
            self::FIELD_Categories,
            [self::VALUE_CategoryIdSecond, self::VALUE_CategoryIdThird, self::VALUE_CategoryIdFourth]
        );
    }

    public function localizeContentChainOfRelationAndAddCategoryWithLanguageSynchronization()
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

    /**
     * See DataSet/localizeCategoryRecordOfCategoryRelation.csv
     */
    public function localizeCategoryOfRelation()
    {
        $localizedTableIds = $this->actionService->localizeRecord(self::TABLE_Category, self::VALUE_CategoryIdFirst, self::VALUE_LanguageId);
        $this->recordIds['localizedCategoryId'] = $localizedTableIds[self::TABLE_Category][self::VALUE_CategoryIdFirst];
    }

    /**
     * See DataSet/moveContentRecordOfCategoryRelationToDifferentPage.csv
     */
    public function moveContentOfRelationToDifferentPage()
    {
        $this->actionService->moveRecord(self::TABLE_Content, self::VALUE_ContentIdLast, self::VALUE_PageIdTarget);
    }

    /**
     * See DataSet/copyPage.csv
     */
    public function copyPage()
    {
        $newTableIds = $this->actionService->copyRecord(self::TABLE_Page, self::VALUE_PageId, self::VALUE_PageIdTarget);
        $this->recordIds['newPageId'] = $newTableIds[self::TABLE_Page][self::VALUE_PageId];
        $this->recordIds['newContentIdFirst'] = $newTableIds[self::TABLE_Content][self::VALUE_ContentIdFirst];
        $this->recordIds['newContentIdLast'] = $newTableIds[self::TABLE_Content][self::VALUE_ContentIdLast];
    }
}
