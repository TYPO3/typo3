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
 * Event which is fired after the storage backend has pulled results from a given query.
 */
final class ModifyResultAfterFetchingObjectDataEvent
{
    /**
     * @var QueryInterface
     */
    private $query;

    /**
     * @var array
     */
    private $result;

    public function __construct(QueryInterface $query, array $result)
    {
        $this->query = $query;
        $this->result = $result;
    }

    public function getQuery(): QueryInterface
    {
        return $this->query;
    }

    public function getResult(): array
    {
        return $this->result;
    }

    public function setResult(array $result): void
    {
        $this->result = $result;
    }
}
