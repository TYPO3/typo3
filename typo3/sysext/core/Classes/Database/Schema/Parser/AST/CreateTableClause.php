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
 * Syntax node to represent the initial CREATE TABLE statement in the
 * syntax tree. Represents everything up to the start of the definition
 * of fields/indexes/foreign keys.
 *
 * @internal
 */
final class CreateTableClause
{
    public function __construct(
        public readonly Identifier $tableName,
        public bool $isTemporary = false
    ) {}
}
