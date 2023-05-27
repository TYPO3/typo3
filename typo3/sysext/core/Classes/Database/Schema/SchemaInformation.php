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
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;

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
        private readonly FrontendInterface $cache
    ) {
        $this->connectionIdentifier = sprintf(
            '%s-%s',
            str_replace(
                ['.', ':', '/', '\\', '!', '?'],
                '_',
                (string)$connection->getDatabase()
            ),
            // hash connection params, which holds various information like host,
            // port etc. to get a descriptive hash for this connection.
            hash('xxh3', serialize($connection->getParams()))
        );
    }

    /**
     * Similar to doctrine DBAL/AbstractSchemaManager, but with a cache-layer.
     * This is used core internally to auto-add types, for instance in Connection::insert().
     *
     * @return string[]
     */
    public function listTableNames(): array
    {
        $tableNames = [];
        $tables = $this->introspectSchema()->getTables();
        array_walk($tables, static function (Table $table) use (&$tableNames) {
            $tableNames[] = $table->getName();
        });
        return $tableNames;
    }

    /**
     * Similar to doctrine DBAL/AbstractSchemaManager, but with a cache-layer.
     * This is used core internally to auto-add types, for instance in Connection::insert().
     *
     * Creates one cache entry in core cache per configured connection.
     */
    public function introspectSchema(): Schema
    {
        $identifier = $this->connectionIdentifier . '-schema';
        $schema = $this->cache->get($identifier);
        if ($schema instanceof Schema) {
            return $schema;
        }
        $schema = $this->connection->createSchemaManager()->introspectSchema();
        $this->cache->set($identifier, $schema);
        return $schema;
    }

    /**
     * Similar to doctrine DBAL/AbstractSchemaManager, but with a cache-layer.
     * This is used core internally to auto-add types, for instance in Connection::insert().
     *
     * Creates one cache entry in core cache per table.
     */
    public function introspectTable(string $tableName): Table
    {
        $identifier = $this->connectionIdentifier . '-table-' . $tableName;
        $table = $this->cache->get($identifier);
        if ($table instanceof Table) {
            return $table;
        }
        $table = $this->connection->createSchemaManager()->introspectTable($tableName);
        $this->cache->set($identifier, $table);
        return $table;
    }
}
