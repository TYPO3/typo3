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

namespace TYPO3\CMS\Core\Database\Driver\PDOSqlite;

use Doctrine\DBAL\Driver\AbstractSQLiteDriver;
use Doctrine\DBAL\Driver\Connection as DriverConnectionInterface;
use Doctrine\DBAL\Driver\PDO\Exception;
use Doctrine\DBAL\Platforms\SqlitePlatform;
use TYPO3\CMS\Core\Database\Driver\DriverConnection as TYPO3DriverConnection;

/**
 * The main change in favor of Doctrine's implementation is to use our custom DriverConnection (which in turn creates
 * a custom Result object).
 *
 * @internal this implementation is not part of TYPO3's Public API.
 */
class Driver extends AbstractSQLiteDriver
{
    /**
     * @var mixed[]
     */
    protected $_userDefinedFunctions = [
        'sqrt' => ['callback' => [SqlitePlatform::class, 'udfSqrt'], 'numArgs' => 1],
        'mod' => ['callback' => [SqlitePlatform::class, 'udfMod'], 'numArgs' => 2],
        'locate' => ['callback' => [SqlitePlatform::class, 'udfLocate'], 'numArgs' => -1],
    ];

    /**
     * {@inheritdoc}
     *
     * @return DriverConnectionInterface
     */
    public function connect(array $params)
    {
        $driverOptions = $params['driverOptions'] ?? [];

        if (isset($driverOptions['userDefinedFunctions'])) {
            $this->_userDefinedFunctions = array_merge(
                $this->_userDefinedFunctions,
                $driverOptions['userDefinedFunctions']
            );
            unset($driverOptions['userDefinedFunctions']);
        }

        try {
            $pdo = new \PDO(
                $this->_constructPdoDsn($params),
                $params['user'] ?? '',
                $params['password'] ?? '',
                $driverOptions
            );
        } catch (\PDOException $exception) {
            throw Exception::new($exception);
        }

        foreach ($this->_userDefinedFunctions as $fn => $data) {
            $pdo->sqliteCreateFunction($fn, $data['callback'], $data['numArgs']);
        }

        return new TYPO3DriverConnection($pdo);
    }

    /**
     * Constructs the Sqlite PDO DSN.
     *
     * @param mixed[] $params
     *
     * @return string The DSN.
     */
    protected function _constructPdoDsn(array $params)
    {
        $dsn = 'sqlite:';
        if (isset($params['path'])) {
            $dsn .= $params['path'];
        } elseif (isset($params['memory'])) {
            $dsn .= ':memory:';
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
        return 'pdo_sqlite';
    }
}
