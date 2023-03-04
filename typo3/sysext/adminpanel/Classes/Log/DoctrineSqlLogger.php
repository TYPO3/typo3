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

namespace TYPO3\CMS\Adminpanel\Log;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Adminpanel\Utility\MemoryUtility;

/**
 * Doctrine SQL Logger implementation for recording queries for the admin panel
 *
 * @internal
 */
class DoctrineSqlLogger implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /** Executed SQL queries. */
    protected array $queries = [];
    /** If Debug Stack is enabled (log queries) or not. */
    protected bool $enabled = false;
    protected float $start;
    protected int $currentQuery = 0;

    public function enable(): void
    {
        $this->enabled = true;
    }

    public function startQuery($sql, array $params = null, array $types = null): void
    {
        if ($this->enabled && MemoryUtility::isMemoryConsumptionTooHigh()) {
            $this->enabled = false;
            $this->logger->warning('SQL Logging consumed too much memory, aborted. Not all queries have been recorded.');
        }
        if ($this->enabled) {
            $visibleBacktraceLength = 4;
            $removeFromBacktraceLength = 4;

            $this->start = microtime(true);
            $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, $visibleBacktraceLength + $removeFromBacktraceLength);
            // remove internal doctrine and logging methods from the visible backtrace
            array_splice($backtrace, 0, $removeFromBacktraceLength);

            $this->queries[++$this->currentQuery] = [
                'sql' => $sql,
                'params' => $params ?? [],
                'types' => $types ?? [],
                'executionMS' => 0,
                'backtrace' => $backtrace,
            ];
        }
    }

    public function stopQuery(): void
    {
        if ($this->enabled) {
            $this->queries[$this->currentQuery]['executionMS'] = microtime(true) - $this->start;
        }
    }

    public function getQueries(): array
    {
        return $this->queries;
    }
}
