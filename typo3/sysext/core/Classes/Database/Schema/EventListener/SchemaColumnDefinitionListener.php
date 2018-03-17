<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Core\Database\Schema\EventListener;

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

use Doctrine\DBAL\Event\SchemaColumnDefinitionEventArgs;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Types\Type;

/**
 * Event listener to handle additional processing for custom
 * doctrine types.
 */
class SchemaColumnDefinitionListener
{
    /**
     * Listener for column definition events. This intercepts definitions
     * for custom doctrine types and builds the appropriate Column Object.
     *
     * @param \Doctrine\DBAL\Event\SchemaColumnDefinitionEventArgs $event
     * @throws \Doctrine\DBAL\DBALException
     */
    public function onSchemaColumnDefinition(SchemaColumnDefinitionEventArgs $event)
    {
        $tableColumn = $event->getTableColumn();
        $tableColumn = array_change_key_case($tableColumn, CASE_LOWER);

        $dbType = $this->getDatabaseType($tableColumn['type']);
        if ($dbType !== 'enum' && $dbType !== 'set') {
            return;
        }

        $column = $this->getEnumerationTableColumnDefinition(
            $tableColumn,
            $event->getDatabasePlatform()
        );

        $event->setColumn($column);
        $event->preventDefault();
    }

    /**
     * Build a Doctrine column object for TYPE/TYPE columns.
     *
     * @param array $tableColumn
     * @param \Doctrine\DBAL\Platforms\AbstractPlatform $platform
     * @return \Doctrine\DBAL\Schema\Column
     * @throws \Doctrine\DBAL\DBALException
     * @todo: The $tableColumn source currently only support MySQL definition style.
     */
    protected function getEnumerationTableColumnDefinition(array $tableColumn, AbstractPlatform $platform): Column
    {
        $options = [
            'length' => $tableColumn['length'] ?? null,
            'unsigned' => false,
            'fixed' => false,
            'default' => $tableColumn['default'] ?? null,
            'notnull' => ($tableColumn['null'] ?? '') !== 'YES',
            'scale' => null,
            'precision' => null,
            'autoincrement' => false,
            'comment' => $tableColumn['comment'] ?? null,
        ];

        $dbType = $this->getDatabaseType($tableColumn['type']);
        $doctrineType = $platform->getDoctrineTypeMapping($dbType);

        $column = new Column($tableColumn['field'] ?? null, Type::getType($doctrineType), $options);
        $column->setPlatformOption('unquotedValues', $this->getUnquotedEnumerationValues($tableColumn['type']));

        return $column;
    }

    /**
     * Extract the field type from the definition string
     *
     * @param string $typeDefiniton
     * @return string
     */
    protected function getDatabaseType(string $typeDefiniton): string
    {
        $dbType = strtolower($typeDefiniton);
        $dbType = strtok($dbType, '(), ');

        return $dbType;
    }

    /**
     * @param string $typeDefiniton
     * @return array
     */
    protected function getUnquotedEnumerationValues(string $typeDefiniton): array
    {
        $valuesDefinition = preg_replace('#^(enum|set)\((.*)\)\s*$#i', '$2', $typeDefiniton);
        $quoteChar = $valuesDefinition[0];
        $separator = $quoteChar . ',' . $quoteChar;

        $valuesDefinition = preg_replace(
            '#' . $quoteChar . ',\s*' . $quoteChar . '#',
            $separator,
            $valuesDefinition
        );

        $values = explode($quoteChar . ',' . $quoteChar, substr($valuesDefinition, 1, -1));

        return array_map(
            function (string $value) use ($quoteChar) {
                return str_replace($quoteChar . $quoteChar, $quoteChar, $value);
            },
            $values
        );
    }
}
