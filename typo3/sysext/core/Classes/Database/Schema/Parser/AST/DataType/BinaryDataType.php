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

namespace TYPO3\CMS\Core\Database\Schema\Parser\AST\DataType;

/**
 * Node representing the BINARY SQL column type
 *
 * @internal
 */
final class BinaryDataType extends AbstractDataType
{
    public function __construct(int $length)
    {
        /**
         * BINARY is "fixed type". Setting it here instructs Doctrine DBAL to use this type instead of
         * the "variable type" when being transformed within the {@see TableBuilder::addColumn()} method.
         */
        $this->fixed = true;
        $this->length = $length;
    }
}
