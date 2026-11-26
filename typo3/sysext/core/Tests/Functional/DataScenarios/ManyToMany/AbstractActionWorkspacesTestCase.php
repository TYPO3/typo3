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
    protected const VALUE_WorkspaceId = 1;

    protected const SCENARIO_DataSet = __DIR__ . '/DataSet/ImportDefaultWorkspaces.csv';

    protected array $coreExtensionsToLoad = ['workspaces'];

    /**
     * Once a related record has a workspace version, the stored relations
     * point to that version while the submitted value names the live record.
     */
    public function modifyContentAndTestMMWithUnchangedGroupMM2Relations(): void
    {
        $this->importCSVDataSet(__DIR__ . '/DataSet/ImportGroupMM2Relations.csv');
        $this->actionService->modifyRecord(self::TABLE_Content, self::VALUE_ContentIdFirst, ['header' => 'Surfing #1']);
        $this->actionService->modifyRecord(
            self::TABLE_TEST_MM,
            self::VALUE_TestMMIdWithGroupMM2Relations,
            [self::FIELD_GROUP_MM_2_LOCAL => self::TABLE_Content . '_' . self::VALUE_ContentIdFirst . ',' . self::TABLE_Content . '_' . self::VALUE_ContentIdLast]
        );
    }
}
