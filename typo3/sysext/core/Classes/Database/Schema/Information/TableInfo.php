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

namespace TYPO3\CMS\Core\Database\Schema\Information;

use Doctrine\DBAL\Schema\Table;

/**
 * Provides reduced table information compared to {@see Table} and intended to be cacheable.
 *
 * @internal This class is only for internal core usage and is not part of the public core API.
 */
final readonly class TableInfo
{
    /**
     * @param array<string, ColumnInfo> $columnInfos
     */
    public function __construct(
        private string $name,
        private array $columnInfos,
    ) {}

    public function getName(): string
    {
        return $this->name;
    }

    public function hasColumnInfo(string $columnName): bool
    {
        return in_array($columnName, $this->getColumnNames(), true);
    }

    public function getColumnInfo(string $columnName): ?ColumnInfo
    {
        return $this->columnInfos[$columnName] ?? null;
    }

    public function getColumnNames(): array
    {
        return array_keys($this->columnInfos);
    }

    /**
     * @return array<string, ColumnInfo>
     */
    public function getColumnInfos(): array
    {
        return $this->columnInfos;
    }
}
