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
use Doctrine\DBAL\Types\BlobType;
use Doctrine\DBAL\Types\JsonType;
use Doctrine\DBAL\Types\TextType;
use Doctrine\DBAL\Types\Type;

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
    private const MYSQL_ESCAPE_SEQUENCES = [
        '\\0' => "\0",
        "\\'" => "'",
        '\\"' => '"',
        '\\b' => "\b",
        '\\n' => "\n",
        '\\r' => "\r",
        '\\t' => "\t",
        '\\Z' => "\x1a",
        '\\\\' => '\\',
        '\\%' => '%',
        '\\_' => '_',

        // internally
        "''" => "'",
    ];

    private const MYSQL_UNQUOTE_SEQUENCES = [
        "\\'" => "'",
        '\\"' => '"',
    ];

    /**
     * Gets Table Column Definition.
     *
     * @param array<string, mixed> $tableColumn
     */
    protected function _getPortableTableColumnDefinition(array $tableColumn): Column
    {
        /** @var DoctrineMariaDBPlatform|DoctrineMySQLPlatform $platform */
        $platform = $this->platform;
        $tableColumn = $this->normalizeTableColumnData($tableColumn, $platform);
        return $this->processCustomDoctrineTypesColumnDefinition($tableColumn, $platform)
            ?? parent::_getPortableTableColumnDefinition($tableColumn);
    }

    /**
     * @param array<string, mixed> $tableColumn
     * @return array<string, mixed>
     */
    protected function normalizeTableColumnData(array $tableColumn, DoctrineMariaDBPlatform|DoctrineMySQLPlatform $platform): array
    {
        if (!($platform instanceof DoctrineMySQLPlatform)) {
            return $tableColumn;
        }

        $tableColumn = array_change_key_case($tableColumn, CASE_LOWER);
        $dbType = strtolower($tableColumn['type']);
        $dbType = strtok($dbType, '(), ');
        assert(is_string($dbType));

        $columnDefault = $tableColumn['default'] ?? null;
        $type = Type::getType($platform->getDoctrineTypeMapping($dbType));
        if ($type instanceof TextType || $type instanceof BlobType || $type instanceof JsonType) {
            $tableColumn['default'] = $this->getMySQLTextAndBlobColumnDefault($columnDefault);
        }

        return $tableColumn;
    }

    protected function getMySQLTextAndBlobColumnDefault(string|null $columnDefault): string|null
    {
        if ($columnDefault === null || $columnDefault === 'NULL') {
            return null;
        }
        if (str_starts_with($columnDefault, '_')) {
            $columnDefault = substr($columnDefault, (mb_strpos($columnDefault, '\'') - 1));
        }
        if ($columnDefault === "\'\'") {
            return '';
        }
        if (preg_match("/^\\\'(.*)\\\'$/", trim($columnDefault), $matches) === 1) {
            return strtr(
                strtr($matches[1], self::MYSQL_ESCAPE_SEQUENCES),
                // MySQL saves quoted single-quote as escaped single-quote in the INFORMATION SCHEMA table, even
                // if it has been provided with double-quote quoting and is inconsistent for itself and enforces
                // a additional unquoting after the un-escaping step
                self::MYSQL_UNQUOTE_SEQUENCES
            );
        }
        return $columnDefault;
    }

    /**
     * @param array<int, array<string, mixed>> $tableIndexes
     * @param string $tableName
     *
     * @return array<string, Index>
     */
    protected function _getPortableTableIndexesList(array $tableIndexes, string $tableName): array
    {
        // Get doctrine generated list.
        $tableIndexesList = parent::_getPortableTableIndexesList(
            // tableIndexes
            $tableIndexes,
            // tableName
            $tableName,
        );

        // Enrich tablesIndexesList with custom index handlings.
        return $this->customGetPortableTableIndexesList(
            // tableIndexesList
            $tableIndexesList,
            // tableIndexes
            $tableIndexes,
            // tableName
            $tableName,
            // connection
            $this->connection,
        );
    }
}
