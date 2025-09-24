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

namespace TYPO3\CMS\Core\Database\Schema;

use Doctrine\DBAL\Connection;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Database\Schema\Information\ColumnInfo;
use TYPO3\CMS\Core\Database\Schema\Information\TableInfo;
use TYPO3\CMS\Core\Package\Cache\PackageDependentCacheIdentifier;

/**
 * This wrapper of SchemaManager contains some internal caches to avoid performance issues for recurring calls to
 * specific schema related information. This should only be used in context where no changes are expected to happen.
 *
 * @internal This class is only for internal core usage and is not part of the public core API.
 */
final class SchemaInformation
{
    private string $connectionIdentifier;

    public function __construct(
        private readonly Connection $connection,
        private readonly FrontendInterface $cache,
        private readonly PackageDependentCacheIdentifier $packageDependentCacheIdentifier,
    ) {
        $this->connectionIdentifier = $this->packageDependentCacheIdentifier
            ->withPrefix(str_replace(
                ['.', ':', '/', '\\', '!', '?'],
                '_',
                (string)($connection->getParams()['dbname'] ?? 'generic')
            ))
            // hash connection params, which holds various information like host,
            // port etc. to get a descriptive hash for this connection.
            ->withAdditionalHashedIdentifier(serialize($connection->getParams()))
            ->toString();
    }

    /**
     * Similar to doctrine DBAL/AbstractSchemaManager, but with a cache-layer.
     * This is used core internally to auto-add types, for instance in Connection::insert().
     *
     * @return string[]
     */
    public function listTableNames(): array
    {
        $identifier = $this->connectionIdentifier . '-tablenames';
        $tableNames = $this->cache->get($identifier);
        if (is_array($tableNames)) {
            return $tableNames;
        }
        return $this->buildTableNames();
    }

    /**
     * @param string $tableName
     * @return array<string, ColumnInfo>
     */
    public function listTableColumnInfos(string $tableName): array
    {
        return $this->getTableInfo($tableName)->getColumnInfos();
    }

    /**
     * @param string $tableName
     * @return string[]
     */
    public function listTableColumnNames(string $tableName): array
    {
        return $this->getTableInfo($tableName)->getColumnNames();
    }

    public function getTableInfo(string $tableName): TableInfo
    {
        $identifier = $this->connectionIdentifier . '-tableinfo-' . $tableName;
        $tableInfo = $this->cache->get($identifier);
        if ($tableInfo instanceof TableInfo) {
            return $tableInfo;
        }
        return $this->buildTableInformation($tableName);
    }

    /**
     * @return string[]
     */
    private function buildTableNames(): array
    {
        $identifier = $this->connectionIdentifier . '-tablenames';
        $names = array_values($this->connection->createSchemaManager()->listTableNames());
        $this->cache->set($identifier, $names);
        return $names;
    }

    private function buildTableInformation(string $tableName): TableInfo
    {
        $identifier = $this->connectionIdentifier . '-tableinfo-' . $tableName;
        // Transform doctrine columns into ColumnInfo and add to new associative array using column name with
        // unmodified casing as array keys and not the lowercased from doctrine dbal associative array, which
        // leads to comparison issues in the core using the names. We need the untouched casing.
        $columns = $this->connection->createSchemaManager()->listTableColumns($tableName);
        $columnInfos = [];
        foreach ($columns as $column) {
            $columnInfo = ColumnInfo::convertFromDoctrineColumn($column);
            $columnInfos[$columnInfo->name] = $columnInfo;
        }
        $tableInfo = new TableInfo(
            name: $tableName,
            columnInfos: $columnInfos,
        );
        $this->cache->set($identifier, $tableInfo);
        return $tableInfo;
    }
}
