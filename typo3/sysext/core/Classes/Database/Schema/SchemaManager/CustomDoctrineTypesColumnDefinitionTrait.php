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

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\MariaDBPlatform as DoctrineMariaDBPlatform;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Types\Type;

/**
 * Provide shared custom doctrine types processing to all extended SchemaManager classes:
 *
 *  - MySQLSchemaManager
 *  - SQLiteSchemaManager
 *  - PostgreSQLSchemaManager
 *
 * @internal only for use in extended SchemaManager classes and not part of public core API.
 *
 * @todo ENUM and SET does not work for SQLite and PostgresSQL. SQLite supports it with a slightly other syntax and
 *       PostgreSQL needs to create a custom type with a human-readable name, which is not reasonable either. Consider
 *       to deprecate and drop ENUM/SET support due not having compatibility for all supported database systems.
 *         * @see https://www.postgresql.org/docs/current/datatype-enum.html#DATATYPE-ENUM (PostgreSQL)
 *         * @see https://www.sqlite.org/lang_createtable.html#ckconst (SQlite)
 *         * @see https://stackoverflow.com/questions/5299267/how-to-create-enum-type-in-sqlite
 */
trait CustomDoctrineTypesColumnDefinitionTrait
{
    use ColumnTypeCommentMethodsTrait;

    /**
     * This method is used to handle additional processing for custom doctrine types.
     *
     * Note: `doctrine/dbal` dropped the event listener system, and this replaces the
     *        used `onSchemaColumnDefinition()` event lower than `doctrine/dbal 4.x`.
     */
    protected function processCustomDoctrineTypesColumnDefinition(
        array $tableColumn,
        AbstractPlatform $platform,
    ): ?Column {
        $tableColumn = array_change_key_case($tableColumn);
        $dbType = $this->getDatabaseType($tableColumn['type']);
        if ($dbType !== 'enum' && $dbType !== 'set') {
            return null;
        }
        $default = $tableColumn['default'] ?? null;
        // Doctrine DBAL retrieves for MariaDB `ENUM()` and `SET()` field default values quotes with single quotes,
        // which leads to an endless field change reporting recursion in the database analyzer. The default value
        // is now trimmed to ensure a working field compare within `TYPO3\CMS\Core\Database\Schema\Comparator`.
        if ($platform instanceof DoctrineMariaDBPlatform
            && is_string($default)
            && $default !== ''
            && str_starts_with($default, "'")
            && str_ends_with($default, "'")
        ) {
            $default = trim($default, "'");
        }
        $options = [
            'length' => $tableColumn['length'] ?? null,
            'unsigned' => false,
            'fixed' => false,
            'default' => $default,
            'notnull' => ($tableColumn['null'] ?? '') !== 'YES',
            'scale' => 0,
            'precision' => null,
            'autoincrement' => false,
            'comment' => (string)($tableColumn['comment'] ?? ''),
        ];

        $doctrineType = $this->determineColumnType($dbType, $tableColumn);

        $column = new Column($tableColumn['field'] ?? '', Type::getType($doctrineType), $options);
        $column->setValues($this->getUnquotedEnumerationValues($tableColumn['type']));

        return $column;
    }

    /**
     * Extract the field type from the definition string
     */
    protected function getDatabaseType(string $typeDefinition): string
    {
        $dbType = strtolower($typeDefinition);
        return strtok($dbType, '(), ');
    }

    protected function getUnquotedEnumerationValues(string $typeDefinition): array
    {
        $valuesDefinition = preg_replace('#^(enum|set)\((.*)\)\s*$#i', '$2', $typeDefinition) ?? '';
        $quoteChar = $valuesDefinition[0];
        $separator = $quoteChar . ',' . $quoteChar;

        $valuesDefinition = preg_replace(
            '#' . $quoteChar . ',\s*' . $quoteChar . '#',
            $separator,
            $valuesDefinition
        ) ?? '';

        $values = explode($quoteChar . ',' . $quoteChar, substr($valuesDefinition, 1, -1));

        return array_map(
            static function (string $value) use ($quoteChar): string {
                return str_replace($quoteChar . $quoteChar, $quoteChar, $value);
            },
            $values
        );
    }
}
