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

namespace TYPO3Tests\TestRowUpdater\Upgrades\RowUpdater;

use TYPO3\CMS\Core\Attribute\AsRowUpdater;
use TYPO3\CMS\Core\Upgrades\RowUpdater\RowUpdaterInterface;

/**
 * RowUpdater test fixture marking sys_file rows as deleted. The sys_file table
 * has no soft delete capability, so the row must be removed from the database.
 * Used by DatabaseRowsUpdateWizardTest.
 */
#[AsRowUpdater('sysFileHardDeleter')]
final class SysFileHardDeleter implements RowUpdaterInterface
{
    public function getTitle(): string
    {
        return 'RowUpdater test implementation hard deleting sys_file rows.';
    }

    public function hasPotentialUpdateForTable(string $tableName): bool
    {
        return $tableName === 'sys_file';
    }

    public function updateTableRow(string $tableName, array $row): array
    {
        $row['deleted'] = 1;
        return $row;
    }
}
