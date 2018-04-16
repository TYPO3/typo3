<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Core\Database\Query;

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

use Doctrine\DBAL\Connection;

/**
 * Provides functionality to generate and execute row based bulk INSERT statements.
 *
 * Based on work by Steve Müller <st.mueller@dzh-online.de> for the Doctrine project,
 * licensend under the MIT license.
 *
 * This class will be removed from core and the functionality will be provided by
 * the upstream implemention once the pull request has been merged into Doctrine DBAL.
 *
 * @see https://github.com/doctrine/dbal/pull/682
 * @internal
 */
class BulkInsertQuery
{
    /**
     * @var string[]
     */
    protected $columns;

    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @var string
     */
    protected $table;

    /**
     * @var array
     */
    protected $parameters = [];

    /**
     * @var array
     */
    protected $types = [];

    /**
     * @var array
     */
    protected $values = [];

    /**
     * Constructor.
     *
     * @param Connection $connection The connection to use for query execution.
     * @param string $table The name of the table to insert rows into.
     * @param string[] $columns The names of the columns to insert values into.
     *                          Can be left empty to allow arbitrary row inserts based on the table's column order.
     */
    public function __construct(Connection $connection, string $table, array $columns = [])
    {
        $this->connection = $connection;
        $this->table = $connection->quoteIdentifier($table);
        $this->columns = $columns;
    }

    /**
     * Render the bulk insert statement as string.
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->getSQL();
    }

    /**
     * Adds a set of values to the bulk insert query to be inserted as a row into the specified table.
     *
     * @param array $values The set of values to be inserted as a row into the table.
     *                      If no columns have been specified for insertion, this can be
     *                      an arbitrary list of values to be inserted into the table.
     *                      Otherwise the values' keys have to match either one of the
     *                      specified column names or indexes.
     * @param array $types The types for the given values to bind to the query.
     *                     If no columns have been specified for insertion, the types'
     *                     keys will be matched against the given values' keys.
     *                     Otherwise the types' keys will be matched against the
     *                     specified column names and indexes.
     *                     Non-matching keys will be discarded, missing keys will not
     *                     be bound to a specific type.
     *
     * @throws \InvalidArgumentException if columns were specified for this query
     *                                   and either no value for one of the specified
     *                                   columns is given or multiple values are given
     *                                   for a single column (named and indexed) or
     *                                   multiple types are given for a single column
     *                                   (named and indexed).
     */
    public function addValues(array $values, array $types = [])
    {
        $valueSet = [];

        if (empty($this->columns)) {
            foreach ($values as $index => $value) {
                $this->parameters[] = $value;
                $this->types[] = isset($types[$index]) ? $types[$index] : null;
                $valueSet[] = '?';
            }

            $this->values[] = $valueSet;

            return;
        }

        foreach ($this->columns as $index => $column) {
            $namedValue = isset($values[$column]) || array_key_exists($column, $values);
            $positionalValue = isset($values[$index]) || array_key_exists($index, $values);

            if (!$namedValue && !$positionalValue) {
                throw new \InvalidArgumentException(
                    sprintf('No value specified for column %s (index %d).', $column, $index),
                    1476049651
                );
            }

            if ($namedValue && $positionalValue && $values[$column] !== $values[$index]) {
                throw new \InvalidArgumentException(
                    sprintf('Multiple values specified for column %s (index %d).', $column, $index),
                    1476049652
                );
            }

            $this->parameters[] = $namedValue ? $values[$column] : $values[$index];
            $valueSet[] = '?';

            $namedType = isset($types[$column]);
            $positionalType = isset($types[$index]);

            if ($namedType && $positionalType && $types[$column] !== $types[$index]) {
                throw new \InvalidArgumentException(
                    sprintf('Multiple types specified for column %s (index %d).', $column, $index),
                    1476049653
                );
            }

            if ($namedType) {
                $this->types[] = $types[$column];

                continue;
            }

            if ($positionalType) {
                $this->types[] = $types[$index];

                continue;
            }

            $this->types[] = null;
        }

        $this->values[] = $valueSet;
    }

    /**
     * Executes this INSERT query using the bound parameters and their types.
     *
     * @return int The number of affected rows.
     *
     * @throws \LogicException if this query contains more rows than acceptable
     *                         for a single INSERT statement by the underlying platform.
     */
    public function execute(): int
    {
        $platform = $this->connection->getDatabasePlatform();
        $insertMaxRows = $this->getInsertMaxRows();

        if ($insertMaxRows > 0 && count($this->values) > $insertMaxRows) {
            throw new \LogicException(
                sprintf(
                    'You can only insert %d rows in a single INSERT statement with platform "%s".',
                    $insertMaxRows,
                    $platform->getName()
                ),
                1476049654
            );
        }

        return $this->connection->executeUpdate($this->getSQL(), $this->parameters, $this->types);
    }

    /**
     * Return the maximum number of rows that can be inserted at the same time.
     *
     * @return int
     */
    protected function getInsertMaxRows(): int
    {
        $platform = $this->connection->getDatabasePlatform();
        if ($platform->getName() === 'mssql' && $platform->getReservedKeywordsList()->isKeyword('MERGE')) {
            return 1000;
        }

        return 0;
    }

    /**
     * Returns the parameters for this INSERT query being constructed indexed by parameter index.
     *
     * @return array
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * Returns the parameter types for this INSERT query being constructed indexed by parameter index.
     *
     * @return array
     */
    public function getParameterTypes(): array
    {
        return $this->types;
    }

    /**
     * Returns the SQL formed by the current specifications of this INSERT query.
     *
     * @return string
     *
     * @throws \LogicException if no values have been specified yet.
     */
    public function getSQL(): string
    {
        if (empty($this->values)) {
            throw new \LogicException(
                'You need to add at least one set of values before generating the SQL.',
                1476049702
            );
        }

        $connection = $this->connection;
        $columnList = '';

        if (!empty($this->columns)) {
            $columnList = sprintf(
                ' (%s)',
                implode(
                    ', ',
                    array_map(
                        function ($column) use ($connection) {
                            return $connection->quoteIdentifier($column);
                        },
                        $this->columns
                    )
                )
            );
        }

        return sprintf(
            'INSERT INTO %s%s VALUES (%s)',
            $this->table,
            $columnList,
            implode(
                '), (',
                array_map(
                    function (array $valueSet) {
                        return implode(', ', $valueSet);
                    },
                    $this->values
                )
            )
        );
    }
}
