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

/**
 * Syntax node to represent the REFERENCES part of a foreign key
 * definition, encapsulating ON UPDATE/ON DELETE actions as well
 * as the foreign table name and columns.
 *
 * @internal
 */
final class ReferenceDefinition
{
    // Match type if given: FULL, PARTIAL or SIMPLE
    public ?string $match = null;
    // Reference option if given: RESTRICT | CASCADE | SET NULL | NO ACTION
    public ?string $onDelete = null;
    // Reference option if given: RESTRICT | CASCADE | SET NULL | NO ACTION
    public ?string $onUpdate = null;

    /**
     * @param IndexColumnName[] $columnNames
     */
    public function __construct(
        public readonly Identifier $tableName,
        public readonly array $columnNames,
    ) {}
}
