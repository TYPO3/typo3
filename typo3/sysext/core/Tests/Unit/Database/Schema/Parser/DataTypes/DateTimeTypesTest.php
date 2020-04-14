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

namespace TYPO3\CMS\Core\Tests\Unit\Database\Schema\Parser\DataTypes;

use TYPO3\CMS\Core\Database\Schema\Exception\StatementException;
use TYPO3\CMS\Core\Database\Schema\Parser\AST\DataType\DateDataType;
use TYPO3\CMS\Core\Database\Schema\Parser\AST\DataType\DateTimeDataType;
use TYPO3\CMS\Core\Database\Schema\Parser\AST\DataType\TimeDataType;
use TYPO3\CMS\Core\Database\Schema\Parser\AST\DataType\TimestampDataType;
use TYPO3\CMS\Core\Database\Schema\Parser\AST\DataType\YearDataType;
use TYPO3\CMS\Core\Tests\Unit\Database\Schema\Parser\AbstractDataTypeBaseTestCase;

/**
 * Tests for parsing DATE/TIME related SQL data types
 */
class DateTimeTypesTest extends AbstractDataTypeBaseTestCase
{
    /**
     * Data provider for canParseDateTimeType()
     *
     * @return array
     */
    public function canParseDateTimeTypeProvider(): array
    {
        return [
            'DATE' => [
                'DATE',
                DateDataType::class,
                null,
            ],
            'YEAR' => [
                'YEAR',
                YearDataType::class,
                null,
            ],
            'TIME' => [
                'TIME',
                TimeDataType::class,
                0,
            ],
            'TIME with fractional second part' => [
                'TIME(3)',
                TimeDataType::class,
                3,
            ],
            'TIMESTAMP' => [
                'TIMESTAMP',
                TimestampDataType::class,
                0,
            ],
            'TIMESTAMP with fractional second part' => [
                'TIMESTAMP(3)',
                TimestampDataType::class,
                3,
            ],
            'DATETIME' => [
                'DATETIME',
                DateTimeDataType::class,
                0,
            ],
            'DATETIME with fractional second part' => [
                'DATETIME(3)',
                DateTimeDataType::class,
                3,
            ],
        ];
    }

    /**
     * @test
     * @dataProvider canParseDateTimeTypeProvider
     * @param string $columnDefinition
     * @param string $className
     * @param int $length
     */
    public function canParseDataType(string $columnDefinition, string $className, int $length = null)
    {
        $subject = $this->createSubject($columnDefinition);

        self::assertInstanceOf($className, $subject->dataType);

        // DATE & YEAR don't support fractional second parts
        if ($length !== null) {
            self::assertSame($length, $subject->dataType->getLength());
        }
    }

    /**
     * @test
     */
    public function parseDateTimeTypeWithInvalidLowerBound()
    {
        $this->expectException(StatementException::class);
        $this->expectDeprecationMessageMatches(
            '@Error: the fractional seconds part for TIME, DATETIME or TIMESTAMP columns must >= 0@'
        );
        $this->createSubject('TIME(-1)');
    }

    /**
     * @test
     */
    public function parseDateTimeTypeWithInvalidUpperBound()
    {
        $this->expectException(StatementException::class);
        $this->expectDeprecationMessageMatches(
            '@Error: the fractional seconds part for TIME, DATETIME or TIMESTAMP columns must <= 6@'
        );
        $this->createSubject('TIME(7)');
    }
}
