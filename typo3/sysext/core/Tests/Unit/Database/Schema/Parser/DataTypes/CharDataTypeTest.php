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
use TYPO3\CMS\Core\Database\Schema\Parser\AST\DataType\CharDataType;
use TYPO3\CMS\Core\Database\Schema\Parser\AST\DataType\VarCharDataType;
use TYPO3\CMS\Core\Database\Schema\Parser\Lexer;
use TYPO3\CMS\Core\Database\Schema\Parser\Parser;
use TYPO3\CMS\Core\Tests\Unit\Database\Schema\Parser\AbstractDataTypeBaseTestCase;

/**
 * Tests for parsing CHAR/VARCHAR SQL data types
 */
final class CharDataTypeTest extends AbstractDataTypeBaseTestCase
{
    /**
     * Data provider for canParseBinaryDataType()
     */
    public static function canParseBinaryDataTypeProvider(): array
    {
        return [
            'CHAR without length' => [
                'columnDefinition' => 'CHAR',
                'className' => CharDataType::class,
                'length' => 0,
                'isFixed' => true,
            ],
            'CHAR with length' => [
                'columnDefinition' => 'CHAR(200)',
                'className' => CharDataType::class,
                'length' => 200,
                'isFixed' => true,
            ],
            'VARCHAR with length' => [
                'columnDefinition' => 'VARCHAR(200)',
                'className' => VarCharDataType::class,
                'length' => 200,
                'isFixed' => false,
            ],
        ];
    }

    #[DataProvider('canParseBinaryDataTypeProvider')]
    #[Test]
    public function canParseDataType(string $columnDefinition, string $className, int $length, bool $isFixed): void
    {
        $subject = $this->createSubject($columnDefinition);

        self::assertInstanceOf($className, $subject->dataType);
        self::assertSame($length, $subject->dataType->getLength());
        self::assertSame($isFixed, $subject->dataType->isFixed());
    }

    #[Test]
    public function lengthIsRequiredForVarCharType(): void
    {
        $this->expectException(StatementException::class);
        $this->expectExceptionCode(1471504822);
        $this->expectExceptionMessage('The current data type requires a field length definition');
        (new Parser(new Lexer()))->parse('CREATE TABLE `aTable`(`aField` VARCHAR);');
    }
}
