<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Core\Database\Driver\PDOMySql;

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

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Driver\PDOMySql\Driver as DoctrinePDOMySqlDriver;
use PDOException;
use TYPO3\CMS\Core\Database\Driver\PDOConnection;

/**
 * This is a full "clone" of the class of package doctrine/dbal. Scope is to use the PDOConnection of TYPO3.
 * All private methods have to be checked on every release of doctrine/dbal.
 */
class Driver extends DoctrinePDOMySqlDriver
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
}
