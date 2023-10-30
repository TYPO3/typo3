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

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaDiff;
use Doctrine\DBAL\Schema\SchemaException;
use Doctrine\DBAL\Schema\Sequence;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\BlobType;
use Doctrine\DBAL\Types\TextType;
use TYPO3\CMS\Core\Utility\ArrayUtility;

/**
 * Compares two Schemas and returns an instance of SchemaDiff.
 *
 * @internal
 */
class Comparator extends \Doctrine\DBAL\Schema\Comparator
{
    /**
     * @var AbstractPlatform|null
     */
    protected $databasePlatform;

    /**
     * Comparator constructor.
     */
    public function __construct(AbstractPlatform $platform = null)
    {
        $this->databasePlatform = $platform;
        parent::__construct($platform);
    }

    /**
     * Returns the difference between the tables $fromTable and $toTable.
     *
     * If there are no differences this method returns the boolean false.
     *
     * @param \Doctrine\DBAL\Schema\Table $fromTable
     * @param \Doctrine\DBAL\Schema\Table $toTable
     * @return false|\Doctrine\DBAL\Schema\TableDiff|\TYPO3\CMS\Core\Database\Schema\TableDiff
     * @throws \InvalidArgumentException
     */
    public function diffTable(Table $fromTable, Table $toTable)
    {
        $newTableOptions = array_merge($fromTable->getOptions(), $toTable->getOptions());
        $optionDiff = ArrayUtility::arrayDiffAssocRecursive($newTableOptions, $fromTable->getOptions());
        $tableDifferences = parent::diffTable($fromTable, $toTable);

        // No changed table options, return parent result
        if (count($optionDiff) === 0) {
            return $tableDifferences;
        }

        if ($tableDifferences === false) {
            $tableDifferences = new TableDiff($fromTable->getName());
            $tableDifferences->fromTable = $fromTable;
        } else {
            $renamedColumns = $tableDifferences->renamedColumns;
            $renamedIndexes = $tableDifferences->renamedIndexes;
            // Rebuild TableDiff with enhanced TYPO3 TableDiff class
            $tableDifferences = new TableDiff(
                $tableDifferences->name,
                $tableDifferences->addedColumns,
                $tableDifferences->changedColumns,
                $tableDifferences->removedColumns,
                $tableDifferences->addedIndexes,
                $tableDifferences->changedIndexes,
                $tableDifferences->removedIndexes,
                $tableDifferences->fromTable
            );
            $tableDifferences->renamedColumns = $renamedColumns;
            $tableDifferences->renamedIndexes = $renamedIndexes;
        }

        // Set the table options to be parsed in the AlterTable event.
        $tableDifferences->setTableOptions($optionDiff);

        return $tableDifferences;
    }

    /**
     * Returns the difference between the columns $column1 and $column2
     * by first checking the doctrine diffColumn. Extend the Doctrine
     * method by taking into account MySQL TINY/MEDIUM/LONG type variants.
     *
     * @return array
     */
    public function diffColumn(Column $column1, Column $column2)
    {
        $changedProperties = parent::diffColumn($column1, $column2);

        // Only MySQL has variable length versions of TEXT/BLOB
        if (!$this->databasePlatform instanceof MySQLPlatform) {
            return $changedProperties;
        }

        $properties1 = $column1->toArray();
        $properties2 = $column2->toArray();

        if ($properties1['type'] instanceof BlobType || $properties1['type'] instanceof TextType) {
            // Doctrine does not provide a length for LONGTEXT/LONGBLOB columns
            $length1 = $properties1['length'] ?: 2147483647;
            $length2 = $properties2['length'] ?: 2147483647;

            if ($length1 !== $length2) {
                $changedProperties[] = 'length';
            }
        }

        return array_unique($changedProperties);
    }

    /**
     * Returns a SchemaDiff object containing the differences between the schemas
     * $fromSchema and $toSchema.
     *
     * This method should be called non-statically since it will be declared as
     * non-static in the next doctrine/dbal major release.
     *
     * doctrine/dbal uses new self() in this method, which instantiate the doctrine
     * Comparator class and not our extended class, thus not calling our overridden
     * methods 'diffTable()' and 'diffColum()' and breaking core table engine support,
     * which is tested in core functional tests explicity. See corresponding test
     * `TYPO3\CMS\Core\Tests\Functional\Database\Schema\SchemaMigratorTest->changeTableEngine()`
     *
     * @return SchemaDiff
     * @throws SchemaException
     *
     * @todo Create PR for doctrine/dbal to change to late binding 'new static()', so our
     *       override is working correctly and remove this method after min package raise,
     *       if PR was accepted and merged. Also remove 'isAutoIncrementSequenceInSchema()'.
     */
    public static function compareSchemas(
        Schema $fromSchema,
        Schema $toSchema
    ) {
        $comparator       = new self();
        $diff             = new SchemaDiff();
        $diff->fromSchema = $fromSchema;

        $foreignKeysToTable = [];

        foreach ($toSchema->getNamespaces() as $namespace) {
            if ($fromSchema->hasNamespace($namespace)) {
                continue;
            }

            $diff->newNamespaces[$namespace] = $namespace;
        }

        foreach ($fromSchema->getNamespaces() as $namespace) {
            if ($toSchema->hasNamespace($namespace)) {
                continue;
            }

            $diff->removedNamespaces[$namespace] = $namespace;
        }

        foreach ($toSchema->getTables() as $table) {
            $tableName = $table->getShortestName($toSchema->getName());
            if (!$fromSchema->hasTable($tableName)) {
                $diff->newTables[$tableName] = $toSchema->getTable($tableName);
            } else {
                $tableDifferences = $comparator->diffTable(
                    $fromSchema->getTable($tableName),
                    $toSchema->getTable($tableName)
                );

                if ($tableDifferences !== false) {
                    $diff->changedTables[$tableName] = $tableDifferences;
                }
            }
        }

        /* Check if there are tables removed */
        foreach ($fromSchema->getTables() as $table) {
            $tableName = $table->getShortestName($fromSchema->getName());

            $table = $fromSchema->getTable($tableName);
            if (!$toSchema->hasTable($tableName)) {
                $diff->removedTables[$tableName] = $table;
            }

            // also remember all foreign keys that point to a specific table
            foreach ($table->getForeignKeys() as $foreignKey) {
                $foreignTable = strtolower($foreignKey->getForeignTableName());
                if (!isset($foreignKeysToTable[$foreignTable])) {
                    $foreignKeysToTable[$foreignTable] = [];
                }

                $foreignKeysToTable[$foreignTable][] = $foreignKey;
            }
        }

        foreach ($diff->removedTables as $tableName => $table) {
            if (!isset($foreignKeysToTable[$tableName])) {
                continue;
            }

            $diff->orphanedForeignKeys = array_merge($diff->orphanedForeignKeys, $foreignKeysToTable[$tableName]);

            // deleting duplicated foreign keys present on both on the orphanedForeignKey
            // and the removedForeignKeys from changedTables
            foreach ($foreignKeysToTable[$tableName] as $foreignKey) {
                // strtolower the table name to make if compatible with getShortestName
                $localTableName = strtolower($foreignKey->getLocalTableName());
                if (!isset($diff->changedTables[$localTableName])) {
                    continue;
                }

                foreach ($diff->changedTables[$localTableName]->removedForeignKeys as $key => $removedForeignKey) {
                    assert($removedForeignKey instanceof ForeignKeyConstraint);

                    // We check if the key is from the removed table if not we skip.
                    if ($tableName !== strtolower($removedForeignKey->getForeignTableName())) {
                        continue;
                    }

                    unset($diff->changedTables[$localTableName]->removedForeignKeys[$key]);
                }
            }
        }

        foreach ($toSchema->getSequences() as $sequence) {
            $sequenceName = $sequence->getShortestName($toSchema->getName());
            if (!$fromSchema->hasSequence($sequenceName)) {
                if (!$comparator->isAutoIncrementSequenceInSchema($fromSchema, $sequence)) {
                    $diff->newSequences[] = $sequence;
                }
            } else {
                if ($comparator->diffSequence($sequence, $fromSchema->getSequence($sequenceName))) {
                    $diff->changedSequences[] = $toSchema->getSequence($sequenceName);
                }
            }
        }

        foreach ($fromSchema->getSequences() as $sequence) {
            if ($comparator->isAutoIncrementSequenceInSchema($toSchema, $sequence)) {
                continue;
            }

            $sequenceName = $sequence->getShortestName($fromSchema->getName());

            if ($toSchema->hasSequence($sequenceName)) {
                continue;
            }

            $diff->removedSequences[] = $sequence;
        }

        return $diff;
    }

    /**
     * @param Schema   $schema
     * @param Sequence $sequence
     * @todo Remove this method, when 'compareSchemas()' could removed. We needed to borrow
     *       this method along with 'compareSchemas()' through missing late-static binding.
     */
    private function isAutoIncrementSequenceInSchema($schema, $sequence): bool
    {
        foreach ($schema->getTables() as $table) {
            if ($sequence->isAutoIncrementsFor($table)) {
                return true;
            }
        }

        return false;
    }
}
