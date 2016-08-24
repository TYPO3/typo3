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

use TYPO3\CMS\Core\Database\Schema\Parser\AST\DataType\DoubleDataType;
use TYPO3\CMS\Core\Database\Schema\Parser\AST\DataType\FloatDataType;
use TYPO3\CMS\Core\Database\Schema\Parser\AST\DataType\RealDataType;
use TYPO3\CMS\Core\Tests\Unit\Database\Schema\Parser\AbstractDataTypeBaseTestCase;

/**
 * Tests for parsing FLOAT/REAL/DOUBLE SQL data types
 */
class FloatingPointTypesTest extends AbstractDataTypeBaseTestCase
{
    /**
     * Data provider for canParseFloatingPointTypes()
     *
     * @return array
     */
    public function canParseFloatingPointTypesProvider(): array
    {
        return [
            'FLOAT without precision' => [
                'FLOAT',
                FloatDataType::class,
                -1,
                -1,
            ],
            'FLOAT with precision' => [
                'FLOAT(44)',
                FloatDataType::class,
                44,
                -1,
            ],
            'FLOAT with precision and decimals' => [
                'FLOAT(44,5)',
                FloatDataType::class,
                44,
                5,
            ],
            'REAL without precision' => [
                'REAL',
                RealDataType::class,
                -1,
                -1,
            ],
            'REAL with precision' => [
                'REAL(44)',
                RealDataType::class,
                44,
                -1,
            ],
            'REAL with precision and decimals' => [
                'REAL(44,5)',
                RealDataType::class,
                44,
                5,
            ],
            'DOUBLE without precision' => [
                'DOUBLE',
                DoubleDataType::class,
                -1,
                -1,
            ],
            'DOUBLE with precision' => [
                'DOUBLE(44)',
                DoubleDataType::class,
                44,
                -1,
            ],
            'DOUBLE with precision and decimals' => [
                'DOUBLE(44,5)',
                DoubleDataType::class,
                44,
                5,
            ],
        ];
    }

    /**
     * @test
     * @dataProvider canParseFloatingPointTypesProvider
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
