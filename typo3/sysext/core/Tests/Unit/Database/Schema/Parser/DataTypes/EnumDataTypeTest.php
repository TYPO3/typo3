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

use TYPO3\CMS\Core\Database\Schema\Parser\AST\DataType\EnumDataType;
use TYPO3\CMS\Core\Tests\Unit\Database\Schema\Parser\AbstractDataTypeBaseTestCase;

/**
 * Tests for parsing ENUM SQL data type
 */
class EnumDataTypeTest extends AbstractDataTypeBaseTestCase
{
    /**
     * Data provider for canParseEnumDataType()
     *
     * @return array
     */
    public function canParseEnumDataTypeProvider(): array
    {
        return [
            'ENUM(value)' => [
                "ENUM('value1')",
                EnumDataType::class,
                ['value1'],
            ],
            'ENUM(value,value)' => [
                "ENUM('value1','value2')",
                EnumDataType::class,
                ['value1', 'value2'],
            ],
            'ENUM(value, value)' => [
                "ENUM('value1', 'value2')",
                EnumDataType::class,
                ['value1', 'value2'],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider canParseEnumDataTypeProvider
     * @param string $columnDefinition
     * @param string $className
     * @param array $values
     */
    public function canParseDataType(string $columnDefinition, string $className, array $values)
    {
        $subject = $this->createSubject($columnDefinition);

        $this->assertInstanceOf($className, $subject->dataType);
        $this->assertSame($values, $subject->dataType->getValues());
    }
}
