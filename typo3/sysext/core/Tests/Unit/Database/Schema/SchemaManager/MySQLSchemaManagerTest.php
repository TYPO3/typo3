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

namespace TYPO3\CMS\Core\Tests\Unit\Database\Schema\SchemaManager;

use Doctrine\DBAL\Platforms\AbstractMySQLPlatform as DoctrineAbstractMySQLPlatform;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Types\EnumType;
use Doctrine\DBAL\Types\Type;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\Platform\MariaDB1052Platform;
use TYPO3\CMS\Core\Database\Platform\MariaDBPlatform;
use TYPO3\CMS\Core\Database\Platform\MySQL80Platform;
use TYPO3\CMS\Core\Database\Platform\MySQLPlatform;
use TYPO3\CMS\Core\Database\Schema\Types\SetType;
use TYPO3\CMS\Core\Tests\Unit\Database\Schema\SchemaManager\Fixtures\SchemaManager\FixtureMySQLSchemaManager;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class MySQLSchemaManagerTest extends UnitTestCase
{
    public static function platformDataProvider(): \Generator
    {
        yield 'Use TYPO3 MySQLPlatform' => [
            'platform' => MySQLPlatform::class,
        ];
        yield 'Use TYPO3 MySQL80Platform' => [
            'platform' => MySQL80Platform::class,
        ];
        yield 'Use TYPO3 MariaDBPlatform' => [
            'platform' => MariaDBPlatform::class,
        ];
        yield 'Use TYPO3 MariaDB1052Platform' => [
            'platform' => MariaDB1052Platform::class,
        ];
    }

    #[DataProvider('platformDataProvider')]
    #[Test]
    public function isInactiveForStandardColumnTypes(string $platform): void
    {
        /** @var DoctrineAbstractMySQLPlatform&MockObject $platformMock */
        $platformMock = $this->createMock($platform);
        $connectionMock = $this->createConnectionMock($platformMock);
        $subject = $this->createSchemaManager($connectionMock, $platformMock);

        $column = $subject->callProcessCustomDoctrineTypesColumnDefinitionFromTraitDirectly(['Type' => 'int(11)']);

        self::assertNull($column);
    }

    #[DataProvider('platformDataProvider')]
    #[Test]
    public function buildsColumnForEnumDataType(string $platform): void
    {
        /** @var DoctrineAbstractMySQLPlatform&MockObject $platformMock */
        $platformMock = $this->createMock($platform);
        $platformMock->method('getDoctrineTypeMapping')->with('enum')->willReturn('enum');
        $connectionMock = $this->createConnectionMock($platformMock);
        $subject = $this->createSchemaManager($connectionMock, $platformMock);

        $column = $subject->callProcessCustomDoctrineTypesColumnDefinitionFromTraitDirectly(['Type' => "enum('value1', 'value2','value3')"]);
        self::assertInstanceOf(Column::class, $column);
        self::assertInstanceOf(EnumType::class, $column->getType());
        self::assertSame(['value1', 'value2', 'value3'], $column->getPlatformOption('values'));

        $column = $subject->callProtectedGetPortableTableColumnDefinition(['Type' => "enum('value1', 'value2','value3')"]);
        self::assertInstanceOf(Column::class, $column);
        self::assertInstanceOf(EnumType::class, $column->getType());
        self::assertSame(['value1', 'value2', 'value3'], $column->getPlatformOption('values'));
    }

    #[DataProvider('platformDataProvider')]
    #[Test]
    public function buildsColumnForSetDataType(string $platform): void
    {
        /** @var DoctrineAbstractMySQLPlatform&MockObject $platformMock */
        $platformMock = $this->createMock($platform);
        $platformMock->method('getDoctrineTypeMapping')->with('set')->willReturn('set');
        $connectionMock = $this->createConnectionMock($platformMock);
        $subject = $this->createSchemaManager($connectionMock, $platformMock);
        if (Type::hasType('set')) {
            Type::overrideType('set', SetType::class);
        } else {
            Type::addType('set', SetType::class);
        }

        $column = $subject->callProcessCustomDoctrineTypesColumnDefinitionFromTraitDirectly(['Type' => "set('value1', 'value3')"]);
        self::assertInstanceOf(Column::class, $column);
        self::assertInstanceOf(SetType::class, $column->getType());
        self::assertSame(['value1', 'value3'], $column->getPlatformOption('values'));

        $column = $subject->callProtectedGetPortableTableColumnDefinition(['Type' => "set('value1', 'value3')"]);
        self::assertInstanceOf(Column::class, $column);
        self::assertInstanceOf(SetType::class, $column->getType());
        self::assertSame(['value1', 'value3'], $column->getPlatformOption('values'));
    }
    private function createSchemaManager(Connection $connection, DoctrineAbstractMySQLPlatform $platform): FixtureMySQLSchemaManager
    {
        return new FixtureMySQLSchemaManager($connection, $platform);
    }

    private function createConnectionMock(DoctrineAbstractMySQLPlatform $platform): Connection&MockObject
    {
        $connectionMock = $this->createMock(Connection::class);
        $connectionMock->method('getDatabasePlatform')->willReturn($platform);
        return $connectionMock;
    }
}
