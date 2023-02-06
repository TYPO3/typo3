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

use Doctrine\DBAL\Platforms\AbstractPlatform as DoctrineAbstractPlatform;
use Doctrine\DBAL\Platforms\MariaDBPlatform as DoctrineMariaDBPlatform;
use Doctrine\DBAL\Platforms\MySQLPlatform as DoctrineMySQLPlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform as DoctrinePostgreSQLPlatform;
use Doctrine\DBAL\Platforms\SQLitePlatform as DoctrineSQLitePlatform;
use TYPO3\CMS\Core\Database\Platform\PlatformInformation;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class PlatformInformationTest extends UnitTestCase
{
    /**
     * @return array<string, array{0: class-string<DoctrineAbstractPlatform>}>
     */
    public static function platformDataProvider(): array
    {
        return [
            'mysql' => [DoctrineMySQLPlatform::class],
            'mariadb' => [DoctrineMariaDBPlatform::class],
            'postgresql' => [DoctrinePostgreSQLPlatform::class],
            'sqlite' => [DoctrineSQLitePlatform::class],
        ];
    }

    /**
     * @test
     * @param class-string<DoctrineAbstractPlatform> $platform
     * @dataProvider platformDataProvider
     */
    public function maxBindParameters(string $platform): void
    {
        $platformMock = $this->createMock($platform);
        self::assertGreaterThanOrEqual(1, PlatformInformation::getMaxBindParameters($platformMock));
    }

    /**
     * @test
     * @param class-string<DoctrineAbstractPlatform> $platform
     * @dataProvider platformDataProvider
     */
    public function maxIdentifierLength(string $platform): void
    {
        $platformMock = $this->createMock($platform);
        self::assertGreaterThanOrEqual(1, PlatformInformation::getMaxIdentifierLength($platformMock));
    }

    /**
     * @test
     */
    public function maxBindParametersWithUnknownPlatform(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1500958070);
        $platformMock = $this->createMock(DoctrineAbstractPlatform::class);
        self::assertGreaterThanOrEqual(1, PlatformInformation::getMaxBindParameters($platformMock));
    }

    /**
     * @test
     */
    public function maxIdentifierLengthWithUnknownPlatform(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1500958070);
        $platformMock = $this->createMock(DoctrineAbstractPlatform::class);
        self::assertGreaterThanOrEqual(1, PlatformInformation::getMaxIdentifierLength($platformMock));
    }
}
