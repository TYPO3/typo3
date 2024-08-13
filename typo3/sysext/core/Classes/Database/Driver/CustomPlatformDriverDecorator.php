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
use Doctrine\DBAL\Platforms\MariaDB1010Platform as DoctrineMariaDB1010Platform;
use Doctrine\DBAL\Platforms\MariaDB1052Platform as DoctrineMariaDB1052Platform;
use Doctrine\DBAL\Platforms\MariaDB1060Platform as DoctrineMariaDB1060Platform;
use Doctrine\DBAL\Platforms\MariaDBPlatform as DoctrineMariaDBPlatform;
use Doctrine\DBAL\Platforms\MySQL80Platform as DoctrineMySQL80Platform;
use Doctrine\DBAL\Platforms\MySQL84Platform as DoctrineMySQL84Platform;
use Doctrine\DBAL\Platforms\MySQLPlatform as DoctrineMySQLPlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform as DoctrinePostgreSQLPlatform;
use Doctrine\DBAL\Platforms\SQLitePlatform as DoctrineSQLitePlatform;
use Doctrine\DBAL\ServerVersionProvider;
use TYPO3\CMS\Core\Database\Platform\MariaDB1010Platform as Typo3MariaDB1010Platform;
use TYPO3\CMS\Core\Database\Platform\MariaDB1052Platform as Typo3MariaDB1052Platform;
use TYPO3\CMS\Core\Database\Platform\MariaDB1060Platform as Typo3MariaDB1060Platform;
use TYPO3\CMS\Core\Database\Platform\MariaDBPlatform as Typo3MariaDBPlatform;
use TYPO3\CMS\Core\Database\Platform\MySQL80Platform as Typo3MySQL80Platform;
use TYPO3\CMS\Core\Database\Platform\MySQL84Platform as Typo3MySQL84Platform;
use TYPO3\CMS\Core\Database\Platform\MySQLPlatform as Typo3MySQLPlatform;
use TYPO3\CMS\Core\Database\Platform\PostgreSQLPlatform as Typo3PostgreSQLPlatform;
use TYPO3\CMS\Core\Database\Platform\SQLitePlatform as Typo3SQLitePlatform;

/**
 * @internal this implementation is not part of TYPO3's Public API.
 */
final class CustomPlatformDriverDecorator extends AbstractDriverMiddleware
{
    public function getDatabasePlatform(ServerVersionProvider $versionProvider): AbstractPlatform
    {
        return $this->elevatePlatform(parent::getDatabasePlatform($versionProvider));
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
            DoctrineMySQLPlatform::class => new Typo3MySQLPlatform(),
            DoctrineMySQL80Platform::class => new Typo3MySQL80Platform(),
            DoctrineMySQL84Platform::class => new Typo3MySQL84Platform(),
            DoctrineMariaDBPlatform::class => new Typo3MariaDBPlatform(),
            DoctrineMariaDB1052Platform::class => new Typo3MariaDB1052Platform(),
            DoctrineMariaDB1060Platform::class => new Typo3MariaDB1060Platform(),
            DoctrineMariaDB1010Platform::class => new Typo3MariaDB1010Platform(),
            DoctrineSQLitePlatform::class  => new Typo3SQLitePlatform(),
            DoctrinePostgreSQLPlatform::class => new Typo3PostgreSQLPlatform(),
            default => $platform,
        };
    }
}
