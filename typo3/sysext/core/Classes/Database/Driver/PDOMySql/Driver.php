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

namespace TYPO3\CMS\Core\Database\Driver\PDOMySql;

use Doctrine\DBAL\Driver\AbstractMySQLDriver;
use Doctrine\DBAL\Driver\Connection as DriverConnectionInterface;
use Doctrine\DBAL\Driver\PDO\Exception;
use PDO;
use PDOException;
use TYPO3\CMS\Core\Database\Driver\DriverConnection;

/**
 * The main change in favor of Doctrine's implementation is to use our custom
 * DriverConnection which creates a custom Result object.
 *
 * @internal this implementation is not part of TYPO3's Public API.
 */
final class Driver extends AbstractMySQLDriver
{
    public function connect(array $params): DriverConnectionInterface
    {
        $driverOptions = $params['driverOptions'] ?? [];

        if (! empty($params['persistent'])) {
            $driverOptions[PDO::ATTR_PERSISTENT] = true;
        }

        try {
            $pdo = new PDO(
                $this->constructPdoDsn($params),
                $params['user'] ?? '',
                $params['password'] ?? '',
                $driverOptions
            );
            // use prepared statements for pdo_mysql per default to retrieve native data types
            if (!isset($driverOptions[\PDO::ATTR_EMULATE_PREPARES])) {
                $pdo->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);
            }
        } catch (PDOException $exception) {
            throw Exception::new($exception);
        }

        return new DriverConnection($pdo);
    }

    /**
     * @return string MySql PDO DSN
     */
    private function constructPdoDsn(array $params): string
    {
        $dsn = 'mysql:';
        if (isset($params['host']) && $params['host'] !== '') {
            $dsn .= 'host=' . $params['host'] . ';';
        }
        if (isset($params['port'])) {
            $dsn .= 'port=' . $params['port'] . ';';
        }
        if (isset($params['dbname'])) {
            $dsn .= 'dbname=' . $params['dbname'] . ';';
        }
        if (isset($params['unix_socket'])) {
            $dsn .= 'unix_socket=' . $params['unix_socket'] . ';';
        }
        if (isset($params['charset'])) {
            $dsn .= 'charset=' . $params['charset'] . ';';
        }
        return $dsn;
    }
}
