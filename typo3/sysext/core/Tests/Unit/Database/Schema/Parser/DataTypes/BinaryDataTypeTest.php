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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\Schema\Exception\StatementException;
use TYPO3\CMS\Core\Database\Schema\Parser\AST\DataType\BinaryDataType;
use TYPO3\CMS\Core\Database\Schema\Parser\AST\DataType\VarBinaryDataType;
use TYPO3\CMS\Core\Database\Schema\Parser\Lexer;
use TYPO3\CMS\Core\Database\Schema\Parser\Parser;
use TYPO3\CMS\Core\Tests\Unit\Database\Schema\Parser\AbstractDataTypeBaseTestCase;

/**
 * Tests for parsing BINARY/VARBINARY SQL data types
 */
final class BinaryDataTypeTest extends AbstractDataTypeBaseTestCase
{
    /**
     * Data provider for canParseBinaryDataType()
     */
    public static function canParseBinaryDataTypeProvider(): array
    {
        return [
            'BINARY without length' => [
                'BINARY',
                BinaryDataType::class,
                0,
            ],
            'BINARY with length' => [
                'BINARY(200)',
                BinaryDataType::class,
                200,
            ],
            'VARBINARY with length' => [
                'VARBINARY(200)',
                VarBinaryDataType::class,
                200,
            ],
        ];
    }

    #[DataProvider('canParseBinaryDataTypeProvider')]
    #[Test]
    public function canParseDataType(string $columnDefinition, string $className, int $length): void
    {
        $subject = $this->createSubject($columnDefinition);
        self::assertInstanceOf($className, $subject->dataType);
        self::assertSame($length, $subject->dataType->getLength());
    }

    #[Test]
    public function lengthIsRequiredForVarBinaryType(): void
    {
        $this->expectException(StatementException::class);
        $this->expectExceptionCode(1471504822);
        $this->expectExceptionMessage('The current data type requires a field length definition');
        (new Parser(new Lexer()))->parse('CREATE TABLE `aTable`(`aField` VARBINARY);');
    }
}
