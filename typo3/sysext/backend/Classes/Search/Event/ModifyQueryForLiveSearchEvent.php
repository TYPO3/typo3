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

namespace TYPO3\CMS\Backend\Search\Event;

use TYPO3\CMS\Core\Database\Query\QueryBuilder;

/**
 * PSR-14 event to modify the query builder instance for the live search
 */
final class ModifyQueryForLiveSearchEvent
{
    public function __construct(private readonly QueryBuilder $queryBuilder, private readonly string $table)
    {
    }

    public function getQueryBuilder(): QueryBuilder
    {
        return $this->queryBuilder;
    }

    public function getTableName(): string
    {
        return $this->table;
    }
}
