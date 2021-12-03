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

namespace TYPO3\CMS\Core\Database\Schema\Parser;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Schema\Parser\AST\CreateColumnDefinitionItem;
use TYPO3\CMS\Core\Database\Schema\Parser\AST\CreateForeignKeyDefinitionItem;
use TYPO3\CMS\Core\Database\Schema\Parser\AST\CreateIndexDefinitionItem;
use TYPO3\CMS\Core\Database\Schema\Parser\AST\CreateTableStatement;
use TYPO3\CMS\Core\Database\Schema\Parser\AST\DataType\AbstractDataType;
use TYPO3\CMS\Core\Database\Schema\Parser\AST\DataType\BigIntDataType;
use TYPO3\CMS\Core\Database\Schema\Parser\AST\DataType\BinaryDataType;
use TYPO3\CMS\Core\Database\Schema\Parser\AST\DataType\BlobDataType;
use TYPO3\CMS\Core\Database\Schema\Parser\AST\DataType\CharDataType;
use TYPO3\CMS\Core\Database\Schema\Parser\AST\DataType\DateDataType;
use TYPO3\CMS\Core\Database\Schema\Parser\AST\DataType\DateTimeDataType;
use TYPO3\CMS\Core\Database\Schema\Parser\AST\DataType\DecimalDataType;
use TYPO3\CMS\Core\Database\Schema\Parser\AST\DataType\DoubleDataType;
use TYPO3\CMS\Core\Database\Schema\Parser\AST\DataType\EnumDataType;
use TYPO3\CMS\Core\Database\Schema\Parser\AST\DataType\FloatDataType;
use TYPO3\CMS\Core\Database\Schema\Parser\AST\DataType\IntegerDataType;
use TYPO3\CMS\Core\Database\Schema\Parser\AST\DataType\JsonDataType;
use TYPO3\CMS\Core\Database\Schema\Parser\AST\DataType\LongBlobDataType;
use TYPO3\CMS\Core\Database\Schema\Parser\AST\DataType\LongTextDataType;
use TYPO3\CMS\Core\Database\Schema\Parser\AST\DataType\MediumBlobDataType;
use TYPO3\CMS\Core\Database\Schema\Parser\AST\DataType\MediumIntDataType;
use TYPO3\CMS\Core\Database\Schema\Parser\AST\DataType\MediumTextDataType;
use TYPO3\CMS\Core\Database\Schema\Parser\AST\DataType\NumericDataType;
use TYPO3\CMS\Core\Database\Schema\Parser\AST\DataType\RealDataType;
use TYPO3\CMS\Core\Database\Schema\Parser\AST\DataType\SetDataType;
use TYPO3\CMS\Core\Database\Schema\Parser\AST\DataType\SmallIntDataType;
use TYPO3\CMS\Core\Database\Schema\Parser\AST\DataType\TextDataType;
use TYPO3\CMS\Core\Database\Schema\Parser\AST\DataType\TimeDataType;
use TYPO3\CMS\Core\Database\Schema\Parser\AST\DataType\TimestampDataType;
use TYPO3\CMS\Core\Database\Schema\Parser\AST\DataType\TinyBlobDataType;
use TYPO3\CMS\Core\Database\Schema\Parser\AST\DataType\TinyIntDataType;
use TYPO3\CMS\Core\Database\Schema\Parser\AST\DataType\TinyTextDataType;
use TYPO3\CMS\Core\Database\Schema\Parser\AST\DataType\VarBinaryDataType;
use TYPO3\CMS\Core\Database\Schema\Parser\AST\DataType\VarCharDataType;
use TYPO3\CMS\Core\Database\Schema\Parser\AST\DataType\YearDataType;
use TYPO3\CMS\Core\Database\Schema\Parser\AST\IndexColumnName;
use TYPO3\CMS\Core\Database\Schema\Parser\AST\ReferenceDefinition;
use TYPO3\CMS\Core\Database\Schema\Types\EnumType;
use TYPO3\CMS\Core\Database\Schema\Types\SetType;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Converts a CreateTableStatement syntax node into a Doctrine Table
 * object that represents the table defined in the original SQL statement.
 */
class TableBuilder
{
    /**
     * @var Table
     */
    protected $table;

    /**
     * @var AbstractPlatform
     */
    protected $platform;

    /**
     * TableBuilder constructor.
     *
     * @param \Doctrine\DBAL\Platforms\AbstractPlatform $platform
     * @throws \InvalidArgumentException
     * @throws \Doctrine\DBAL\Exception
     */
    public function __construct(AbstractPlatform $platform = null)
    {
        // Register custom data types as no connection might have
        // been established yet so the types would not be available
        // when building tables/columns.
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);

        foreach ($connectionPool->getCustomDoctrineTypes() as $type => $className) {
            if (!Type::hasType($type)) {
                Type::addType($type, $className);
            }
        }
        $this->platform = $platform ?: GeneralUtility::makeInstance(MySqlPlatform::class);
    }

    /**
     * Create a Doctrine Table object based on the parsed MySQL SQL command.
     *
     * @param \TYPO3\CMS\Core\Database\Schema\Parser\AST\CreateTableStatement $tableStatement
     * @return \Doctrine\DBAL\Schema\Table
     * @throws \Doctrine\DBAL\Schema\SchemaException
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     */
    public function create(CreateTableStatement $tableStatement): Table
    {
        $this->table = GeneralUtility::makeInstance(
            Table::class,
            $tableStatement->tableName->getQuotedName(),
            [],
            [],
            [],
            0,
            $this->buildTableOptions($tableStatement->tableOptions)
        );

        foreach ($tableStatement->createDefinition->items as $item) {
            switch (get_class($item)) {
                case CreateColumnDefinitionItem::class:
                    $this->addColumn($item);
                    break;
                case CreateIndexDefinitionItem::class:
                    $this->addIndex($item);
                    break;
                case CreateForeignKeyDefinitionItem::class:
                    $this->addForeignKey($item);
                    break;
                default:
                    throw new \RuntimeException(
                        'Unknown item definition of type "' . get_class($item) . '" encountered.',
                        1472044085
                    );
            }
        }

        return $this->table;
    }

    /**
     * @param \TYPO3\CMS\Core\Database\Schema\Parser\AST\CreateColumnDefinitionItem $item
     * @return \Doctrine\DBAL\Schema\Column
     * @throws \Doctrine\DBAL\Schema\SchemaException
     * @throws \RuntimeException
     */
    protected function addColumn(CreateColumnDefinitionItem $item): Column
    {
        $column = $this->table->addColumn(
            $item->columnName->getQuotedName(),
            $this->getDoctrineColumnTypeName($item->dataType)
        );

        $column->setNotnull(!$item->allowNull);
        $column->setAutoincrement((bool)$item->autoIncrement);
        $column->setComment($item->comment);

        // Set default value (unless it's an auto increment column)
        if ($item->hasDefaultValue && !$column->getAutoincrement()) {
            $column->setDefault($item->defaultValue);
        }

        if ($item->dataType->getLength()) {
            $column->setLength($item->dataType->getLength());
        }

        if ($item->dataType->getPrecision() >= 0) {
            $column->setPrecision($item->dataType->getPrecision());
        }

        if ($item->dataType->getScale() >= 0) {
            $column->setScale($item->dataType->getScale());
        }

        if ($item->dataType->isUnsigned()) {
            $column->setUnsigned(true);
        }

        // Select CHAR/VARCHAR or BINARY/VARBINARY
        if ($item->dataType->isFixed()) {
            $column->setFixed(true);
        }

        if ($item->dataType instanceof EnumDataType
            || $item->dataType instanceof SetDataType
        ) {
            $column->setPlatformOption('unquotedValues', $item->dataType->getValues());
        }

        if ($item->index) {
            $this->table->addIndex([$item->columnName->getQuotedName()]);
        }

        if ($item->unique) {
            $this->table->addUniqueIndex([$item->columnName->getQuotedName()]);
        }

        if ($item->primary) {
            $this->table->setPrimaryKey([$item->columnName->getQuotedName()]);
        }

        if ($item->reference !== null) {
            $this->addForeignKeyConstraint(
                [$item->columnName->getQuotedName()],
                $item->reference
            );
        }

        return $column;
    }

    /**
     * @param \TYPO3\CMS\Core\Database\Schema\Parser\AST\CreateIndexDefinitionItem $item
     * @return \Doctrine\DBAL\Schema\Index
     * @throws \Doctrine\DBAL\Schema\SchemaException
     * @throws \InvalidArgumentException
     */
    protected function addIndex(CreateIndexDefinitionItem $item): Index
    {
        $indexName = $item->indexName->getQuotedName();

        $columnNames = array_map(
            static function (IndexColumnName $columnName) {
                if ($columnName->length) {
                    return $columnName->columnName->getQuotedName() . '(' . $columnName->length . ')';
                }
                return $columnName->columnName->getQuotedName();
            },
            $item->columnNames
        );

        if ($item->isPrimary) {
            $this->table->setPrimaryKey($columnNames);
            $index = $this->table->getPrimaryKey();
        } else {
            $index = GeneralUtility::makeInstance(
                Index::class,
                $indexName,
                $columnNames,
                $item->isUnique,
                $item->isPrimary
            );

            if ($item->isFulltext) {
                $index->addFlag('fulltext');
            } elseif ($item->isSpatial) {
                $index->addFlag('spatial');
            }

            $this->table = new Table(
                $this->table->getQuotedName($this->platform),
                $this->table->getColumns(),
                array_merge($this->table->getIndexes(), [strtolower($indexName) => $index]),
                $this->table->getForeignKeys(),
                0,
                $this->table->getOptions()
            );
        }

        return $index;
    }

    /**
     * Prepare an explicit foreign key definition item to be added to the table being built.
     *
     * @param \TYPO3\CMS\Core\Database\Schema\Parser\AST\CreateForeignKeyDefinitionItem $item
     */
    protected function addForeignKey(CreateForeignKeyDefinitionItem $item)
    {
        $indexName = $item->indexName->getQuotedName() ?: null;
        $localColumnNames = array_map(
            static function (IndexColumnName $columnName) {
                return $columnName->columnName->getQuotedName();
            },
            $item->columnNames
        );
        $this->addForeignKeyConstraint($localColumnNames, $item->reference, $indexName);
    }

    /**
     * Add a foreign key constraint to the table being built.
     *
     * @param string[] $localColumnNames
     * @param \TYPO3\CMS\Core\Database\Schema\Parser\AST\ReferenceDefinition $referenceDefinition
     * @param string $indexName
     */
    protected function addForeignKeyConstraint(
        array $localColumnNames,
        ReferenceDefinition $referenceDefinition,
        string $indexName = null
    ) {
        $foreignTableName = $referenceDefinition->tableName->getQuotedName();
        $foreignColumnNames = array_map(
            static function (IndexColumnName $columnName) {
                return $columnName->columnName->getQuotedName();
            },
            $referenceDefinition->columnNames
        );

        $options = [
            'onDelete' => $referenceDefinition->onDelete,
            'onUpdate' => $referenceDefinition->onUpdate,
        ];

        $this->table->addForeignKeyConstraint(
            $foreignTableName,
            $localColumnNames,
            $foreignColumnNames,
            $options,
            $indexName
        );
    }

    /**
     * @param \TYPO3\CMS\Core\Database\Schema\Parser\AST\DataType\AbstractDataType $dataType
     * @return string
     * @throws \RuntimeException
     */
    protected function getDoctrineColumnTypeName(AbstractDataType $dataType): string
    {
        switch (get_class($dataType)) {
            case TinyIntDataType::class:
                // TINYINT is MySQL specific and mapped to a standard SMALLINT
            case SmallIntDataType::class:
                $doctrineType = Types::SMALLINT;
                break;
            case MediumIntDataType::class:
                // MEDIUMINT is MySQL specific and mapped to a standard INT
            case IntegerDataType::class:
                $doctrineType = Types::INTEGER;
                break;
            case BigIntDataType::class:
                $doctrineType = Types::BIGINT;
                break;
            case BinaryDataType::class:
            case VarBinaryDataType::class:
                // CHAR/VARCHAR is determined by "fixed" column property
                $doctrineType = Types::BINARY;
                break;
            case TinyBlobDataType::class:
            case MediumBlobDataType::class:
            case BlobDataType::class:
            case LongBlobDataType::class:
                // Actual field type is determined by field length
                $doctrineType = Types::BLOB;
                break;
            case DateDataType::class:
                $doctrineType = Types::DATE_MUTABLE;
                break;
            case TimestampDataType::class:
            case DateTimeDataType::class:
                // TIMESTAMP or DATETIME are determined by "version" column property
                $doctrineType = Types::DATETIME_MUTABLE;
                break;
            case NumericDataType::class:
            case DecimalDataType::class:
                $doctrineType = Types::DECIMAL;
                break;
            case RealDataType::class:
            case FloatDataType::class:
            case DoubleDataType::class:
                $doctrineType = Types::FLOAT;
                break;
            case TimeDataType::class:
                $doctrineType = Types::TIME_MUTABLE;
                break;
            case TinyTextDataType::class:
            case MediumTextDataType::class:
            case TextDataType::class:
            case LongTextDataType::class:
                $doctrineType = Types::TEXT;
                break;
            case CharDataType::class:
            case VarCharDataType::class:
                $doctrineType = Types::STRING;
                break;
            case EnumDataType::class:
                $doctrineType = EnumType::TYPE;
                break;
            case SetDataType::class:
                $doctrineType = SetType::TYPE;
                break;
            case JsonDataType::class:
                // JSON is not supported in Doctrine 2.5, mapping to the more generic TEXT type
                $doctrineType = Types::TEXT;
                break;
            case YearDataType::class:
                // The YEAR data type is MySQL specific and offers little to no benefit.
                // The two-digit year logic implemented in this data type (1-69 mapped to
                // 2001-2069, 70-99 mapped to 1970-1999) can be easily implemented in the
                // application and for all other accounts it's an integer with a valid
                // range of 1901 to 2155.
                // Using a SMALLINT covers the value range and ensures database compatibility.
                $doctrineType = Types::SMALLINT;
                break;
            default:
                throw new \RuntimeException(
                    'Unsupported data type: ' . get_class($dataType) . '!',
                    1472046376
                );
        }

        return $doctrineType;
    }

    /**
     * Build the table specific options as far as they are supported by Doctrine.
     *
     * @param array $tableOptions
     * @return array
     */
    protected function buildTableOptions(array $tableOptions): array
    {
        $options = [];

        if (!empty($tableOptions['engine'])) {
            $options['engine'] = (string)$tableOptions['engine'];
        }
        if (!empty($tableOptions['character_set'])) {
            $options['charset'] = (string)$tableOptions['character_set'];
        }
        if (!empty($tableOptions['collation'])) {
            $options['collate'] = (string)$tableOptions['collation'];
        }
        if (!empty($tableOptions['auto_increment'])) {
            $options['auto_increment'] = (string)$tableOptions['auto_increment'];
        }
        if (!empty($tableOptions['comment'])) {
            $options['comment'] = (string)$tableOptions['comment'];
        }
        if (!empty($tableOptions['row_format'])) {
            $options['row_format'] = (string)$tableOptions['row_format'];
        }

        return $options;
    }
}
