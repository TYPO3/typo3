<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Adminpanel\Log;

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

use Doctrine\DBAL\Logging\SQLLogger;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Adminpanel\Utility\MemoryUtility;

/**
 * Doctrine SQL Logger implementation for recording queries for the admin panel
 */
class DoctrineSqlLogger implements SQLLogger, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * Executed SQL queries.
     *
     * @var array
     */
    protected $queries = [];

    /**
     * If Debug Stack is enabled (log queries) or not.
     *
     * @var bool
     */
    protected $enabled = true;

    /**
     * @var float
     */
    protected $start;

    /**
     * @var int
     */
    protected $currentQuery = 0;

    /**
     * {@inheritdoc}
     */
    public function startQuery($sql, array $params = null, array $types = null)
    {
        if ($this->enabled && MemoryUtility::isMemoryConsumptionTooHigh()) {
            $this->enabled = false;
            $this->logger->warning('SQL Logging consumed too much memory, aborted. Not all queries have been recorded.');
        }
        if ($this->enabled) {
            $this->start = microtime(true);
            $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 7);
            // remove this method
            array_shift($backtrace);
            // remove doctrine execute query
            array_shift($backtrace);
            // remove queryBuilder execute
            array_shift($backtrace);
            $this->queries[++$this->currentQuery] = [
                'sql' => $sql,
                'params' => $params,
                'types' => $types,
                'executionMS' => 0,
                'backtrace' => $backtrace
            ];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function stopQuery()
    {
        if ($this->enabled) {
            $this->queries[$this->currentQuery]['executionMS'] = microtime(true) - $this->start;
        }
    }

    /**
     * @return array
     */
    public function getQueries(): array
    {
        return $this->queries;
    }
}
