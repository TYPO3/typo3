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

namespace TYPO3\CMS\Extbase\Event\Persistence;

use TYPO3\CMS\Extbase\Persistence\QueryInterface;

/**
 * Event which is fired before the storage backend is asked for results from a given query.
 */
final class ModifyQueryBeforeFetchingObjectDataEvent
{
    /**
     * @var QueryInterface
     */
    private $query;

    public function __construct(QueryInterface $query)
    {
        $this->query = $query;
    }

    public function getQuery(): QueryInterface
    {
        return $this->query;
    }

    public function setQuery(QueryInterface $query): void
    {
        $this->query = $query;
    }
}
