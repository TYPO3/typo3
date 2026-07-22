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
 * RowUpdater test fixture changing the "header" field of tt_content rows.
 * Used by DatabaseRowsUpdateWizardTest.
 */
#[AsRowUpdater('ttContentHeaderUpdater')]
final class TtContentHeaderUpdater implements RowUpdaterInterface
{
    public function getTitle(): string
    {
        return 'RowUpdater test implementation changing the tt_content header field.';
    }

    public function hasPotentialUpdateForTable(string $tableName): bool
    {
        return $tableName === 'tt_content';
    }

    public function updateTableRow(string $tableName, array $row): array
    {
        $row['header'] = 'Updated by row updater';
        return $row;
    }
}
