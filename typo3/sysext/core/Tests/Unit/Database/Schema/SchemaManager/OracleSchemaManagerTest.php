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

use Doctrine\DBAL\Platforms\OraclePlatform as DoctrineOraclePlatform;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Types\Type;
use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\Platform\OraclePlatform;
use TYPO3\CMS\Core\Database\Schema\Types\EnumType;
use TYPO3\CMS\Core\Database\Schema\Types\SetType;
use TYPO3\CMS\Core\Tests\Unit\Database\Schema\SchemaManager\Fixtures\SchemaManager\FixtureOracleSchemaManager;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class OracleSchemaManagerTest extends UnitTestCase
{
    /**
     * @test
     */
    public function isInactiveForStandardColumnTypes(): void
    {
        /** @var DoctrineOraclePlatform&MockObject $platformMock */
        $platformMock = $this->createMock(OraclePlatform::class);
        $connectionMock = $this->createConnectionMock($platformMock);
        $subject = $this->createSchemaManager($connectionMock, $platformMock);

        $column = $subject->callProcessCustomDoctrineTypesColumnDefinitionFromTraitDirectly(['Type' => 'int(11)']);

        self::assertNull($column);
    }

    /**
     * @test
     */
    public function buildsColumnForEnumDataType(): void
    {
        /** @var DoctrineOraclePlatform&MockObject $platformMock */
        $platformMock = $this->createMock(OraclePlatform::class);
        $platformMock->method('getDoctrineTypeMapping')->with('enum')->willReturn('enum');
        $connectionMock = $this->createConnectionMock($platformMock);
        $subject = $this->createSchemaManager($connectionMock, $platformMock);
        if (Type::hasType('enum')) {
            Type::overrideType('enum', EnumType::class);
        } else {
            Type::addType('enum', EnumType::class);
        }

        $column = $subject->callProcessCustomDoctrineTypesColumnDefinitionFromTraitDirectly(['Type' => "enum('value1', 'value2','value3')"]);
        self::assertInstanceOf(Column::class, $column);
        self::assertInstanceOf(EnumType::class, $column->getType());
        self::assertSame(['value1', 'value2', 'value3'], $column->getPlatformOption('unquotedValues'));

        $column = $subject->callProtectedGetPortableTableColumnDefinition(['Type' => "enum('value1', 'value2','value3')"]);
        self::assertInstanceOf(Column::class, $column);
        self::assertInstanceOf(EnumType::class, $column->getType());
        self::assertSame(['value1', 'value2', 'value3'], $column->getPlatformOption('unquotedValues'));
    }

    /**
     * @test
     */
    public function buildsColumnForSetDataType(): void
    {
        /** @var DoctrineOraclePlatform&MockObject $platformMock */
        $platformMock = $this->createMock(OraclePlatform::class);
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

    private function createSchemaManager(Connection $connection, DoctrineOraclePlatform $platform): FixtureOracleSchemaManager
    {
        return new FixtureOracleSchemaManager($connection, $platform);
    }

    private function createConnectionMock(DoctrineOraclePlatform $platform): Connection&MockObject
    {
        $connectionMock = $this->createMock(Connection::class);
        $connectionMock->method('getDatabasePlatform')->willReturn($platform);
        return $connectionMock;
    }
}
