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

abstract class AbstractActionWorkspacesTestCase extends AbstractActionTestCase
{
    protected const VALUE_SurfIdLast = 31;
    protected const VALUE_WorkspaceId = 1;

    protected const SCENARIO_DataSet = __DIR__ . '/DataSet/ImportDefaultWorkspaces.csv';

    protected array $coreExtensionsToLoad = ['workspaces'];

    public function createContentAndAddRelation(): void
    {
        $newTableIds = $this->actionService->createNewRecord(
            self::TABLE_Content,
            self::VALUE_PageId,
            ['header' => 'Surfing #1', self::FIELD_Surfing => self::VALUE_SurfIdSecond]
        );
        $this->recordIds['newContentId'] = $newTableIds[self::TABLE_Content][0];
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

    public function createContentAndCreateRelation(): void
    {
        $newTableIds = $this->actionService->createNewRecords(
            self::VALUE_PageId,
            [
                self::TABLE_Surf => ['pid' => 0, 'title' => 'Surfing #1'],
                self::TABLE_Content => ['header' => 'Surfing #1', self::FIELD_Surfing => '__previousUid'],
            ]
        );
        $this->recordIds['newSurfId'] = $newTableIds[self::TABLE_Surf][0];
        $this->recordIds['newContentId'] = $newTableIds[self::TABLE_Content][0];
    }

    public function createSurfAndCreateRelation(): void
    {
        $newTableIds = $this->actionService->createNewRecords(
            self::VALUE_PageId,
            [
                self::TABLE_Content => ['header' => 'Surfing #1'],
                self::TABLE_Surf => ['pid' => 0, 'title' => 'Surfing #1', self::FIELD_Relations => 'tt_content___previousUid'],
            ]
        );
        $this->recordIds['newContentId'] = $newTableIds[self::TABLE_Content][0];
        $this->recordIds['newSurfId'] = $newTableIds[self::TABLE_Surf][0];
    }

    public function createContentWithSurfAndAddRelation(): void
    {
        $newTableIds = $this->actionService->createNewRecords(
            self::VALUE_PageId,
            [
                self::TABLE_Surf => ['pid' => 0, 'title' => 'Surfing #1'],
                self::TABLE_Content => ['header' => 'Surfing #1'],
            ]
        );
        $this->recordIds['newSurfId'] = $newTableIds[self::TABLE_Surf][0];
        $this->recordIds['newContentId'] = $newTableIds[self::TABLE_Content][0];

        $this->actionService->modifyRecord(
            self::TABLE_Content,
            $this->recordIds['newContentId'],
            [self::FIELD_Surfing => $this->recordIds['newSurfId']]
        );
    }

    public function createSurfWithContentAndAddRelation(): void
    {
        $newTableIds = $this->actionService->createNewRecords(
            self::VALUE_PageId,
            [
                self::TABLE_Content => ['header' => 'Surfing #1'],
                self::TABLE_Surf => ['pid' => 0, 'title' => 'Surfing #1', self::FIELD_Relations => 'tt_content___previousUid'],
            ]
        );
        $this->recordIds['newContentId'] = $newTableIds[self::TABLE_Content][0];
        $this->recordIds['newSurfId'] = $newTableIds[self::TABLE_Surf][0];

        $this->actionService->modifyRecord(
            self::TABLE_Surf,
            $this->recordIds['newSurfId'],
            [self::FIELD_Relations => 'tt_content_' . $this->recordIds['newContentId']]
        );
    }
}
