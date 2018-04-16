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
 * Syntax node to represent a column within an index, which can in MySQL
 * context consist of the actual column name, length information for a partial
 * index and a direction which influences default sorting and access patterns.
 */
class IndexColumnName
{
    /**
     * @var \TYPO3\CMS\Core\Database\Schema\Parser\AST\Identifier
     */
    public $columnName;

    /**
     * @var int
     */
    public $length;

    /**
     * @var string
     */
    public $direction;

    /**
     * IndexColumnName constructor.
     *
     * @param \TYPO3\CMS\Core\Database\Schema\Parser\AST\Identifier $columnName
     * @param int $length
     * @param string $direction
     */
    public function __construct(Identifier $columnName, int $length, string $direction = null)
    {
        $this->columnName = $columnName;
        $this->length = $length;
        $this->direction = $direction;
    }
}
