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

namespace TYPO3\CMS\Form\Tests\Unit\Domain\Finishers;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Form\Domain\Finishers\Exception\FinisherException;
use TYPO3\CMS\Form\Domain\Finishers\FinisherContext;
use TYPO3\CMS\Form\Domain\Finishers\SaveToDatabaseFinisher;
use TYPO3\CMS\Form\Domain\Model\FormElements\FormElementInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class SaveToDatabaseFinisherTest extends UnitTestCase
{
    #[Test]
    public function throwExceptionOnInconsistentConfigurationThrowsExceptionOnInconsistentConfiguration(): void
    {
        $this->expectException(FinisherException::class);
        $this->expectExceptionCode(1480469086);
        $mockSaveToDatabaseFinisher = $this->getAccessibleMock(SaveToDatabaseFinisher::class, ['parseOption'], [], '', false);
        $mockSaveToDatabaseFinisher->method('parseOption')->willReturnMap([
            ['mode', 'update'],
            ['whereClause', ''],
        ]);
        $mockSaveToDatabaseFinisher->_call('throwExceptionOnInconsistentConfiguration');
    }

    #[Test]
    public function prepareDataConvertsArrayValuesToCsv(): void
    {
        $elementsConfiguration = [
            'foo' => [
                'mapOnDatabaseColumn' => 'bar',
            ],
        ];

        $saveToDatabaseFinisher = $this->getAccessibleMock(SaveToDatabaseFinisher::class, ['getFormValues', 'getElementByIdentifier']);
        $saveToDatabaseFinisher->method('getFormValues')->willReturn([
            'foo' => [
                'one',
                'two',
            ],
        ]);
        $saveToDatabaseFinisher->method('getElementByIdentifier')->willReturn($this->createMock(FormElementInterface::class));
        $databaseData = $saveToDatabaseFinisher->_call('prepareData', $elementsConfiguration, []);

        self::assertSame('one,two', $databaseData['bar']);
    }

    #[Test]
    public function executeInternalProcessesSingleTable(): void
    {
        $saveToDatabaseFinisher = $this->getMockBuilder(SaveToDatabaseFinisher::class)
            ->onlyMethods(['process'])
            ->getMock();
        $saveToDatabaseFinisher->setOptions([
            'table' => 'tx_foo',
            'databaseColumnMappings' => [
                'foo' => 1,
            ],
        ]);

        $saveToDatabaseFinisher->expects($this->once())->method('process')->with(0);

        $saveToDatabaseFinisher->execute($this->createMock(FinisherContext::class));
    }

    public static function skipIfValueIsEmptyDataProvider(): array
    {
        return [
            'null value' => [
                'value' => null,
                'expectedEmpty' => true,
            ],
            'empty string' => [
                'value' => '',
                'expectedEmpty' => true,
            ],
            'false value' => [
                'value' => false,
                'expectedEmpty' => false,
            ],
            'space character' => [
                'value' => ' ',
                'expectedEmpty' => false,
            ],
            'zero' => [
                'value' => 0,
                'expectedEmpty' => false,
            ],
            'zero float' => [
                'value' => 0.0,
                'expectedEmpty' => false,
            ],
        ];
    }

    /**
     * @param mixed $value
     */
    #[DataProvider('skipIfValueIsEmptyDataProvider')]
    #[Test]
    public function skipIfValueIsEmptyDetectsEmptyValues($value, bool $expectedEmpty): void
    {
        $elementsConfiguration = [
            'foo' => [
                'mapOnDatabaseColumn' => 'bar',
                'skipIfValueIsEmpty' => true,
            ],
        ];

        $saveToDatabaseFinisher = $this->getAccessibleMock(SaveToDatabaseFinisher::class, ['getFormValues', 'getElementByIdentifier']);
        $saveToDatabaseFinisher->method('getFormValues')->willReturn([
            'foo' => $value,
        ]);
        $saveToDatabaseFinisher->method('getElementByIdentifier')->willReturn($this->createMock(FormElementInterface::class));
        $databaseData = $saveToDatabaseFinisher->_call('prepareData', $elementsConfiguration, []);

        self::assertSame($expectedEmpty, empty($databaseData));
    }

    #[Test]
    public function executeInternalProcessesMultipleTables(): void
    {
        $saveToDatabaseFinisher = $this->getMockBuilder(SaveToDatabaseFinisher::class)->onlyMethods(['process'])->getMock();
        $saveToDatabaseFinisher->setOptions([
            [
                'table' => 'tx_foo',
                'databaseColumnMappings' => [
                    'foo' => 1,
                ],
            ],
            [
                'table' => 'tx_bar',
                'databaseColumnMappings' => [
                    'bar' => 1,
                ],
            ],
        ]);
        $saveToDatabaseFinisher->expects($this->exactly(2))->method('process');
        $saveToDatabaseFinisher->execute($this->createMock(FinisherContext::class));
    }

    #[Test]
    public function prepareDataConvertsDateTimeToUnixTimestamp(): void
    {
        $elementsConfiguration = [
            'date' => [
                'mapOnDatabaseColumn' => 'date',
            ],
        ];

        $saveToDatabaseFinisher = $this->getAccessibleMock(SaveToDatabaseFinisher::class, ['getFormValues', 'getElementByIdentifier']);
        $saveToDatabaseFinisher->method('getFormValues')->willReturn([
            'date' => new \DateTime(),
        ]);
        $saveToDatabaseFinisher->method('getElementByIdentifier')->willReturn($this->createMock(FormElementInterface::class));
        $databaseData = $saveToDatabaseFinisher->_call('prepareData', $elementsConfiguration, []);

        $expected = '#^([0-9]{10})$#';
        self::assertMatchesRegularExpression($expected, $databaseData['date']);
    }

    #[Test]
    public function prepareDataConvertsDateTimeToFormat(): void
    {
        $elementsConfiguration = [
            'date' => [
                'mapOnDatabaseColumn' => 'date',
                'dateFormat' => 'Y.m.d',
            ],
        ];

        $saveToDatabaseFinisher = $this->getAccessibleMock(SaveToDatabaseFinisher::class, ['getFormValues', 'getElementByIdentifier']);
        $saveToDatabaseFinisher->method('getFormValues')->willReturn([
            'date' => new \DateTime('2018-06-12'),
        ]);
        $saveToDatabaseFinisher->method('getElementByIdentifier')->willReturn($this->createMock(FormElementInterface::class));
        $databaseData = $saveToDatabaseFinisher->_call('prepareData', $elementsConfiguration, []);

        self::assertSame('2018.06.12', $databaseData['date']);
    }
}
