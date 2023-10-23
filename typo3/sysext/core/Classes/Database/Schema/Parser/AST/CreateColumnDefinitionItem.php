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

namespace TYPO3\CMS\Core\Database\Schema\Parser\AST;

use TYPO3\CMS\Core\Database\Schema\Parser\AST\DataType\AbstractDataType;

/**
 * Syntax tree node for column definitions within "create table" statements.
 * Holds basic attributes common to all types of columns.
 *
 * @internal
 */
final class CreateColumnDefinitionItem extends AbstractCreateDefinitionItem
{
    public bool $allowNull = true;
    // Has explicit default value?
    public bool $hasDefaultValue = false;
    // The explicit default value
    public mixed $defaultValue = null;
    public bool $autoIncrement = false;
    // Create non-unique index for column?
    public bool $index = false;
    // Create unique constraint for column?
    public bool $unique = false;
    // Use column as primary key for table?
    public bool $primary = false;
    public ?string $comment = null;
    // Column format: "dynamic" or "fixed"
    public ?string $columnFormat = null;
    // The storage type for the column (ignored unless MySQL Cluster with NDB Engine)
    public ?string $storage = null;
    public ?ReferenceDefinition $reference = null;

    public function __construct(
        public readonly Identifier $columnName,
        public readonly AbstractDataType $dataType,
    ) {}
}
