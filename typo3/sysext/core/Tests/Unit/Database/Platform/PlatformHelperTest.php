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
use Doctrine\DBAL\Platforms\MariaDB1052Platform as DoctrineMariaDB1052Platform;
use Doctrine\DBAL\Platforms\MariaDBPlatform as DoctrineMariaDBPlatform;
use Doctrine\DBAL\Platforms\MySQL80Platform as DoctrineMySQL80Platform;
use Doctrine\DBAL\Platforms\MySQLPlatform as DoctrineMySQLPlatform;
use Doctrine\DBAL\Platforms\OraclePlatform as DoctrineOraclePlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform as DoctrinePostgreSQLPlatform;
use Doctrine\DBAL\Platforms\SQLitePlatform as DoctrineSQLitePlatform;
use TYPO3\CMS\Core\Database\Platform\MariaDB1052Platform as Typo3MariaDB1052Platform;
use TYPO3\CMS\Core\Database\Platform\MariaDBPlatform as Typo3MariaDBPlatform;
use TYPO3\CMS\Core\Database\Platform\MySQL80Platform as Typo3MySQL80Platform;
use TYPO3\CMS\Core\Database\Platform\MySQLPlatform as Typo3MySQLPlatform;
use TYPO3\CMS\Core\Database\Platform\PlatformHelper;
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
    }
}
