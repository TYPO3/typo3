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

namespace TYPO3\CMS\IndexedSearch\Utility;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Enumeration object for LikeWildcard
 * @internal
 */
enum LikeWildcard: int
{
    /** Do not use any wildcard */
    case NONE = 0;

    /** Use wildcard on left side */
    case LEFT = 1;

    /** Use wildcard on right side */
    case RIGHT = 2;

    /** Use wildcard on both sides */
    case BOTH = 3;

    /**
     * Returns a LIKE clause for sql queries.
     *
     * @param string $tableName The name of the table to query.
     * @param string $fieldName The name of the field to query with LIKE.
     * @param string $likeValue The value for the LIKE clause operation.
     * @return string
     */
    public function getLikeQueryPart(string $tableName, string $fieldName, string $likeValue): string
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable($tableName);

        $string = ($this->value & self::LEFT->value ? '%' : '')
            . $queryBuilder->escapeLikeWildcards($likeValue)
            . ($this->value & self::RIGHT->value ? '%' : '');

        return $queryBuilder->expr()->like($fieldName, $queryBuilder->quote($string));
    }
}
