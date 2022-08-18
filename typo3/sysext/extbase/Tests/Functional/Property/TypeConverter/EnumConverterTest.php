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

namespace TYPO3\CMS\Extbase\Tests\Functional\Property\TypeConverter;

use Generator;
use TYPO3\CMS\Extbase\Property\PropertyMapper;
use TYPO3\CMS\Extbase\Tests\Fixture\IntegerBackedEnum;
use TYPO3\CMS\Extbase\Tests\Fixture\StringBackedEnum;
use TYPO3\CMS\Extbase\Tests\Fixture\UnbackedEnum;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class EnumConverterTest extends FunctionalTestCase
{
    protected bool $initializeDatabase = false;

    /**
     * @test
     * @dataProvider convertEnumDataProvider
     */
    public function convertEnum(string $enumClass, float|int|string $input, ?object $expected): void
    {
        $propertyMapper = $this->get(PropertyMapper::class);
        $enumItem = $propertyMapper->convert($input, $enumClass);

        self::assertSame($expected, $enumItem);
    }

    public function convertEnumDataProvider(): Generator
    {
        yield 'convert by name 1' => [
            UnbackedEnum::class,
            'FirstCase',
            UnbackedEnum::FirstCase,
        ];
        yield 'convert by name 2' => [
            UnbackedEnum::class,
            'SecondCase',
            UnbackedEnum::SecondCase,
        ];
        yield 'convert by name 3' => [
            UnbackedEnum::class,
            'ThirdCase',
            UnbackedEnum::ThirdCase,
        ];
        yield 'convert by name 4' => [
            UnbackedEnum::class,
            'FourthCase',
            null,
        ];
        yield 'convert by value string 1' => [
            StringBackedEnum::class,
            'first',
            StringBackedEnum::FirstCase,
        ];
        yield 'convert by value string 2' => [
            StringBackedEnum::class,
            'second',
            StringBackedEnum::SecondCase,
        ];
        yield 'convert by value string 3' => [
            StringBackedEnum::class,
            'third',
            StringBackedEnum::ThirdCase,
        ];
        yield 'convert by value string 4' => [
            StringBackedEnum::class,
            'fourth',
            null,
        ];
        yield 'convert by value string, value comes first 1' => [
            StringBackedEnum::class,
            'A',
            StringBackedEnum::B,
        ];
        yield 'convert by value string, value comes first 2' => [
            StringBackedEnum::class,
            'B',
            StringBackedEnum::A,
        ];
        yield 'convert by value integer 1' => [
            IntegerBackedEnum::class,
            1,
            IntegerBackedEnum::FirstCase,
        ];
        yield 'convert by value integer 2' => [
            IntegerBackedEnum::class,
            2,
            IntegerBackedEnum::SecondCase,
        ];
        yield 'convert by value integer 3' => [
            IntegerBackedEnum::class,
            3,
            IntegerBackedEnum::ThirdCase,
        ];
        yield 'convert by value integer 4' => [
            IntegerBackedEnum::class,
            4,
            null,
        ];
        yield 'convert by value float 1' => [
            IntegerBackedEnum::class,
            1.0,
            IntegerBackedEnum::FirstCase,
        ];
        yield 'convert by value float 2' => [
            IntegerBackedEnum::class,
            2.0,
            IntegerBackedEnum::SecondCase,
        ];
        yield 'convert by value float 3' => [
            IntegerBackedEnum::class,
            3.0,
            IntegerBackedEnum::ThirdCase,
        ];
        yield 'convert by value float 4' => [
            IntegerBackedEnum::class,
            4.0,
            null,
        ];
    }
}
