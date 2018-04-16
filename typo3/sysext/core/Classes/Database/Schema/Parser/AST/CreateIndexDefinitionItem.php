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
 * Syntax node to structure an index definition.
 */
class CreateIndexDefinitionItem extends AbstractCreateDefinitionItem
{
    /**
     * @var
     */
    public $indexName = '';

    /**
     * Create the primary key
     *
     * @var bool
     */
    public $isPrimary = false;

    /**
     * Create a unique index
     *
     * @var bool
     */
    public $isUnique = false;

    /**
     * Create a fulltext index
     *
     * @var bool
     */
    public $isFulltext = false;

    /**
     * Create a spatial (geo) index
     *
     * @var bool
     */
    public $isSpatial = false;

    /**
     * Use a special index type (MySQL: BTREE | HASH)
     *
     * @var string
     */
    public $indexType = '';

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
     * Index options KEY_BLOCK_SIZE, USING, WITH PARSER or COMMENT
     *
     * @var array
     */
    public $options = [];

    /**
     * CreateIndexDefinitionItem constructor.
     *
     * @param Identifier $indexName
     * @param bool $isPrimary
     * @param bool $isUnique
     * @param bool $isSpatial
     * @param bool $isFulltext
     */
    public function __construct(
        Identifier $indexName = null,
        bool $isPrimary = false,
        bool $isUnique = false,
        bool $isSpatial = false,
        bool $isFulltext = false
    ) {
        $this->indexName = $indexName;
        $this->isPrimary = $isPrimary;
        $this->isUnique = $isUnique;
        $this->isSpatial = $isSpatial;
        $this->isFulltext = $isFulltext;
    }
}
