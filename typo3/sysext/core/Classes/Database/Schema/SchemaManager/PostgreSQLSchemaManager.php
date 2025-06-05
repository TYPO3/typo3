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
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\PostgreSQLSchemaManager as DoctrinePostgreSQLSchemaManager;
use Doctrine\DBAL\Types\JsonType;
use Doctrine\DBAL\Types\Type;

/**
 * Extending the doctrine PostgreSQLSchemaManager to integrate additional processing stuff
 * due to the dropped event system with `doctrine/dbal 4.x`.
 *
 * For example, this is used to process custom doctrine types.
 *
 * Platform specific SchemaManager are extended to manipulate the schema handling. TYPO3 needs to
 *  do that to provide additional doctrine type handling and other workarounds or alignments. Long
 *  time this have been done by using the `doctrine EventManager` to hook into several places, which
 *  no longer exists.
 *
 * @link https://github.com/doctrine/dbal/blob/3.7.x/UPGRADE.md#deprecated-not-setting-a-schema-manager-factory
 * @link https://github.com/doctrine/dbal/blob/3.7.x/UPGRADE.md#deprecated-extension-via-doctrine-event-manager
 *
 * @internal not part of the public Core API.
 */
class PostgreSQLSchemaManager extends DoctrinePostgreSQLSchemaManager
{
    use ColumnTypeCommentMethodsTrait;

    /**
     * Gets Table Column Definition.
     *
     * @param array<string, mixed> $tableColumn
     */
    protected function _getPortableTableColumnDefinition(array $tableColumn): Column
    {
        return $this->parentGetPortableTableColumnDefinition($tableColumn);
    }

    /**
     * Gets Table Column Definition.
     *
     * This is a copy of {@see DoctrinePostgreSQLSchemaManager::_getPortableTableColumnDefinition()} with a minor change
     * to respect column comments for Doctrine Type matching and thus restoring Doctrine DBAL behaviour before v4.x.
     *
     * @param array $tableColumn
     *
     * @throws Exception
     */
    protected function parentGetPortableTableColumnDefinition(array $tableColumn): Column
    {
        $tableColumn = array_change_key_case($tableColumn, CASE_LOWER);

        $length    = null;
        $precision = null;
        $scale     = 0;
        $fixed     = false;
        $jsonb     = false;

        $dbType = $tableColumn['type'];

        if (
            $tableColumn['domain_type'] !== null
            && ! $this->platform->hasDoctrineTypeMappingFor($dbType)
        ) {
            $dbType       = $tableColumn['domain_type'];
            $completeType = $tableColumn['domain_complete_type'];
        } else {
            $completeType = $tableColumn['complete_type'];
        }

        // This is the change required for TYPO3 - rest of method is kept (cloned) from original.
        // Following line differs from \Doctrine\DBAL\Schema\MySQLSchemaManager::_getPortableTableColumnDefinition,
        // taken from:
        // - https://github.com/doctrine/dbal/blob/61446f07fcb522414d6cfd8b1c3e5f9e18c579ba/src/Schema/PostgreSQLSchemaManager.php#L427-L429
        $type = $this->determineColumnType($dbType, $tableColumn);

        switch ($dbType) {
            case 'bpchar':
            case 'varchar':
                $parameters = $this->parseColumnTypeParameters($completeType);
                if (count($parameters) > 0) {
                    $length = $parameters[0];
                }

                break;

            case 'double':
            case 'decimal':
            case 'money':
            case 'numeric':
                $parameters = $this->parseColumnTypeParameters($completeType);
                if (count($parameters) > 0) {
                    $precision = $parameters[0];
                }

                if (count($parameters) > 1) {
                    $scale = $parameters[1];
                }

                break;
        }

        if ($dbType === 'bpchar') {
            $fixed = true;
        } elseif ($dbType === 'jsonb') {
            $jsonb = true;
        }

        $options = [
            'length'        => $length,
            'notnull'       => (bool)$tableColumn['isnotnull'],
            'default'       => $this->parseDefaultExpression($tableColumn['default']),
            'precision'     => $precision,
            'scale'         => $scale,
            'fixed'         => $fixed,
            'autoincrement' => $tableColumn['attidentity'] === 'd',
        ];

        if ($tableColumn['comment'] !== null) {
            $options['comment'] = $tableColumn['comment'];
        }

        $column = new Column($tableColumn['field'], Type::getType($type), $options);

        if (! empty($tableColumn['collation'])) {
            $column->setPlatformOption('collation', $tableColumn['collation']);
        }

        if ($column->getType() instanceof JsonType) {
            $column->setPlatformOption('jsonb', $jsonb);
        }

        return $column;
    }

    /**
     * Parses a default value expression as given by PostgreSQL
     *
     * Copy of {@see DoctrinePostgreSQLSchemaManager::parseDefaultExpression()} (Doctrine DBAL 4.3.x)
     */
    private function parseDefaultExpression(?string $expression): mixed
    {
        if ($expression === null || str_starts_with($expression, 'NULL::')) {
            return null;
        }

        if ($expression === 'true') {
            return true;
        }

        if ($expression === 'false') {
            return false;
        }

        if (preg_match("/^'(.*)'::/s", $expression, $matches) === 1) {
            return str_replace("''", "'", $matches[1]);
        }

        return $expression;
    }

    /**
     * Parses the parameters between parenthesis in the data type.
     *
     * Copy of {@see DoctrinePostgreSQLSchemaManager::parseColumnTypeParameters()}
     *
     * @return list<int>
     */
    private function parseColumnTypeParameters(string $type): array
    {
        if (preg_match('/\((\d+)(?:,(\d+))?\)/', $type, $matches) !== 1) {
            return [];
        }

        $parameters = [(int)$matches[1]];

        if (isset($matches[2])) {
            $parameters[] = (int)$matches[2];
        }

        return $parameters;
    }
}
