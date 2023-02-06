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

use Doctrine\DBAL\Driver\Result as ResultInterface;
use Doctrine\DBAL\Driver\Statement;
use Doctrine\DBAL\Driver\Statement as StatementInterface;
use Doctrine\DBAL\ParameterType;

/**
 * Part of the Doctrine SQL Logging Driver Adapter
 *
 * @internal
 */
final class LoggingStatement implements Statement
{
    private array $params = [];
    private array $types = [];

    public function __construct(
        private StatementInterface $wrappedStatement,
        private DoctrineSqlLogger $logger,
        private string $sql
    ) {}

    public function bindValue(int|string $param, mixed $value, ParameterType $type = ParameterType::STRING): void
    {
        $this->params[$param] = $value;
        $this->types[$param]  = $type;

        $this->wrappedStatement->bindValue($param, $value, $type);
    }

    public function execute(): ResultInterface
    {
        $this->logger->startQuery($this->sql, $this->params, $this->types);
        $result = $this->wrappedStatement->execute();
        $this->logger->stopQuery();

        return $result;
    }
}
