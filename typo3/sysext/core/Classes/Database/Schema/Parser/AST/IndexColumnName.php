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
 * Syntax node to represent a column within an index, which can in MySQL
 * context consist of the actual column name, length information for a partial
 * index and a direction which influences default sorting and access patterns.
 *
 * @internal
 */
final class IndexColumnName
{
    public function __construct(
        public readonly Identifier $columnName,
        public readonly int $length,
        public readonly ?string $direction = null
    ) {}
}
