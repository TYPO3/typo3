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

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Schema\Identifier;
use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaConfig;
use Doctrine\DBAL\Schema\Sequence;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Schema\UniqueConstraint;
use Doctrine\DBAL\Types\ArrayType;
use Doctrine\DBAL\Types\AsciiStringType;
use Doctrine\DBAL\Types\BigIntType;
use Doctrine\DBAL\Types\BlobType;
use Doctrine\DBAL\Types\BooleanType;
use Doctrine\DBAL\Types\DateImmutableType;
use Doctrine\DBAL\Types\DateIntervalType;
use Doctrine\DBAL\Types\DateTimeImmutableType;
use Doctrine\DBAL\Types\DateTimeType;
use Doctrine\DBAL\Types\DateTimeTzImmutableType;
use Doctrine\DBAL\Types\DateTimeTzType;
use Doctrine\DBAL\Types\DateType;
use Doctrine\DBAL\Types\DecimalType;
use Doctrine\DBAL\Types\FloatType;
use Doctrine\DBAL\Types\GuidType;
use Doctrine\DBAL\Types\IntegerType;
use Doctrine\DBAL\Types\JsonType;
use Doctrine\DBAL\Types\ObjectType;
use Doctrine\DBAL\Types\PhpDateTimeMappingType;
use Doctrine\DBAL\Types\PhpIntegerMappingType;
use Doctrine\DBAL\Types\SimpleArrayType;
use Doctrine\DBAL\Types\SmallIntType;
use Doctrine\DBAL\Types\StringType;
use Doctrine\DBAL\Types\TextType;
use Doctrine\DBAL\Types\TimeImmutableType;
use Doctrine\DBAL\Types\TimeType;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\TypeRegistry;
use Doctrine\DBAL\Types\VarDateTimeImmutableType;
use Doctrine\DBAL\Types\VarDateTimeType;
use TYPO3\CMS\Core\Cache\Frontend\PhpFrontend;
use TYPO3\CMS\Core\Database\Schema\Types\DateTimeType as CoreDateTimeType;
use TYPO3\CMS\Core\Database\Schema\Types\DateType as CoreDateType;
use TYPO3\CMS\Core\Database\Schema\Types\EnumType;
use TYPO3\CMS\Core\Database\Schema\Types\SetType;
use TYPO3\CMS\Core\Database\Schema\Types\TimeType as CoreTimeType;

/**
 * This wrapper of SchemaManager contains some internal caches to avoid performance issues for recurring calls to
 * specific schema related information. This should only be used in context where no changes are expected to happen.
 *
 * @internal This class is only for internal core usage and is not part of the public core API.
 */
final class SchemaInformation
{
    private string $connectionIdentifier;

    public function __construct(
        private readonly Connection $connection,
        private readonly PhpFrontend $coreCache
    ) {
        $this->connectionIdentifier = sprintf(
            '%s-%s',
            (string)$connection->getDatabase(),
            // hash connection params, which holds various information like host,
            // port etc. to get a descriptive hash for this connection.
            hash('xxh3', serialize($connection->getParams()))
        );
    }

    /**
     * Similar to doctrine DBAL/AbstractSchemaManager, but with a cache-layer.
     * This is used core internally to auto-add types, for instance in Connection::insert().
     *
     * @return string[]
     */
    public function listTableNames(): array
    {
        $tableNames = [];
        $tables = $this->introspectSchema()->getTables();
        array_walk($tables, static function (Table $table) use (&$tableNames) {
            $tableNames[] = $table->getName();
        });
        return $tableNames;
    }

    /**
     * Similar to doctrine DBAL/AbstractSchemaManager, but with a cache-layer.
     * This is used core internally to auto-add types, for instance in Connection::insert().
     *
     * Creates one cache entry in core cache per configured connection.
     */
    public function introspectSchema(): Schema
    {
        $identifier = $this->connectionIdentifier . '-schema';
        $schema = $this->coreCache->require($identifier);
        if ($schema) {
            return $schema;
        }
        $this->coreCache->set(
            $identifier,
            $this->createSerializedCacheValue($schema, $this->getAllowedClassesForCacheEntryUnserializeCall())
        );
        return $this->coreCache->require($identifier);
    }

    /**
     * Similar to doctrine DBAL/AbstractSchemaManager, but with a cache-layer.
     * This is used core internally to auto-add types, for instance in Connection::insert().
     *
     * Creates one cache entry in core cache per table.
     */
    public function introspectTable(string $tableName): Table
    {
        $identifier = $this->connectionIdentifier . '-table-' . $tableName;
        $table = $this->coreCache->require($identifier);
        if ($table) {
            return $table;
        }
        $table = $this->connection->createSchemaManager()->introspectTable($tableName);
        $this->coreCache->set(
            $identifier,
            $this->createSerializedCacheValue($table, $this->getAllowedClassesForCacheEntryUnserializeCall())
        );
        return $table;
    }

    /**
     * Create a serialized cache value string, which can be used to add it
     * into a code cache. The build value contains a return statement with
     * the "unserialize()" call, covered with allowed classes.
     */
    private function createSerializedCacheValue(object|array $object, array $allowedClasses): string
    {
        array_walk($allowedClasses, static function (&$value) {
            $value = '\'' . addcslashes($value, '\'\\') . '\'';
        });
        return 'return unserialize(\''
            . addcslashes(serialize($object), '\'\\')
            . '\', ['
            . '\'allowed_classes\' => [' . implode(',', $allowedClasses) . ']'
            . ']);';
    }

    /**
     * This method returns the allowed classes for serialized schema information,
     * which is set as 'allowed_classes' for the deserialization. Added to avoid
     * security issues.
     *
     * @return string[]
     */
    private function getAllowedClassesForCacheEntryUnserializeCall(): array
    {
        // @todo: An event may be needed here: If an extension registers custom types - which
        //        is currently unlikely - it needs to add it's class here.
        //        Alternative: Loop over TypeRegistry registered types and auto-allow the implementing classes
        //        to avoid a rather hidden cross-dependency event - other things like Table can't be overridden
        //        anyways, so only custom types *may* be relevant.
        return [
            // doctrine/dbal classes and types
            Column::class,
            ForeignKeyConstraint::class,
            Identifier::class,
            Index::class,
            SchemaConfig::class,
            Schema::class,
            Sequence::class,
            Table::class,
            UniqueConstraint::class,
            ArrayType::class,
            AsciiStringType::class,
            BigIntType::class,
            BlobType::class,
            BooleanType::class,
            DateImmutableType::class,
            DateIntervalType::class,
            DateTimeImmutableType::class,
            DateTimeType::class,
            DateTimeTzImmutableType::class,
            DateTimeTzType::class,
            DateType::class,
            DecimalType::class,
            FloatType::class,
            GuidType::class,
            IntegerType::class,
            JsonType::class,
            ObjectType::class,
            PhpDateTimeMappingType::class,
            PhpIntegerMappingType::class,
            SimpleArrayType::class,
            SmallIntType::class,
            StringType::class,
            TextType::class,
            TimeImmutableType::class,
            TimeType::class,
            Type::class,
            TypeRegistry::class,
            VarDateTimeImmutableType::class,
            VarDateTimeType::class,
            // core classes and types
            CoreDateTimeType::class,
            CoreDateType::class,
            CoreTimeType::class,
            SetType::class,
            EnumType::class,
        ];
    }
}
