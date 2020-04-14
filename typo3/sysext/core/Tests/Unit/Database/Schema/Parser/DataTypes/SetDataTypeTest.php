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

use TYPO3\CMS\Core\Database\Schema\Parser\AST\DataType\SetDataType;
use TYPO3\CMS\Core\Tests\Unit\Database\Schema\Parser\AbstractDataTypeBaseTestCase;

/**
 * Tests for parsing SET SQL data type
 */
class SetDataTypeTest extends AbstractDataTypeBaseTestCase
{
    /**
     * Data provider for canParseSetDataType()
     *
     * @return array
     */
    public function canParseSetDataTypeProvider(): array
    {
        return [
            'SET(value)' => [
                "SET('value1')",
                SetDataType::class,
                ['value1'],
            ],
            'SET(value,value)' => [
                "SET('value1','value2')",
                SetDataType::class,
                ['value1', 'value2'],
            ],
            'SET(value, value)' => [
                "SET('value1', 'value2')",
                SetDataType::class,
                ['value1', 'value2'],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider canParseSetDataTypeProvider
     * @param string $columnDefinition
     * @param string $className
     * @param array $values
     */
    public function canParseDataType(string $columnDefinition, string $className, array $values)
    {
        $subject = $this->createSubject($columnDefinition);

        self::assertInstanceOf($className, $subject->dataType);
        self::assertSame($values, $subject->dataType->getValues());
    }
}
