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

use Doctrine\DBAL\Platforms\SQLitePlatform as DoctrineSQLitePlatform;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Types\EnumType;
use Doctrine\DBAL\Types\Type;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\Platform\SQLitePlatform;
use TYPO3\CMS\Core\Database\Schema\Types\SetType;
use TYPO3\CMS\Core\Tests\Unit\Database\Schema\SchemaManager\Fixtures\SchemaManager\FixtureSQLiteSchemaManager;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class SQLiteSchemaManagerTest extends UnitTestCase
{
    #[Test]
    public function isInactiveForStandardColumnTypes(): void
    {
        /** @var DoctrineSQLitePlatform&MockObject $platformMock */
        $platformMock = $this->createMock(SQLitePlatform::class);
        $connectionMock = $this->createConnectionMock($platformMock);
        $subject = $this->createSchemaManager($connectionMock, $platformMock);

        $column = $subject->callProcessCustomDoctrineTypesColumnDefinitionFromTraitDirectly(['Type' => 'int(11)']);

        self::assertNull($column);
    }

    #[Test]
    public function buildsColumnForEnumDataType(): void
    {
        /** @var DoctrineSQLitePlatform&MockObject $platformMock */
        $platformMock = $this->createMock(SQLitePlatform::class);
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

    #[Test]
    public function buildsColumnForSetDataType(): void
    {
        /** @var DoctrineSQLitePlatform&MockObject $platformMock */
        $platformMock = $this->createMock(SQLitePlatform::class);
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
        self::assertSame(['value1', 'value3'], $column->getPlatformOption('unquotedValues'));

        $column = $subject->callProtectedGetPortableTableColumnDefinition(['Type' => "set('value1', 'value3')"]);
        self::assertInstanceOf(Column::class, $column);
        self::assertInstanceOf(SetType::class, $column->getType());
        self::assertSame(['value1', 'value3'], $column->getPlatformOption('unquotedValues'));
    }

    private function createSchemaManager(Connection $connection, DoctrineSQLitePlatform $platform): FixtureSQLiteSchemaManager
    {
        return new FixtureSQLiteSchemaManager($connection, $platform);
    }

    private function createConnectionMock(DoctrineSQLitePlatform $platform): Connection&MockObject
    {
        $connectionMock = $this->createMock(Connection::class);
        $connectionMock->method('getDatabasePlatform')->willReturn($platform);
        return $connectionMock;
    }
}
