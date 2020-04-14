<?php

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

/**
 * Test case
 */
class ArrayConverterTest extends UnitTestCase
{
    /**
     * @var \TYPO3\CMS\Extbase\Property\TypeConverter\ArrayConverter
     */
    protected $converter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->converter = new ArrayConverter();
    }

    /**
     * @test
     */
    public function checkMetadata(): void
    {
        self::assertEquals(['array', 'string'], $this->converter->getSupportedSourceTypes(), 'Source types do not match');
        self::assertEquals('array', $this->converter->getSupportedTargetType(), 'Target type does not match');
        self::assertEquals(10, $this->converter->getPriority(), 'Priority does not match');
    }

    /**
     * @test
     */
    public function convertFromDoesNotModifyTheSourceArray(): void
    {
        $sourceArray = ['Foo' => 'Bar', 'Baz'];
        self::assertEquals($sourceArray, $this->converter->convertFrom($sourceArray, 'array'));
    }

    /**
     * @return array
     */
    public function stringToArrayDataProvider(): array
    {
        return [
            'Empty string to empty array' => ['', []],
        ];
    }

    /**
     * @test
     * @dataProvider stringToArrayDataProvider
     *
     * @param string $source
     * @param array $expectedResult
     */
    public function canConvertFromEmptyString($source, $expectedResult): void
    {
        self::assertEquals($expectedResult, $this->converter->convertFrom($source, 'array'));
    }

    /**
     * @return array
     */
    public function stringToArrayWithConfigurationDataProvider(): array
    {
        return [
            'Sentence with two spaces string converts to array, delimiter is space' => ['foo  bar', $this->getStringConf(' ', true), ['foo', 'bar']],
            'String List with comma converts to array, delimiter: ,' => ['foo,bar', $this->getStringConf(','), ['foo', 'bar']],
            'String List with comma converts to array; no empty: true, delimiter: ,' => ['foo,,bar', $this->getStringConf(',', true), ['foo', 'bar']],
            'Long sentence splits into two parts; delimiter: space' => ['Foo bar is a long sentence', $this->getStringConf(' ', false, 2), ['Foo', 'bar is a long sentence']],
            'All available configuration options' => ['This. Is. Awesome... Right?', $this->getStringConf('.', true, 4), ['This', 'Is', 'Awesome', 'Right?']]
        ];
    }

    private function getStringConf(?string $delimiter = null, ?bool $removeEmptyValues = null, ?int $limit = null): PropertyMappingConfigurationInterface
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
     *
     * @param string $source
     * @param PropertyMappingConfigurationInterface $configuration
     * @param array $expectedResult
     */
    public function canConvertWithConfigurationFromString($source, PropertyMappingConfigurationInterface $configuration, $expectedResult): void
    {
        self::assertEquals($expectedResult, $this->converter->convertFrom($source, 'array', [], $configuration));
    }

    /**
     * @return array
     */
    public function canConvertFromDataProvider(): array
    {
        return [
            'Can convert empty string' => ['', true],
            'Can convert not empty string' => ['foo', true],
            'Can not convert number' => [12, false],
            'Can convert array' => [['foo'], true],
        ];
    }

    /**
     * @test
     * @dataProvider canConvertFromDataProvider
     *
     * @param mixed $source
     * @param bool $expectedResult
     */
    public function canConvertFromReturnsCorrectBooleans($source, $expectedResult): void
    {
        self::assertSame($expectedResult, $this->converter->canConvertFrom($source, 'array'));
    }

    /**
     * @test
     */
    public function throwsTypeConverterExceptionIfDelimiterIsNotGiven(): void
    {
        $this->expectException(TypeConverterException::class);
        $this->expectExceptionCode(1582877555);
        $this->converter->convertFrom('foo', 'array', [], new PropertyMappingConfiguration());
    }

    /**
     * @test
     */
    public function returnsSourceUnchangedIfNonEmptyValueWithNoConfigurationIsGiven(): void
    {
        $result = $this->converter->convertFrom('foo', 'array', [], null);
        self::assertSame('foo', $result);
    }
}
