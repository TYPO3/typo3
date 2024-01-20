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

use Doctrine\DBAL\Driver\Middleware\AbstractDriverMiddleware;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\MariaDb1027Platform as DoctrineMariaDB1027Platform;
use Doctrine\DBAL\Platforms\MariaDb1043Platform as DoctrineMariaDB1043Platform;
use Doctrine\DBAL\Platforms\MariaDb1052Platform as DoctrineMariaDB1052Platform;
use Doctrine\DBAL\Platforms\MariaDBPlatform as DoctrineMariaDBPlatform;
use Doctrine\DBAL\Platforms\MySQL57Platform as DoctrineMySQL57Platform;
use Doctrine\DBAL\Platforms\MySQL80Platform as DoctrineMySQL80Platform;
use Doctrine\DBAL\Platforms\MySQLPlatform as DoctrineMySQLPlatform;
use Doctrine\DBAL\Platforms\PostgreSQL100Platform as DoctrinePostgreSQL100Platform;
use Doctrine\DBAL\Platforms\PostgreSQL94Platform as DoctrinePostgreSQL94Platform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform as DoctrinePostgreSQLPlatform;
use Doctrine\DBAL\Platforms\SqlitePlatform as DoctrineSQLitePlatform;
use TYPO3\CMS\Core\Database\Platform\MariaDB1027Platform;
use TYPO3\CMS\Core\Database\Platform\MariaDB1043Platform;
use TYPO3\CMS\Core\Database\Platform\MariaDB1052Platform;
use TYPO3\CMS\Core\Database\Platform\MariaDBPlatform;
use TYPO3\CMS\Core\Database\Platform\MySQL57Platform;
use TYPO3\CMS\Core\Database\Platform\MySQL80Platform;
use TYPO3\CMS\Core\Database\Platform\MySQLPlatform;
use TYPO3\CMS\Core\Database\Platform\PostgreSQL100Platform;
use TYPO3\CMS\Core\Database\Platform\PostgreSQL94Platform;
use TYPO3\CMS\Core\Database\Platform\PostgreSQLPlatform;
use TYPO3\CMS\Core\Database\Platform\SQLitePlatform;

/**
 * @internal this implementation is not part of TYPO3's Public API.
 */
final class CustomPlatformDriverDecorator extends AbstractDriverMiddleware
{
    /**
     * Note: The method signature will change with doctrine/dbal 4.0 getting the database platform passed.
     */
    public function getDatabasePlatform(): AbstractPlatform
    {
        return $this->elevatePlatform(parent::getDatabasePlatform());
    }

    public function createDatabasePlatformForVersion($version)
    {
        return $this->elevatePlatform(parent::createDatabasePlatformForVersion($version));
    }

    /**
     * Due to the deprecation doctrine/event-manager usage in doctrine/dbal the platform classes needs to be extended
     * to still provide the same behaviour as before. Therefore, we replace the doctrine platform instances with our
     * extended classes.
     *
     * @param AbstractPlatform $platform
     * @return AbstractPlatform
     */
    private function elevatePlatform(AbstractPlatform $platform): AbstractPlatform
    {
        return match ($platform::class) {
            DoctrineMySQLPlatform::class => new MySQLPlatform(),
            DoctrineMySQL80Platform::class => new MySQL80Platform(),
            DoctrineMariaDBPlatform::class => new MariaDBPlatform(),
            DoctrineMariaDB1052Platform::class => new MariaDB1052Platform(),
            DoctrineSQLitePlatform::class  => new SQLitePlatform(),
            DoctrinePostgreSQLPlatform::class => new PostgreSQLPlatform(),
            // @todo Remove following platforms with doctrine/dbal 4.0 (except default)
            DoctrinePostgreSQL94Platform::class => new PostgreSQL94Platform(),
            DoctrinePostgreSQL100Platform::class => new PostgreSQL100Platform(),
            DoctrineMySQL57Platform::class => new MySQL57Platform(),
            DoctrineMariaDB1027Platform::class => new MariaDB1027Platform(),
            DoctrineMariaDB1043Platform::class => new MariaDB1043Platform(),
            default => $platform,
        };
    }
}
