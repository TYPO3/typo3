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
use TYPO3\CMS\Core\Database\Schema\Parser\AST\DataType\CharDataType;
use TYPO3\CMS\Core\Database\Schema\Parser\AST\DataType\VarCharDataType;
use TYPO3\CMS\Core\Database\Schema\Parser\Parser;
use TYPO3\CMS\Core\Tests\Unit\Database\Schema\Parser\AbstractDataTypeBaseTestCase;

/**
 * Tests for parsing CHAR/VARCHAR SQL data types
 */
class CharDataTypeTest extends AbstractDataTypeBaseTestCase
{
    /**
     * Data provider for canParseBinaryDataType()
     *
     * @return array
     */
    public function canParseBinaryDataTypeProvider(): array
    {
        return [
            'CHAR without length' => [
                'CHAR',
                CharDataType::class,
                0,
            ],
            'CHAR with length' => [
                'CHAR(200)',
                CharDataType::class,
                200,
            ],
            'VARCHAR with length' => [
                'VARCHAR(200)',
                VarCharDataType::class,
                200,
            ],
        ];
    }

    /**
     * @test
     * @dataProvider canParseBinaryDataTypeProvider
     * @param string $columnDefinition
     * @param string $className
     * @param int $length
     */
    public function canParseDataType(string $columnDefinition, string $className, int $length)
    {
        $subject = $this->createSubject($columnDefinition);

        self::assertInstanceOf($className, $subject->dataType);
        self::assertSame($length, $subject->dataType->getLength());
    }

    /**
     * @test
     */
    public function lengthIsRequiredForVarCharType()
    {
        $this->expectException(StatementException::class);
        $this->expectExceptionCode(1471504822);
        $this->expectExceptionMessage('The current data type requires a field length definition');
        (new Parser('CREATE TABLE `aTable`(`aField` VARCHAR);'))->parse();
    }
}
