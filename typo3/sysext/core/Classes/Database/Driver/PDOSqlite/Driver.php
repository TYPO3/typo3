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
 * The main change in favor of Doctrine's implementation is to use our custom
 * DriverConnection which creates a custom Result object.
 *
 * @internal this implementation is not part of TYPO3's Public API.
 */
final class Driver extends AbstractSQLiteDriver
{
    private array $userDefinedFunctions = [
        'sqrt' => ['callback' => [SqlitePlatform::class, 'udfSqrt'], 'numArgs' => 1],
        'mod' => ['callback' => [SqlitePlatform::class, 'udfMod'], 'numArgs' => 2],
        'locate' => ['callback' => [SqlitePlatform::class, 'udfLocate'], 'numArgs' => -1],
    ];

    public function connect(array $params): DriverConnectionInterface
    {
        $driverOptions = $params['driverOptions'] ?? [];

        if (isset($driverOptions['userDefinedFunctions'])) {
            $this->userDefinedFunctions = array_merge(
                $this->userDefinedFunctions,
                $driverOptions['userDefinedFunctions']
            );
            unset($driverOptions['userDefinedFunctions']);
        }

        try {
            $pdo = new \PDO(
                $this->constructPdoDsn($params),
                $params['user'] ?? '',
                $params['password'] ?? '',
                $driverOptions
            );
        } catch (\PDOException $exception) {
            throw Exception::new($exception);
        }

        foreach ($this->userDefinedFunctions as $fn => $data) {
            $pdo->sqliteCreateFunction($fn, $data['callback'], $data['numArgs']);
        }

        return new TYPO3DriverConnection($pdo);
    }

    /**
     * @return string Sqlite PDO DSN
     */
    private function constructPdoDsn(array $params): string
    {
        $dsn = 'sqlite:';
        if (isset($params['path'])) {
            $dsn .= $params['path'];
        } elseif (isset($params['memory'])) {
            $dsn .= ':memory:';
        }
        return $dsn;
    }
}
