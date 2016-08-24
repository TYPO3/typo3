<?php
declare(strict_types=1);

namespace TYPO3\CMS\Core\Tests\Unit\Database\Schema\Parser\DataTypes;

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

use TYPO3\CMS\Core\Database\Schema\Parser\AST\DataType\BigIntDataType;
use TYPO3\CMS\Core\Database\Schema\Parser\AST\DataType\IntegerDataType;
use TYPO3\CMS\Core\Database\Schema\Parser\AST\DataType\MediumIntDataType;
use TYPO3\CMS\Core\Database\Schema\Parser\AST\DataType\SmallIntDataType;
use TYPO3\CMS\Core\Database\Schema\Parser\AST\DataType\TinyIntDataType;
use TYPO3\CMS\Core\Tests\Unit\Database\Schema\Parser\AbstractDataTypeBaseTestCase;

/**
 * Tests for parsing INTEGER SQL data types
 */
class IntegerTypesTest extends AbstractDataTypeBaseTestCase
{
    /**
     * Data provider for canParseIntegerDataType()
     *
     * @return array
     */
    public function canParseIntegerDataTypeProvider(): array
    {
        return [
            'TINYINT without length' => [
                'TINYINT',
                TinyIntDataType::class,
                0,
            ],
            'SMALLINT without length' => [
                'SMALLINT',
                SmallIntDataType::class,
                0,
            ],
            'MEDIUMINT without length' => [
                'MEDIUMINT',
                MediumIntDataType::class,
                0,
            ],
            'INT without length' => [
                'INT',
                IntegerDataType::class,
                0,
            ],
            'INTEGER without length' => [
                'INTEGER',
                IntegerDataType::class,
                0,
            ],
            'BIGINT without length' => [
                'BIGINT',
                BigIntDataType::class,
                0,
            ],
            // MySQL supports an extension for optionally specifying the display width of integer data types
            // in parentheses following the base keyword for the type. For example, INT(4) specifies an INT
            // with a display width of four digits.
            // The display width does not constrain the range of values that can be stored in the column.
            'TINYINT with length' => [
                'TINYINT(4)',
                TinyIntDataType::class,
                4,
            ],
            'SMALLINT with length' => [
                'SMALLINT(6)',
                SmallIntDataType::class,
                6,
            ],
            'MEDIUMINT with length' => [
                'MEDIUMINT(8)',
                MediumIntDataType::class,
                8,
            ],
            'INT with length' => [
                'INT(11)',
                IntegerDataType::class,
                11,
            ],
            'INTEGER with length' => [
                'INTEGER(11)',
                IntegerDataType::class,
                11,
            ],
            'BIGINT with length' => [
                'BIGINT(20)',
                BigIntDataType::class,
                20,
            ],
        ];
    }

    /**
     * @test
     * @dataProvider canParseIntegerDataTypeProvider
     * @param string $columnDefinition
     * @param string $className
     * @param int $length
     */
    public function canParseDataType(string $columnDefinition, string $className, int $length)
    {
        $subject = $this->createSubject($columnDefinition);

        $this->assertInstanceOf($className, $subject->dataType);
        $this->assertSame($length, $subject->dataType->getLength());
    }
}
