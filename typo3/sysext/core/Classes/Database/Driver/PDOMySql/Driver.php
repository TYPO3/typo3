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
use Doctrine\DBAL\Exception as DBALException;
use PDOException;
use TYPO3\CMS\Core\Database\Driver\PDOConnection;

/**
 * This is a full "clone" of the class of package doctrine/dbal. Scope is to use the PDOConnection of TYPO3.
 * All private methods have to be checked on every release of doctrine/dbal.
 */
class Driver extends AbstractMySQLDriver
{
    /**
     * {@inheritdoc}
     */
    public function connect(array $params, $username = null, $password = null, array $driverOptions = [])
    {
        try {
            $conn = new PDOConnection(
                $this->constructPdoDsn($params),
                $username,
                $password,
                $driverOptions
            );

            // use prepared statements for pdo_mysql per default to retrieve native data types
            if (!isset($driverOptions[\PDO::ATTR_EMULATE_PREPARES])) {
                $conn->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);
            }
        } catch (PDOException $e) {
            throw DBALException::driverException($this, $e);
        }

        return $conn;
    }

    /**
     * Constructs the MySql PDO DSN.
     *
     * @param mixed[] $params
     *
     * @return string The DSN.
     */
    protected function constructPdoDsn(array $params)
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

    /**
     * {@inheritdoc}
     *
     * @deprecated
     */
    public function getName()
    {
        return 'pdo_mysql';
    }
}
