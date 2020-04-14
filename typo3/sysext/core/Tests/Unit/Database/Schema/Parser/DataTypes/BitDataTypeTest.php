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

use TYPO3\CMS\Core\Database\Schema\Parser\AST\DataType\BitDataType;
use TYPO3\CMS\Core\Tests\Unit\Database\Schema\Parser\AbstractDataTypeBaseTestCase;

/**
 * Tests for parsing BIT SQL data type
 */
class BitDataTypeTest extends AbstractDataTypeBaseTestCase
{
    /**
     * Data provider for canParseBitDataType()
     *
     * @return array
     */
    public function canParseBitDataTypeProvider(): array
    {
        return [
            'BIT without length' => [
                'BIT',
                BitDataType::class,
                0,
            ],
            'BIT with length' => [
                'BIT(8)',
                BitDataType::class,
                8,
            ],
        ];
    }

    /**
     * @test
     * @dataProvider canParseBitDataTypeProvider
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
}
