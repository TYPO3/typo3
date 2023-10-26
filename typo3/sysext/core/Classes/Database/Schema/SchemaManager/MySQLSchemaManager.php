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

namespace TYPO3\CMS\Core\Database\Schema\SchemaManager;

use Doctrine\DBAL\Platforms\MariaDBPlatform as DoctrineMariaDBPlatform;
use Doctrine\DBAL\Platforms\MySQLPlatform as DoctrineMySQLPlatform;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Schema\MySQLSchemaManager as DoctrineMySQLSchemaManager;

/**
 * Extending the doctrine MySQLSchemaManager to integrate additional processing stuff
 * due to the dropped event system with `doctrine/dbal 4.x`.
 *
 * For example, this is used to process custom doctrine types.
 *
 * Platform specific SchemaManager are extended to manipulate the schema handling. TYPO3 needs to
 *  do that to provide additional doctrine type handling and other workarounds or alignments. Long
 *  time this have been done by using the `doctrine EventManager` to hook into several places, which
 *  no longer exists.
 *
 * Note:    MySQLSchemaManager is used for MySQL and MariaDB. Even doctrine/dbal 4.0 provides no dedicated
 *          schema manager for doctrine/dbal 4.0. Keep this in mind.
 *
 * @link https://github.com/doctrine/dbal/blob/3.7.x/UPGRADE.md#deprecated-not-setting-a-schema-manager-factory
 * @link https://github.com/doctrine/dbal/blob/3.7.x/UPGRADE.md#deprecated-extension-via-doctrine-event-manager
 *
 * @internal not part of the public Core API.
 */
class MySQLSchemaManager extends DoctrineMySQLSchemaManager
{
    use CustomDoctrineTypesColumnDefinitionTrait;
    use CustomPortableTableIndexesListTrait;

    /**
     * Gets Table Column Definition.
     *
     * @param array<string, mixed> $tableColumn
     *
     * @todo Add `array` type to `$tableColumn` argument with doctrine/dbal 4.0 upgrade.
     */
    protected function _getPortableTableColumnDefinition($tableColumn): Column
    {
        /** @var DoctrineMariaDBPlatform|DoctrineMySQLPlatform $platform */
        $platform = $this->_platform;
        return $this->processCustomDoctrineTypesColumnDefinition(tableColumn: $tableColumn, platform: $platform)
            ?? parent::_getPortableTableColumnDefinition(tableColumn: $tableColumn);
    }

    /**
     * @param array<int, array<string, mixed>> $tableIndexes
     * @param string|null $tableName
     *
     * @return array<string, Index>
     *
     * @todo Change signature to `(array $tableIndexes, string $tableName)` with doctrine/dbal 4.0
     */
    protected function _getPortableTableIndexesList($tableIndexes, $tableName = null): array
    {
        // Get doctrine generated list.
        $tableIndexesList = parent::_getPortableTableIndexesList(
            tableIndexes: $tableIndexes,
            tableName: $tableName,
        );

        // Enrich tablesIndexesList with custom index handlings.
        return $this->customGetPortableTableIndexesList(
            tableIndexesList: $tableIndexesList,
            tableIndexes: $tableIndexes,
            tableName: $tableName,
            connection: $this->_conn,
        );
    }

    /**
     * @todo Migrate usage of this and remove this. Will be removed with doctrine/dbal 4.0.
     */
    public function getDatabasePlatform(): DoctrineMariaDBPlatform|DoctrineMySQLPlatform
    {
        /** @var DoctrineMariaDBPlatform|DoctrineMySQLPlatform $platform */
        $platform = $this->_platform;
        return $platform;
    }
}
