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
 */
trait CustomDoctrineTypesColumnDefinitionTrait
{
    /**
     * This method is used to handle additional processing for custom doctrine types.
     *
     * Note: `doctrine/dbal` dropped the event listener system, and this replaces the
     *        used `onSchemaColumnDefinition()` event lower than `doctrine/dbal 4.x`.
     */
    protected function processCustomDoctrineTypesColumnDefinition(
        array $tableColumn,
        AbstractPlatform $platform,
    ): Column|null {
        $tableColumn = array_change_key_case($tableColumn, CASE_LOWER);
        $dbType = $this->getDatabaseType($tableColumn['type']);
        if ($dbType !== 'enum' && $dbType !== 'set') {
            return null;
        }

        $options = [
            'length' => $tableColumn['length'] ?? null,
            'unsigned' => false,
            'fixed' => false,
            'default' => $tableColumn['default'] ?? null,
            'notnull' => ($tableColumn['null'] ?? '') !== 'YES',
            'scale' => 0,
            'precision' => null,
            'autoincrement' => false,
            'comment' => (string)($tableColumn['comment'] ?? ''),
        ];

        $dbType = $this->getDatabaseType($tableColumn['type']);
        $doctrineType = $platform->getDoctrineTypeMapping($dbType);

        $column = new Column($tableColumn['field'] ?? '', Type::getType($doctrineType), $options);
        $column->setPlatformOption('unquotedValues', $this->getUnquotedEnumerationValues($tableColumn['type']));

        return $column;
    }

    /**
     * Extract the field type from the definition string
     */
    protected function getDatabaseType(string $typeDefinition): string
    {
        $dbType = strtolower($typeDefinition);
        $dbType = strtok($dbType, '(), ');

        return $dbType;
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
