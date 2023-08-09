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
 * Root node for a CREATE TABLE statement in the syntax tree.
 *
 * @internal
 */
final class CreateTableStatement extends AbstractCreateStatement
{
    public Identifier $tableName;
    public bool $isTemporary = false;
    public array $tableOptions = [];

    public function __construct(
        CreateTableClause $createTableClause,
        public readonly CreateDefinition $createDefinition
    ) {
        $this->tableName = $createTableClause->tableName;
        $this->isTemporary = $createTableClause->isTemporary;
    }
}
