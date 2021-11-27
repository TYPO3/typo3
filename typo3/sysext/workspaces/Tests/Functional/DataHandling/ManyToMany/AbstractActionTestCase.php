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

namespace TYPO3\CMS\Workspaces\Tests\Functional\DataHandling\ManyToMany;

/**
 * Functional test for the DataHandler
 */
abstract class AbstractActionTestCase extends \TYPO3\CMS\Core\Tests\Functional\DataHandling\ManyToMany\AbstractActionTestCase
{
    protected const VALUE_CategoryIdLast = 31;
    protected const VALUE_WorkspaceId = 1;

    protected const SCENARIO_DataSet = __DIR__ . '/DataSet/ImportDefault.csv';

    protected $coreExtensionsToLoad = ['workspaces'];

    public function createContentAndAddRelation(): void
    {
        $newTableIds = $this->actionService->createNewRecord(
            self::TABLE_Content,
            self::VALUE_PageId,
            ['header' => 'Testing #1', 'categories' => self::VALUE_CategoryIdSecond]
        );
        $this->recordIds['newContentId'] = $newTableIds[self::TABLE_Content][0];
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

    public function createContentAndCreateRelation(): void
    {
        $newTableIds = $this->actionService->createNewRecords(
            self::VALUE_PageId,
            [
                self::TABLE_Category => ['pid' => 0, 'title' => 'Testing #1'],
                self::TABLE_Content => ['header' => 'Testing #1', 'categories' => '__previousUid'],
            ]
        );
        $this->recordIds['newCategoryId'] = $newTableIds[self::TABLE_Category][0];
        $this->recordIds['newContentId'] = $newTableIds[self::TABLE_Content][0];
    }

    public function createCategoryAndCreateRelation(): void
    {
        $newTableIds = $this->actionService->createNewRecords(
            self::VALUE_PageId,
            [
                self::TABLE_Content => ['header' => 'Testing #1'],
                self::TABLE_Category => ['pid' => 0, 'title' => 'Testing #1', 'items' => 'tt_content___previousUid'],
            ]
        );
        $this->recordIds['newContentId'] = $newTableIds[self::TABLE_Content][0];
        $this->recordIds['newCategoryId'] = $newTableIds[self::TABLE_Category][0];
    }

    public function createContentWithCategoryAndAddRelation(): void
    {
        $newTableIds = $this->actionService->createNewRecords(
            self::VALUE_PageId,
            [
                self::TABLE_Category => ['pid' => 0, 'title' => 'Testing #1'],
                self::TABLE_Content => ['header' => 'Testing #1'],
            ]
        );
        $this->recordIds['newCategoryId'] = $newTableIds[self::TABLE_Category][0];
        $this->recordIds['newContentId'] = $newTableIds[self::TABLE_Content][0];

        $this->actionService->modifyRecord(
            self::TABLE_Content,
            $this->recordIds['newContentId'],
            ['categories' => $this->recordIds['newCategoryId']]
        );
    }

    public function createCategoryWithContentAndAddRelation(): void
    {
        $newTableIds = $this->actionService->createNewRecords(
            self::VALUE_PageId,
            [
                self::TABLE_Content => ['header' => 'Testing #1'],
                self::TABLE_Category => ['pid' => 0, 'title' => 'Testing #1', 'items' => 'tt_content___previousUid'],
            ]
        );
        $this->recordIds['newContentId'] = $newTableIds[self::TABLE_Content][0];
        $this->recordIds['newCategoryId'] = $newTableIds[self::TABLE_Category][0];

        $this->actionService->modifyRecord(
            self::TABLE_Category,
            $this->recordIds['newCategoryId'],
            ['items' => 'tt_content_' . $this->recordIds['newContentId']]
        );
    }
}
