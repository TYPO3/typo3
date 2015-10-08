<?php
namespace TYPO3\CMS\IndexedSearch\Utility;

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
 * Enumeration object for LikeWildcard
 */
class LikeWildcard extends \TYPO3\CMS\Core\Type\Enumeration
{
    const __default = self::BOTH;

    /** @var int Do not use any wildcard */
    const NONE = 0;

    /** @var int Use wildcard on left side */
    const LEFT = 1;

    /** @var int Use wildcard on right side */
    const RIGHT = 2;

    /** @var int Use wildcard on both sides */
    const BOTH = 3;

    /**
     * Returns a LIKE clause for sql queries.
     *
     * @param string $tableName The name of the table to query.
     * @param string $fieldName The name of the field to query with LIKE.
     * @param string $likeValue The value for the LIKE clause operation.
     * @return string
     * @throws \TYPO3\CMS\Core\Type\Exception\InvalidEnumerationValueException
     */
    public function getLikeQueryPart($tableName, $fieldName, $likeValue)
    {
        $databaseConnection = $GLOBALS['TYPO3_DB'];

        $likeValue = $databaseConnection->quoteStr(
            $databaseConnection->escapeStrForLike($likeValue, $tableName),
            $tableName
        );

        return $fieldName . ' LIKE \''
            . ($this->value & self::LEFT ? '%' : '')
            . $likeValue
            . ($this->value & self::RIGHT ? '%' : '')
            . '\'';
    }
}
