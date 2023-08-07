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

namespace TYPO3\CMS\Core\Database\Driver;

use Doctrine\DBAL\Driver\Connection as ConnectionInterface;
use Doctrine\DBAL\Driver\PDO\Connection as DoctrineDbalPDOConnection;
use Doctrine\DBAL\Driver\PDO\Exception;
use Doctrine\DBAL\Driver\Result as ResultInterface;
use Doctrine\DBAL\Driver\ServerInfoAwareConnection;
use Doctrine\DBAL\Driver\Statement as StatementInterface;
use Doctrine\DBAL\ParameterType;

/**
 * This is a full "clone" of the class of package doctrine/dbal. Scope is to use instanatiate TYPO3's DriverResult
 * and DriverStatement objects instead of Doctrine's native implementation.
 *
 * @internal this implementation is not part of TYPO3's Public API.
 */
class DriverConnection implements ConnectionInterface, ServerInfoAwareConnection
{
    protected DoctrineDbalPDOConnection $doctrineDbalPDOConnection;
    protected \PDO $connection;

    public function __construct(\PDO $connection)
    {
        $this->connection = $connection;
        $this->doctrineDbalPDOConnection = new DoctrineDbalPDOConnection($connection);
    }

    public function exec(string $sql): int
    {
        return $this->doctrineDbalPDOConnection->exec($sql);
    }

    public function getServerVersion()
    {
        return $this->doctrineDbalPDOConnection->getServerVersion();
    }

    public function prepare(string $sql): StatementInterface
    {
        try {
            $stmt = $this->connection->prepare($sql);
            assert($stmt instanceof \PDOStatement);

            // use TYPO3's Statement object in favor of Doctrine's Statement wrapper
            return new DriverStatement($stmt);
        } catch (\PDOException $exception) {
            throw Exception::new($exception);
        }
    }

    public function query(string $sql): ResultInterface
    {
        try {
            $stmt = $this->connection->query($sql);
            assert($stmt instanceof \PDOStatement);

            // use TYPO3's Result object in favor of Doctrine's Result wrapper
            return new DriverResult($stmt);
        } catch (\PDOException $exception) {
            throw Exception::new($exception);
        }
    }

    public function quote($value, $type = ParameterType::STRING)
    {
        return $this->doctrineDbalPDOConnection->quote($value, $type);
    }

    public function lastInsertId($name = null)
    {
        return $this->doctrineDbalPDOConnection->lastInsertId($name);
    }

    public function beginTransaction(): bool
    {
        return $this->doctrineDbalPDOConnection->beginTransaction();
    }

    public function commit(): bool
    {
        return $this->doctrineDbalPDOConnection->commit();
    }

    public function rollBack(): bool
    {
        return $this->doctrineDbalPDOConnection->rollBack();
    }

    public function getWrappedConnection(): \PDO
    {
        return $this->doctrineDbalPDOConnection->getWrappedConnection();
    }
}
