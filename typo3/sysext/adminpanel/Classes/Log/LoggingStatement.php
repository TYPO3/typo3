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

use Doctrine\DBAL\Driver\Middleware\AbstractStatementMiddleware;
use Doctrine\DBAL\Driver\Result as ResultInterface;
use Doctrine\DBAL\Driver\Statement as StatementInterface;
use Doctrine\DBAL\ParameterType;

/**
 * Part of the Doctrine SQL Logging Driver Adapter
 *
 * @internal
 */
final class LoggingStatement extends AbstractStatementMiddleware
{
    private DoctrineSqlLogger $logger;
    private string $sql;
    private array $params = [];
    private array $types = [];

    public function __construct(StatementInterface $statement, DoctrineSqlLogger $logger, string $sql)
    {
        parent::__construct($statement);

        $this->logger = $logger;
        $this->sql    = $sql;
    }

    public function bindValue($param, $value, $type = ParameterType::STRING)
    {
        $this->params[$param] = $value;
        $this->types[$param]  = $type;

        return parent::bindValue($param, $value, $type);
    }

    public function execute($params = null): ResultInterface
    {
        $this->logger->startQuery($this->sql, $params ?? $this->params, $this->types);
        $result = parent::execute($params);
        $this->logger->stopQuery();

        return $result;
    }
}
