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

namespace TYPO3\CMS\Core\Tests\Unit\Database\Platform;

use Doctrine\DBAL\Platforms\AbstractMySQLPlatform as DoctrineAbstractMySQLPlatform;
use Doctrine\DBAL\Platforms\AbstractPlatform as DoctrineAbstractPlatform;
use Doctrine\DBAL\Platforms\DB2Platform as DoctrineDB2Platform;
use Doctrine\DBAL\Platforms\MariaDb1027Platform as DoctrineMariaDb1027Platform;
use Doctrine\DBAL\Platforms\MariaDb1043Platform as DoctrineMariaDb1043Platform;
use Doctrine\DBAL\Platforms\MariaDb1052Platform as DoctrineMariaDB1052Platform;
use Doctrine\DBAL\Platforms\MariaDBPlatform as DoctrineMariaDBPlatform;
use Doctrine\DBAL\Platforms\MySQL57Platform as DoctrineMySQL57Platform;
use Doctrine\DBAL\Platforms\MySQL80Platform as DoctrineMySQL80Platform;
use Doctrine\DBAL\Platforms\MySQLPlatform as DoctrineMySQLPlatform;
use Doctrine\DBAL\Platforms\OraclePlatform as DoctrineOraclePlatform;
use Doctrine\DBAL\Platforms\PostgreSQL100Platform as DoctrinePostgreSQL100Platform;
use Doctrine\DBAL\Platforms\PostgreSQL94Platform as DoctrinePostgreSQL94Platform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform as DoctrinePostgreSQLPlatform;
use Doctrine\DBAL\Platforms\SqlitePlatform as DoctrineSQLitePlatform;
use TYPO3\CMS\Core\Database\Platform\MariaDB1027Platform as Typo3MariaDB1027Platform;
use TYPO3\CMS\Core\Database\Platform\MariaDB1043Platform as Typo3MariaDB1043Platform;
use TYPO3\CMS\Core\Database\Platform\MariaDB1052Platform as Typo3MariaDB1052Platform;
use TYPO3\CMS\Core\Database\Platform\MariaDBPlatform as Typo3MariaDBPlatform;
use TYPO3\CMS\Core\Database\Platform\MySQL57Platform as Typo3MySQL57Platform;
use TYPO3\CMS\Core\Database\Platform\MySQL80Platform as Typo3MySQL80Platform;
use TYPO3\CMS\Core\Database\Platform\MySQLPlatform as Typo3MySQLPlatform;
use TYPO3\CMS\Core\Database\Platform\OraclePlatform as Typo3OraclePlatform;
use TYPO3\CMS\Core\Database\Platform\PlatformHelper;
use TYPO3\CMS\Core\Database\Platform\PostgreSQL100Platform as Typo3PostgreSQL100Platform;
use TYPO3\CMS\Core\Database\Platform\PostgreSQL94Platform as Typo3PostgreSQL94Platform;
use TYPO3\CMS\Core\Database\Platform\PostgreSQLPlatform as Typo3PostgreSQLPlatform;
use TYPO3\CMS\Core\Database\Platform\SQLitePlatform as Typo3SQLitePlatform;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class PlatformHelperTest extends UnitTestCase
{
    public static function getTestPlatforms(): \Generator
    {
        // **supported platforms**
        // MySQL based
        yield 'Supported ' . DoctrineAbstractMySQLPlatform::class => [new class () extends DoctrineAbstractMySQLPlatform {}];
        // MySQL specific
        yield 'Supported ' . DoctrineMySQLPlatform::class => [new DoctrineMySQLPlatform()];
        yield 'Supported ' . DoctrineMySQL80Platform::class => [new DoctrineMySQL80Platform()];
        yield 'Supported ' . Typo3MySQLPlatform::class => [new Typo3MySQLPlatform()];
        yield 'Supported ' . Typo3MySQL80Platform::class => [new Typo3MySQL80Platform()];
        // MariaDB specific
        yield 'Supported ' . DoctrineMariaDBPlatform::class => [new DoctrineMariaDBPlatform()];
        yield 'Supported ' . DoctrineMariaDB1052Platform::class => [new DoctrineMariaDB1052Platform()];
        yield 'Supported ' . Typo3MariaDBPlatform::class => [new Typo3MariaDBPlatform()];
        yield 'Supported ' . Typo3MariaDB1052Platform::class => [new Typo3MariaDB1052Platform()];
        // PostgreSQL specific
        yield 'Supported ' . DoctrinePostgreSQLPlatform::class => [new DoctrinePostgreSQLPlatform()];
        yield 'Supported ' . Typo3PostgreSQLPlatform::class => [new Typo3PostgreSQLPlatform()];
        // SQLite specific
        yield 'Supported ' . DoctrineSQLitePlatform::class => [new DoctrineSQLitePlatform()];
        yield 'Supported ' . Typo3SQLitePlatform::class => [new Typo3SQLitePlatform()];

        // **unsupported platforms (compat check)**
        yield 'Unsupported ' . DoctrineDB2Platform::class => [new DoctrineDB2Platform()];
        yield 'Unsupported ' . DoctrineOraclePlatform::class => [new DoctrineOraclePlatform()];
        yield 'Unsupported ' . Typo3OraclePlatform::class => [new Typo3OraclePlatform()];

        // @todo deprecated by Doctrine DBAL, remove with Doctrine DBAL 4.x upgrade
        yield 'Supported ' . DoctrineMySQL57Platform::class => [new DoctrineMySQL57Platform()];
        yield 'Supported ' . Typo3MySQL57Platform::class => [new Typo3MySQL57Platform()];
        yield 'Supported ' . DoctrineMariaDb1027Platform::class => [new DoctrineMariaDb1027Platform()];
        yield 'Supported ' . Typo3MariaDB1027Platform::class => [new Typo3MariaDB1027Platform()];
        yield 'Supported ' . DoctrineMariaDb1043Platform::class => [new DoctrineMariaDb1043Platform()];
        yield 'Supported ' . Typo3MariaDB1043Platform::class => [new Typo3MariaDB1043Platform()];
        yield 'Supported ' . DoctrinePostgreSQL94Platform::class => [new DoctrinePostgreSQL94Platform()];
        yield 'Supported ' . Typo3PostgreSQL94Platform::class => [new Typo3PostgreSQL94Platform()];
        yield 'Supported ' . DoctrinePostgreSQL100Platform::class => [new DoctrinePostgreSQL100Platform()];
        yield 'Supported ' . Typo3PostgreSQL100Platform::class => [new Typo3PostgreSQL100Platform()];
    }

    /**
     * @test
     * @dataProvider getTestPlatforms
     *
     * @param DoctrineAbstractPlatform $platform
     */
    public function getIdentifierQuoteCharacterReturnsExpectedValue(DoctrineAbstractPlatform $platform): void
    {
        $expectedIdentifierQuoteChar = $platform->quoteIdentifier('fake_identifier')[0];
        self::assertSame($expectedIdentifierQuoteChar, (new PlatformHelper())->getIdentifierQuoteCharacter($platform));
        // @todo Remove following assertion with Doctrine DBAL 4.x upgrade. Method is removed in next major version.
        self::assertSame($expectedIdentifierQuoteChar, $platform->getIdentifierQuoteCharacter());
    }
}
