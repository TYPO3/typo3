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

namespace TYPO3\CMS\Core\Database\Schema\Information;

use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Types\Exception\TypesException;
use Doctrine\DBAL\Types\Type;
use TYPO3\CMS\Core\Database\Schema\SchemaInformation;

/**
 * Provides subset of column schema information compared to {@see Column} and intended to be cacheable.
 *
 * @internal This class is only for internal core usage and is not part of the public core API.
 */
final readonly class ColumnInfo
{
    /**
     * @param string[] $values
     */
    public function __construct(
        public string $name,
        public string $typeName,
        public mixed $default,
        public bool $notNull,
        public ?int $length,
        public ?int $precision,
        public int $scale,
        public bool $fixed,
        public bool $unsigned,
        public bool $autoincrement,
        public array $values,
    ) {}

    /**
     * @throws TypesException
     */
    public function getType(): Type
    {
        return Type::getType($this->typeName);
    }

    /**
     * Used in {@see SchemaInformation::buildTableInformation()} to transform doctrine Columns to ColumnInfo.
     */
    public static function convertFromDoctrineColumn(Column $column): self
    {
        // `Column->getType()` is not passed here by intention to mitigate cache issues getting information from
        // persisted cache due to `sbl_object_id()` usage in the Doctrine DBAL TypesRegistry not matching the
        // type later on. Skipping it here and not having it as class property is part of the mitigation strategy
        // and resolves the cache issues with `Column` directly.
        return new self(
            name: $column->getName(),
            typeName: Type::lookupName($column->getType()),
            default: $column->getDefault(),
            notNull: $column->getNotnull(),
            length: $column->getLength(),
            precision: $column->getPrecision(),
            scale: $column->getScale(),
            fixed: $column->getFixed(),
            unsigned: $column->getUnsigned(),
            autoincrement: $column->getAutoincrement(),
            values: $column->getValues(),
        );
    }
}
