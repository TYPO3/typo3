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

namespace TYPO3\CMS\Core\DataHandling;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\ReferenceIndex;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Helper class for DataHandler to gather requests for reference index updates
 * and perform them in one go after other operations have been done.
 * This is used to suppress multiple reference index update calls for the same
 * workspace/table/uid combination within one DataHandler main call.
 *
 * @internal should only be used by the TYPO3 Core
 */
class ReferenceIndexUpdater
{
    /**
     * [ workspaceId => [ tableName => [ uid ] ] ]
     *
     * @var array<int, array<string, array<int, int>>>
     */
    protected $updateRegistry = [];

    /**
     * Register a workspace/table/uid row for update
     *
     * @param string $table Table name
     * @param int $uid Record uid
     * @param int $workspace Workspace the record lives in
     */
    public function registerForUpdate(string $table, int $uid, int $workspace): void
    {
        if ($workspace && !BackendUtility::isTableWorkspaceEnabled($table)) {
            // If a user is in some workspace and changes relations of not workspace aware
            // records, the reference index update needs to be performed as if the user
            // is in live workspace. This is detected here and the update is registered for live.
            $workspace = 0;
        }
        if (!isset($this->updateRegistry[$workspace][$table])) {
            $this->updateRegistry[$workspace][$table] = [];
        }
        if (!in_array($uid, $this->updateRegistry[$workspace][$table], true)) {
            $this->updateRegistry[$workspace][$table][] = $uid;
        }
    }

    /**
     * Perform the reference index update operations
     */
    public function update(): void
    {
        $referenceIndex = GeneralUtility::makeInstance(ReferenceIndex::class);
        $referenceIndex->enableRuntimeCache();
        foreach ($this->updateRegistry as $workspace => $tableArray) {
            $referenceIndex->setWorkspaceId($workspace);
            foreach ($tableArray as $table => $uidArray) {
                foreach ($uidArray as $uid) {
                    $referenceIndex->updateRefIndexTable($table, $uid);
                }
            }
        }
        $this->updateRegistry = [];
    }
}
