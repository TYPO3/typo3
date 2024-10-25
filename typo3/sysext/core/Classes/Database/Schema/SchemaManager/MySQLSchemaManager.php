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

use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Platforms\MariaDBPlatform;
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
    use ColumnTypeCommentMethodsTrait;

    /** @see https://mariadb.com/kb/en/library/string-literals/#escape-sequences */
    private const MARIADB_ESCAPE_SEQUENCES = [
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

        // Internally, MariaDB escapes single quotes using the standard syntax
        "''" => "'",
    ];

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
            ?? $this->parentGetPortableTableColumnDefinition($tableColumn);
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

    protected function getMySQLTextAndBlobColumnDefault(?string $columnDefault): ?string
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

        // Concatenate index prefix length to column name
        // @todo Adapt TYPO3 schema comparison to use Index::getOption('lengths')
        //       instead of assuming that the length is concatenated to the column name.
        return array_map(
            static function (Index $index): Index {
                if (!$index->hasOption('lengths')) {
                    return $index;
                }

                $options = $index->getOptions();
                $lengths = $options['lengths'];
                unset($options['lengths']);

                $columns = $index->getColumns();
                foreach ($columns as $id => $column) {
                    if (!isset($lengths[$id])) {
                        continue;
                    }
                    $columns[$id] = $column . '(' . $lengths[$id] . ')';
                }

                return new Index(
                    $index->getName(),
                    $columns,
                    $index->isUnique(),
                    $index->isPrimary(),
                    $index->getFlags(),
                    $options
                );
            },
            $tableIndexesList
        );
    }

    /**
     * Gets Table Column Definition.
     *
     * This is a copy of {@see DoctrineMySQLSchemaManager::_getPortableTableColumnDefinition()} with a minor change
     * to respect column comments for Doctrine Type matching and thus restoring Doctrine DBAL behaviour before v4.x.
     *
     * @param array $tableColumn
     *
     * @throws Exception
     */
    private function parentGetPortableTableColumnDefinition(array $tableColumn): Column
    {
        $tableColumn = array_change_key_case($tableColumn, CASE_LOWER);

        $dbType = strtolower($tableColumn['type']);
        $dbType = strtok($dbType, '(), ');
        assert(is_string($dbType));

        $length = $tableColumn['length'] ?? strtok('(), ');

        $fixed = false;

        if (! isset($tableColumn['name'])) {
            $tableColumn['name'] = '';
        }

        $scale     = 0;
        $precision = null;

        // Following line differs from \Doctrine\DBAL\Schema\MySQLSchemaManager::_getPortableTableColumnDefinition,
        // taken from:
        // - https://github.com/doctrine/dbal/blob/61446f07fcb522414d6cfd8b1c3e5f9e18c579ba/src/Schema/MySQLSchemaManager.php#L186-L192
        // - https://github.com/doctrine/dbal/blob/61446f07fcb522414d6cfd8b1c3e5f9e18c579ba/src/Schema/PostgreSQLSchemaManager.php#L427-L429
        $type = $this->determineColumnType($dbType, $tableColumn);

        switch ($dbType) {
            case 'char':
            case 'binary':
                $fixed = true;
                break;

            case 'float':
            case 'double':
            case 'real':
            case 'numeric':
            case 'decimal':
                if (
                    preg_match(
                        '([A-Za-z]+\(([0-9]+),([0-9]+)\))',
                        $tableColumn['type'],
                        $match,
                    ) === 1
                ) {
                    $precision = (int)$match[1];
                    $scale     = (int)$match[2];
                    $length    = null;
                }

                break;

            case 'tinytext':
                $length = AbstractMySQLPlatform::LENGTH_LIMIT_TINYTEXT;
                break;

            case 'text':
                $length = AbstractMySQLPlatform::LENGTH_LIMIT_TEXT;
                break;

            case 'mediumtext':
                $length = AbstractMySQLPlatform::LENGTH_LIMIT_MEDIUMTEXT;
                break;

            case 'tinyblob':
                $length = AbstractMySQLPlatform::LENGTH_LIMIT_TINYBLOB;
                break;

            case 'blob':
                $length = AbstractMySQLPlatform::LENGTH_LIMIT_BLOB;
                break;

            case 'mediumblob':
                $length = AbstractMySQLPlatform::LENGTH_LIMIT_MEDIUMBLOB;
                break;

            case 'tinyint':
            case 'smallint':
            case 'mediumint':
            case 'int':
            case 'integer':
            case 'bigint':
            case 'year':
                $length = null;
                break;
        }

        if ($this->platform instanceof MariaDBPlatform) {
            $columnDefault = $this->getMariaDBColumnDefault($this->platform, $tableColumn['default']);
        } else {
            $columnDefault = $tableColumn['default'];
        }

        $options = [
            'length'        => $length !== null ? (int)$length : null,
            'unsigned'      => str_contains($tableColumn['type'], 'unsigned'),
            'fixed'         => $fixed,
            'default'       => $columnDefault,
            'notnull'       => $tableColumn['null'] !== 'YES',
            'scale'         => $scale,
            'precision'     => $precision,
            'autoincrement' => str_contains($tableColumn['extra'], 'auto_increment'),
        ];

        if (isset($tableColumn['comment'])) {
            $options['comment'] = $tableColumn['comment'];
        }

        $column = new Column($tableColumn['field'], Type::getType($type), $options);

        if (isset($tableColumn['characterset'])) {
            $column->setPlatformOption('charset', $tableColumn['characterset']);
        }

        if (isset($tableColumn['collation'])) {
            $column->setPlatformOption('collation', $tableColumn['collation']);
        }

        return $column;
    }

    /**
     * Return Doctrine/Mysql-compatible column default values for MariaDB 10.2.7+ servers.
     *
     * - Since MariaDb 10.2.7 column defaults stored in information_schema are now quoted
     *   to distinguish them from expressions (see MDEV-10134).
     * - CURRENT_TIMESTAMP, CURRENT_TIME, CURRENT_DATE are stored in information_schema
     *   as current_timestamp(), currdate(), currtime()
     * - Quoted 'NULL' is not enforced by Maria, it is technically possible to have
     *   null in some circumstances (see https://jira.mariadb.org/browse/MDEV-14053)
     * - \' is always stored as '' in information_schema (normalized)
     *
     * @link https://mariadb.com/kb/en/library/information-schema-columns-table/
     * @link https://jira.mariadb.org/browse/MDEV-13132
     *
     * Copy of {@see DoctrineMySQLSchemaManager::getMariaDBColumnDefault()}
     *
     * @param string|null $columnDefault default value as stored in information_schema for MariaDB >= 10.2.7
     */
    private function getMariaDBColumnDefault(MariaDBPlatform $platform, ?string $columnDefault): ?string
    {
        if ($columnDefault === 'NULL' || $columnDefault === null) {
            return null;
        }

        if (preg_match('/^\'(.*)\'$/', $columnDefault, $matches) === 1) {
            return strtr($matches[1], self::MARIADB_ESCAPE_SEQUENCES);
        }

        return match ($columnDefault) {
            'current_timestamp()' => $platform->getCurrentTimestampSQL(),
            'curdate()' => $platform->getCurrentDateSQL(),
            'curtime()' => $platform->getCurrentTimeSQL(),
            default => $columnDefault,
        };
    }
}
