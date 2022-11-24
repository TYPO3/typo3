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

namespace TYPO3\CMS\Core\Tests\Unit\Database\Schema\EventListener;

use Doctrine\DBAL\Event\SchemaColumnDefinitionEventArgs;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Types\Type;
use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\Schema\EventListener\SchemaColumnDefinitionListener;
use TYPO3\CMS\Core\Database\Schema\Types\EnumType;
use TYPO3\CMS\Core\Database\Schema\Types\SetType;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class SchemaColumnDefinitionListenerTest extends UnitTestCase
{
    protected SchemaColumnDefinitionListener $subject;
    protected Connection&MockObject $connectionMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = GeneralUtility::makeInstance(SchemaColumnDefinitionListener::class);
        $this->connectionMock = $this->createMock(Connection::class);
    }

    /**
     * @test
     */
    public function isInactiveForStandardColumnTypes(): void
    {
        $event = new SchemaColumnDefinitionEventArgs(
            ['Type' => 'int(11)'],
            'aTestTable',
            'aTestDatabase',
            $this->connectionMock
        );

        $this->subject->onSchemaColumnDefinition($event);
        self::assertNotTrue($event->isDefaultPrevented());
        self::assertNull($event->getColumn());
    }

    /**
     * @test
     */
    public function buildsColumnForEnumDataType(): void
    {
        if (Type::hasType('enum')) {
            Type::overrideType('enum', EnumType::class);
        } else {
            Type::addType('enum', EnumType::class);
        }
        $databasePlatformMock = $this->createMock(AbstractPlatform::class);
        $databasePlatformMock->method('getDoctrineTypeMapping')->with('enum')->willReturn('enum');
        $this->connectionMock->method('getDatabasePlatform')->willReturn($databasePlatformMock);

        $event = new SchemaColumnDefinitionEventArgs(
            ['Type' => "enum('value1', 'value2','value3')"],
            'aTestTable',
            'aTestDatabase',
            $this->connectionMock
        );

        $this->subject->onSchemaColumnDefinition($event);
        self::assertTrue($event->isDefaultPrevented());
        self::assertInstanceOf(Column::class, $event->getColumn());
        self::assertInstanceOf(EnumType::class, $event->getColumn()->getType());
        self::assertSame(['value1', 'value2', 'value3'], $event->getColumn()->getPlatformOption('unquotedValues'));
    }

    /**
     * @test
     */
    public function buildsColumnForSetDataType(): void
    {
        if (Type::hasType('set')) {
            Type::overrideType('set', SetType::class);
        } else {
            Type::addType('set', SetType::class);
        }
        $databasePlatformMock = $this->createMock(AbstractPlatform::class);
        $databasePlatformMock->method('getDoctrineTypeMapping')->with('set')->willReturn('set');
        $this->connectionMock->method('getDatabasePlatform')->willReturn($databasePlatformMock);

        $event = new SchemaColumnDefinitionEventArgs(
            ['Type' => "set('value1', 'value3')"],
            'aTestTable',
            'aTestDatabase',
            $this->connectionMock
        );

        $this->subject->onSchemaColumnDefinition($event);
        self::assertTrue($event->isDefaultPrevented());
        self::assertInstanceOf(Column::class, $event->getColumn());
        self::assertInstanceOf(SetType::class, $event->getColumn()->getType());
        self::assertSame(['value1', 'value3'], $event->getColumn()->getPlatformOption('unquotedValues'));
    }
}
