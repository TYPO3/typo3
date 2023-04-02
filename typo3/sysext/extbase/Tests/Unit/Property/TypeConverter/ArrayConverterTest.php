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

namespace TYPO3\CMS\Extbase\Tests\Unit\Property\TypeConverter;

use TYPO3\CMS\Extbase\Property\Exception\TypeConverterException;
use TYPO3\CMS\Extbase\Property\PropertyMappingConfiguration;
use TYPO3\CMS\Extbase\Property\PropertyMappingConfigurationInterface;
use TYPO3\CMS\Extbase\Property\TypeConverter\ArrayConverter;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class ArrayConverterTest extends UnitTestCase
{
    /**
     * @test
     */
    public function convertFromDoesNotModifyTheSourceArray(): void
    {
        $sourceArray = ['Foo' => 'Bar', 'Baz'];
        self::assertEquals($sourceArray, (new ArrayConverter())->convertFrom($sourceArray, 'array'));
    }

    /**
     * @test
     */
    public function canConvertFromEmptyStringToArray(): void
    {
        self::assertEquals([], (new ArrayConverter())->convertFrom('', 'array'));
    }

    public static function stringToArrayWithConfigurationDataProvider(): array
    {
        return [
            'Sentence with two spaces string converts to array, delimiter is space' => [
                'foo  bar',
                self::getStringConf(' ', true),
                ['foo', 'bar'],
            ],
            'String List with comma converts to array, delimiter: ,' => [
                'foo,bar',
                self::getStringConf(','),
                ['foo', 'bar'],
            ],
            'String List with comma converts to array; no empty: true, delimiter: ,' => [
                'foo,,bar',
                self::getStringConf(',', true),
                ['foo', 'bar'],
            ],
            'Long sentence splits into two parts; delimiter: space' => [
                'Foo bar is a long sentence',
                self::getStringConf(' ', false, 2),
                ['Foo', 'bar is a long sentence'],
            ],
            'All available configuration options' => [
                'This. Is. Awesome... Right?',
                self::getStringConf('.', true, 4),
                ['This', 'Is', 'Awesome', 'Right?'],
            ],
        ];
    }

    private static function getStringConf(string $delimiter, ?bool $removeEmptyValues = null, ?int $limit = null): PropertyMappingConfigurationInterface
    {
        $configuration = new PropertyMappingConfiguration();
        if ($delimiter) {
            $configuration->setTypeConverterOption(ArrayConverter::class, 'delimiter', $delimiter);
        }
        if ($removeEmptyValues) {
            $configuration->setTypeConverterOption(ArrayConverter::class, 'removeEmptyValues', $removeEmptyValues);
        }
        if ($limit) {
            $configuration->setTypeConverterOption(ArrayConverter::class, 'limit', $limit);
        }
        $configuration->setTypeConverterOptions(ArrayConverter::class, [
            'delimiter' => $delimiter,
            'removeEmptyValues' => $removeEmptyValues,
            'limit' => $limit,
        ]);
        return $configuration;
    }

    /**
     * @test
     * @dataProvider stringToArrayWithConfigurationDataProvider
     */
    public function canConvertWithConfigurationFromString(string $source, PropertyMappingConfigurationInterface $configuration, array $expectedResult): void
    {
        self::assertEquals($expectedResult, (new ArrayConverter())->convertFrom($source, 'array', [], $configuration));
    }

    /**
     * @test
     */
    public function throwsTypeConverterExceptionIfDelimiterIsNotGiven(): void
    {
        $this->expectException(TypeConverterException::class);
        $this->expectExceptionCode(1582877555);
        (new ArrayConverter())->convertFrom('foo', 'array', [], new PropertyMappingConfiguration());
    }

    /**
     * @test
     */
    public function returnsSourceUnchangedIfNonEmptyValueWithNoConfigurationIsGiven(): void
    {
        self::assertSame('foo', (new ArrayConverter())->convertFrom('foo', 'array', []));
    }
}
