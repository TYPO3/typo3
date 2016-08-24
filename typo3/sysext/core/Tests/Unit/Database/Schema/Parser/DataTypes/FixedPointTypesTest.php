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

use TYPO3\CMS\Core\Database\Schema\Parser\AST\DataType\DecimalDataType;
use TYPO3\CMS\Core\Database\Schema\Parser\AST\DataType\NumericDataType;
use TYPO3\CMS\Core\Tests\Unit\Database\Schema\Parser\AbstractDataTypeBaseTestCase;

/**
 * Tests for parsing DECIMAL/NUMERIC SQL data types
 */
class FixedPointTypesTest extends AbstractDataTypeBaseTestCase
{
    /**
     * Data provider for canParseFixedPointTypes()
     *
     * @return array
     */
    public function canParseFixedPointTypesProvider(): array
    {
        return [
            'DECIMAL without precision and scale' => [
                'DECIMAL',
                DecimalDataType::class,
                -1,
                -1,
            ],
            'DECIMAL with precision' => [
                'DECIMAL(5)',
                DecimalDataType::class,
                5,
                -1,
            ],
            'DECIMAL with precision and scale' => [
                'DECIMAL(5,2)',
                DecimalDataType::class,
                5,
                2,
            ],
            'NUMERIC without length' => [
                'NUMERIC',
                NumericDataType::class,
                -1,
                -1,
            ],
            'NUMERIC with length' => [
                'NUMERIC(5)',
                NumericDataType::class,
                5,
                -1,
            ],
            'NUMERIC with length and precision' => [
                'NUMERIC(5,2)',
                NumericDataType::class,
                5,
                2,
            ],
        ];
    }

    /**
     * @test
     * @dataProvider canParseFixedPointTypesProvider
     * @param string $columnDefinition
     * @param string $className
     * @param int $precision
     * @param int $scale
     */
    public function canParseDataType(
        string $columnDefinition,
        string $className,
        int $precision = null,
        int $scale = null
    ) {
        $subject = $this->createSubject($columnDefinition);

        $this->assertInstanceOf($className, $subject->dataType);
        $this->assertSame($precision, $subject->dataType->getPrecision());
        $this->assertSame($scale, $subject->dataType->getScale());
    }
}
