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
 * Node representing the JSON SQL column type
 */
class JsonDataType extends AbstractDataType
{
    /**
     * JsonDataType constructor.
     */
    public function __construct()
    {
        // JSON is not yet supported by Doctrine 2.5 and will be remapped
        // to a TEXT type. Setting the length here will ensure a LONGTEXT
        // column type is selected.
        $this->length = 2147483647;
    }
}
