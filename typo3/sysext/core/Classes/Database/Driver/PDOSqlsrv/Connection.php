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

namespace TYPO3\CMS\Core\Database\Driver\PDOSqlsrv;

use Doctrine\DBAL\Driver\PDO\Exception;
use Doctrine\DBAL\Driver\Statement as StatementInterface;
use TYPO3\CMS\Core\Database\Driver\DriverConnection;

/**
 * This is a full "clone" of the class of package doctrine/dbal. Scope is to use the PDOConnection of TYPO3.
 *
 * @internal this implementation is not part of TYPO3's Public API.
 */
class Connection extends DriverConnection
{
    public function prepare(string $sql): StatementInterface
    {
        try {
            $stmt = $this->connection->prepare($sql);
            assert($stmt instanceof \PDOStatement);

            // use TYPO3's Sqlsrv Statement object in favor of Doctrine's Statement wrapper
            return new Statement($stmt);
        } catch (\PDOException $exception) {
            throw Exception::new($exception);
        }
    }
    /**
     * {@inheritDoc}
     */
    public function lastInsertId($name = null)
    {
        if ($name === null) {
            return parent::lastInsertId($name);
        }

        $stmt = $this->prepare('SELECT CONVERT(VARCHAR(MAX), current_value) FROM sys.sequences WHERE name = ?');
        $result = $stmt->execute([$name]);
        return $result->fetchOne();
    }
}
