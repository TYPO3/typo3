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
 * RowUpdater test fixture implementation using PHP Attribute registration.
 */
#[AsRowUpdater]
class AttributeNoIdentifier implements RowUpdaterInterface
{
    public function getTitle(): string
    {
        return 'RowUpdater test implementation using php attribute registration without identifier';
    }

    public function hasPotentialUpdateForTable(string $tableName): bool
    {
        return $this->supportedTable($tableName);
    }

    public function updateTableRow(string $tableName, array $row): array
    {
        if (!$this->supportedTable($tableName)) {
            throw new \UnexpectedValueException(
                sprintf(
                    'The table "%s" is not supported by the RowUpdater "%s".',
                    $tableName,
                    $this::class,
                ),
                1763155767,
            );
        }
        return $row;
    }

    private function supportedTable(string $tableName): bool
    {
        return in_array($tableName, ['tt_content'], true);
    }
}
