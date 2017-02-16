<?php
declare(strict_types=1);

namespace TYPO3\CMS\Core\Tests\Unit\Database;

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

use Doctrine\DBAL\Event\SchemaColumnDefinitionEventArgs;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Types\Type;
use Prophecy\Prophecy\ObjectProphecy;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\Schema\EventListener\SchemaColumnDefinitionListener;
use TYPO3\CMS\Core\Database\Schema\Types\EnumType;
use TYPO3\CMS\Core\Database\Schema\Types\SetType;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Test case
 */
class SchemaColumnDefinitionListenerTest extends \TYPO3\TestingFramework\Core\Unit\UnitTestCase
{
    /**
     * @var SchemaColumnDefinitionListener
     */
    protected $subject;

    /**
     * @var Connection|ObjectProphecy
     */
    protected $connectionProphet;

    /**
     * Set up the test subject
     */
    protected function setUp()
    {
        parent::setUp();
        $this->subject = GeneralUtility::makeInstance(SchemaColumnDefinitionListener::class);
        $this->connectionProphet = $this->prophesize(Connection::class);
    }

    /**
     * @test
     */
    public function isInactiveForStandardColumnTypes()
    {
        $event = new SchemaColumnDefinitionEventArgs(
            ['Type' => 'int(11)'],
            'aTestTable',
            'aTestDatabase',
            $this->connectionProphet->reveal()
        );

        $this->subject->onSchemaColumnDefinition($event);
        $this->assertNotTrue($event->isDefaultPrevented());
        $this->assertNull($event->getColumn());
    }

    /**
     * @test
     */
    public function buildsColumnForEnumDataType()
    {
        if (Type::hasType('enum')) {
            Type::overrideType('enum', EnumType::class);
        } else {
            Type::addType('enum', EnumType::class);
        }
        $databasePlatformProphet = $this->prophesize(AbstractPlatform::class);
        $databasePlatformProphet->getDoctrineTypeMapping('enum')->willReturn('enum');
        $this->connectionProphet->getDatabasePlatform()->willReturn($databasePlatformProphet->reveal());

        $event = new SchemaColumnDefinitionEventArgs(
            ['Type' => "enum('value1', 'value2','value3')"],
            'aTestTable',
            'aTestDatabase',
            $this->connectionProphet->reveal()
        );

        $this->subject->onSchemaColumnDefinition($event);
        $this->assertTrue($event->isDefaultPrevented());
        $this->assertInstanceOf(Column::class, $event->getColumn());
        $this->assertInstanceOf(EnumType::class, $event->getColumn()->getType());
        $this->assertSame(['value1', 'value2', 'value3'], $event->getColumn()->getPlatformOption('unquotedValues'));
    }

    /**
     * @test
     */
    public function buildsColumnForSetDataType()
    {
        if (Type::hasType('set')) {
            Type::overrideType('set', SetType::class);
        } else {
            Type::addType('set', SetType::class);
        }
        $databasePlatformProphet = $this->prophesize(AbstractPlatform::class);
        $databasePlatformProphet->getDoctrineTypeMapping('set')->willReturn('set');
        $this->connectionProphet->getDatabasePlatform()->willReturn($databasePlatformProphet->reveal());

        $event = new SchemaColumnDefinitionEventArgs(
            ['Type' => "set('value1', 'value3')"],
            'aTestTable',
            'aTestDatabase',
            $this->connectionProphet->reveal()
        );

        $this->subject->onSchemaColumnDefinition($event);
        $this->assertTrue($event->isDefaultPrevented());
        $this->assertInstanceOf(Column::class, $event->getColumn());
        $this->assertInstanceOf(SetType::class, $event->getColumn()->getType());
        $this->assertSame(['value1', 'value3'], $event->getColumn()->getPlatformOption('unquotedValues'));
    }
}
