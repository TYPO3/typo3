<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Core\Database\Schema\Parser\AST\DataType;

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
 * Node representing the VARBINARY SQL column type
 */
class VarBinaryDataType extends AbstractDataType
{
    /**
     * VarBinaryDataType constructor.
     *
     * @param int $length
     */
    public function __construct(int $length)
    {
        $this->length = $length;
    }
}
