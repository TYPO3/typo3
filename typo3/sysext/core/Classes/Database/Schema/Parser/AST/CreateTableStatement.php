<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Core\Database\Schema\Parser\AST;

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

/**
 * Root node for a CREATE TABLE statement in the syntax tree.
 */
class CreateTableStatement extends AbstractCreateStatement
{
    /**
     * @var \TYPO3\CMS\Core\Database\Schema\Parser\AST\Identifier
     */
    public $tableName;

    /**
     * @var bool
     */
    public $isTemporary = false;

    /**
     * @var \TYPO3\CMS\Core\Database\Schema\Parser\AST\CreateDefinition
     */
    public $createDefinition;

    /**
     * @var array
     */
    public $tableOptions = [];

    /**
     * CreateTableStatement constructor.
     *
     * @param \TYPO3\CMS\Core\Database\Schema\Parser\AST\CreateTableClause $createTableClause
     * @param \TYPO3\CMS\Core\Database\Schema\Parser\AST\CreateDefinition $createDefinition
     */
    public function __construct(CreateTableClause $createTableClause, CreateDefinition $createDefinition)
    {
        $this->tableName = $createTableClause->tableName;
        $this->isTemporary = $createTableClause->isTemporary;
        $this->createDefinition = $createDefinition;
    }
}
