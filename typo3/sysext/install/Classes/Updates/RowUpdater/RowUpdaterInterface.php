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

namespace TYPO3\CMS\Install\Updates\RowUpdater;

/**
 * Interface each single row updater must implement.
 */
interface RowUpdaterInterface
{
    /**
     * Get a description of this single row updater
     *
     * @return string
     */
    public function getTitle(): string;

    /**
     * Return true if this row updater may have updates for given table rows.
     *
     * @param string $tableName Given table
     * @return bool
     */
    public function hasPotentialUpdateForTable(string $tableName): bool;

    /**
     * Update a single row from a table.
     *
     * @param string $tableName Given table
     * @param array $row Given row
     * @return array Potentially modified row
     */
    public function updateTableRow(string $tableName, array $row): array;
}
