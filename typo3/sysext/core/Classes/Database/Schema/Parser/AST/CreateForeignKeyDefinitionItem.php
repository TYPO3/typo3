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
 * Syntax node to structure a foreign key definition.
 */
class CreateForeignKeyDefinitionItem extends AbstractCreateDefinitionItem
{
    /**
     * @var Identifier
     */
    public $indexName;

    /**
     * The index name
     *
     * @var string
     */
    public $name = '';

    /**
     * @var IndexColumnName[]
     */
    public $columnNames = [];

    /**
     * Reference definition
     *
     * @var ReferenceDefinition
     */
    public $reference;

    /**
     * CreateForeignKeyDefinitionItem constructor.
     *
     * @param \TYPO3\CMS\Core\Database\Schema\Parser\AST\Identifier $indexName
     * @param array $columnNames
     * @param \TYPO3\CMS\Core\Database\Schema\Parser\AST\ReferenceDefinition $reference
     */
    public function __construct(Identifier $indexName, array $columnNames, ReferenceDefinition $reference)
    {
        $this->indexName = $indexName;
        $this->columnNames = $columnNames;
        $this->reference = $reference;
    }
}
