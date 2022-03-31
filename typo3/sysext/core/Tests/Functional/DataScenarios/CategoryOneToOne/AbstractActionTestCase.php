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

namespace TYPO3\CMS\Core\Tests\Functional\DataScenarios\CategoryOneToOne;

use TYPO3\CMS\Core\Tests\Functional\DataScenarios\AbstractDataHandlerActionTestCase;

/**
 * Functional test for the DataHandler
 */
abstract class AbstractActionTestCase extends AbstractDataHandlerActionTestCase
{
    protected const VALUE_PageId = 88;
    protected const VALUE_TargetPageId = 89;
    protected const VALUE_CategoryPageId = 0;
    protected const VALUE_ContentIdFirst = 297;
    protected const VALUE_ContentIdLast = 298;
    protected const VALUE_CategoryIdFirst = 28;
    protected const VALUE_CategoryIdSecond = 29;
    protected const VALUE_CategoryIdThird = 30;
    protected const VALUE_CategoryIdFourth = 31;

    protected const TABLE_Content = 'tt_content';
    protected const TABLE_Category = 'sys_category';

    protected const FIELD_Category = 'tx_testdatahandler_category';

    protected const SCENARIO_DataSet = __DIR__ . '/DataSet/ImportDefault.csv';

    protected array $testExtensionsToLoad = [
        'typo3/sysext/core/Tests/Functional/Fixtures/Extensions/irre_tutorial',
        'typo3/sysext/core/Tests/Functional/Fixtures/Extensions/test_datahandler',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(static::SCENARIO_DataSet);

        $this->setUpFrontendSite(1, $this->siteLanguageConfiguration);
        $this->setUpFrontendRootPage(1, ['typo3/sysext/core/Tests/Functional/Fixtures/Frontend/JsonRenderer.typoscript']);
    }

    public function addCategoryRelation(): void
    {
        $this->actionService->modifyRecord(
            self::TABLE_Content,
            self::VALUE_ContentIdLast,
            [self::FIELD_Category => self::VALUE_CategoryIdFourth]
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

        $this->actionService->modifyRecord(
            self::TABLE_Content,
            self::VALUE_ContentIdLast,
            [self::FIELD_Category => $this->recordIds['newCategoryId']]
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

        $this->actionService->modifyRecord(
            self::TABLE_Content,
            self::VALUE_ContentIdFirst,
            [self::FIELD_Category => $this->recordIds['newCategoryId']]
        );
    }

    public function changeExistingCategoryRelation(): void
    {
        $this->actionService->modifyRecord(
            self::TABLE_Content,
            self::VALUE_ContentIdFirst,
            [self::FIELD_Category => self::VALUE_CategoryIdSecond]
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
            self::VALUE_CategoryIdThird,
            ['title' => 'Testing #1']
        );
    }

    public function moveContentAndCategoryRelationToDifferentPage(): void
    {
        $this->actionService->moveRecord(self::TABLE_Content, self::VALUE_ContentIdFirst, self::VALUE_TargetPageId);
        $this->actionService->moveRecord(self::TABLE_Category, self::VALUE_CategoryIdThird, self::VALUE_TargetPageId);
    }

    public function changeContentAndCategorySorting(): void
    {
        $this->actionService->moveRecord(self::TABLE_Content, self::VALUE_ContentIdLast, self::VALUE_PageId);
        $this->actionService->moveRecord(self::TABLE_Category, self::VALUE_CategoryIdThird, -self::VALUE_CategoryIdFourth);
    }

    public function copyContentAndCategoryRelation(): void
    {
        $newTableIds = $this->actionService->copyRecord(self::TABLE_Content, self::VALUE_ContentIdFirst, self::VALUE_PageId);
        $this->recordIds['copiedContentId'] = $newTableIds[self::TABLE_Content][self::VALUE_ContentIdFirst];
        $newTableIds = $this->actionService->copyRecord(self::TABLE_Category, self::VALUE_CategoryIdFourth, self::VALUE_CategoryPageId);
        $this->recordIds['copiedCategoryId'] = $newTableIds[self::TABLE_Category][self::VALUE_CategoryIdFourth];
    }

    public function deleteCategoryRelation(): void
    {
        $this->actionService->modifyRecord(
            self::TABLE_Content,
            self::VALUE_ContentIdFirst,
            [self::FIELD_Category => 0]
        );
    }
}
