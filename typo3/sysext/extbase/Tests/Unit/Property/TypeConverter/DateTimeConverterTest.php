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

use TYPO3\CMS\Extbase\Error\Error;
use TYPO3\CMS\Extbase\Property\Exception\TypeConverterException;
use TYPO3\CMS\Extbase\Property\PropertyMappingConfiguration;
use TYPO3\CMS\Extbase\Property\PropertyMappingConfigurationInterface;
use TYPO3\CMS\Extbase\Property\TypeConverter\DateTimeConverter;
use TYPO3\CMS\Extbase\Tests\Unit\Property\TypeConverter\Fixtures\DateTimeSubFixture;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class DateTimeConverterTest extends UnitTestCase
{
    protected DateTimeConverter $converter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->converter = new DateTimeConverter();
    }

    /**
     * @test
     */
    public function checkMetadata(): void
    {
        self::assertEquals(['string', 'integer', 'array'], $this->converter->getSupportedSourceTypes(), 'Source types do not match');
        self::assertEquals('DateTime', $this->converter->getSupportedTargetType(), 'Target type does not match');
        self::assertEquals(10, $this->converter->getPriority(), 'Priority does not match');
    }

    /** String to DateTime testcases  **/

    /**
     * @test
     */
    public function canConvertFromReturnsFalseIfTargetTypeIsNotDateTime(): void
    {
        self::assertFalse($this->converter->canConvertFrom('Foo', 'SomeOtherType'));
    }

    /**
     * @test
     */
    public function canConvertFromReturnsTrueIfSourceTypeIsAString(): void
    {
        self::assertTrue($this->converter->canConvertFrom('Foo', 'DateTime'));
    }

    /**
     * @test
     */
    public function canConvertFromReturnsTrueIfSourceTypeIsAnEmptyString(): void
    {
        self::assertTrue($this->converter->canConvertFrom('', 'DateTime'));
    }

    /**
     * @test
     */
    public function convertFromReturnsErrorIfGivenStringCantBeConverted(): void
    {
        $error = $this->converter->convertFrom('1980-12-13', 'DateTime');
        self::assertInstanceOf(Error::class, $error);
    }

    /**
     * @test
     */
    public function convertFromProperlyConvertsStringWithDefaultDateFormat(): void
    {
        $expectedResult = '1980-12-13T20:15:07+01:23';
        $date = $this->converter->convertFrom($expectedResult, 'DateTime');
        $actualResult = $date->format('Y-m-d\\TH:i:sP');
        self::assertSame($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function convertFromUsesDefaultDateFormatIfItIsNotConfigured(): void
    {
        $expectedResult = '1980-12-13T20:15:07+01:23';
        $mockMappingConfiguration = $this->createMock(PropertyMappingConfigurationInterface::class);
        $mockMappingConfiguration
                ->expects(self::atLeastOnce())
                ->method('getConfigurationValue')
                ->with(DateTimeConverter::class, DateTimeConverter::CONFIGURATION_DATE_FORMAT)
                ->willReturn(null);

        $date = $this->converter->convertFrom($expectedResult, 'DateTime', [], $mockMappingConfiguration);
        $actualResult = $date->format(DateTimeConverter::DEFAULT_DATE_FORMAT);
        self::assertSame($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function convertFromEmptyStringReturnsNull(): void
    {
        $date = $this->converter->convertFrom('', 'DateTime', [], null);
        self::assertNull($date);
    }

    /**
     * @return array
     * @see convertFromStringTests()
     */
    public function convertFromStringDataProvider(): array
    {
        return [
            ['1308174051', '', false],
            ['13-12-1980', 'd.m.Y', false],
            ['1308174051', 'Y-m-d', false],
            ['12:13', 'H:i', true],
            ['13.12.1980', 'd.m.Y', true],
            ['2005-08-15T15:52:01+00:00', null, true],
            ['2005-08-15T15:52:01+00:00', \DateTimeInterface::ATOM, true],
            ['1308174051', 'U', true],
        ];
    }

    /**
     * @param string $source the string to be converted
     * @param string $dateFormat the expected date format
     * @param bool $isValid TRUE if the conversion is expected to be successful, otherwise FALSE
     * @test
     * @dataProvider convertFromStringDataProvider
     */
    public function convertFromStringTests($source, $dateFormat, $isValid): void
    {
        if ($dateFormat !== null) {
            $mockMappingConfiguration = $this->createMock(PropertyMappingConfigurationInterface::class);
            $mockMappingConfiguration
                    ->expects(self::atLeastOnce())
                    ->method('getConfigurationValue')
                    ->with(DateTimeConverter::class, DateTimeConverter::CONFIGURATION_DATE_FORMAT)
                    ->willReturn($dateFormat);
        } else {
            $mockMappingConfiguration = null;
        }
        $date = $this->converter->convertFrom($source, 'DateTime', [], $mockMappingConfiguration);
        if ($isValid !== true) {
            self::assertInstanceOf(Error::class, $date);
            return;
        }
        self::assertInstanceOf('DateTime', $date);

        if ($dateFormat === null) {
            $dateFormat = DateTimeConverter::DEFAULT_DATE_FORMAT;
        }
        self::assertSame($source, $date->format($dateFormat));
    }

    /**
     * @return array
     * @see convertFromIntegerOrDigitStringWithoutConfigurationTests()
     * @see convertFromIntegerOrDigitStringInArrayWithoutConfigurationTests()
     */
    public function convertFromIntegerOrDigitStringsWithoutConfigurationDataProvider(): array
    {
        return [
            ['1308174051'],
            [1308174051],
        ];
    }

    /**
     * @test
     * @dataProvider convertFromIntegerOrDigitStringsWithoutConfigurationDataProvider
     */
    public function convertFromIntegerOrDigitStringWithoutConfigurationTests(string|int $source): void
    {
        $date = $this->converter->convertFrom($source, 'DateTime', [], null);
        self::assertInstanceOf('DateTime', $date);
        self::assertSame((string)$source, $date->format('U'));
    }

    /** Array to DateTime testcases  **/

    /**
     * @test
     * @dataProvider convertFromIntegerOrDigitStringsWithoutConfigurationDataProvider
     */
    public function convertFromIntegerOrDigitStringInArrayWithoutConfigurationTests(string|int $source): void
    {
        $date = $this->converter->convertFrom(['date' => $source], 'DateTime', [], null);
        self::assertInstanceOf('DateTime', $date);
        self::assertSame((string)$source, $date->format('U'));
    }

    /**
     * @test
     */
    public function canConvertFromReturnsTrueIfSourceTypeIsAnArray(): void
    {
        self::assertTrue($this->converter->canConvertFrom([], 'DateTime'));
    }

    /**
     * @test
     */
    public function convertFromReturnsErrorIfGivenArrayCantBeConverted(): void
    {
        $error = $this->converter->convertFrom(['date' => '1980-12-13'], 'DateTime');
        self::assertInstanceOf(Error::class, $error);
    }

    /**
     * @test
     */
    public function convertFromThrowsExceptionIfGivenArrayDoesNotSpecifyTheDate(): void
    {
        $this->expectException(TypeConverterException::class);
        $this->expectExceptionCode(1308003914);
        $this->converter->convertFrom(['hour' => '12', 'minute' => '30'], 'DateTime');
    }

    /**
     * @test
     */
    public function convertFromProperlyConvertsArrayWithDefaultDateFormat(): void
    {
        $expectedResult = '1980-12-13T20:15:07+01:23';
        $date = $this->converter->convertFrom(['date' => $expectedResult], 'DateTime');
        $actualResult = $date->format('Y-m-d\\TH:i:sP');
        self::assertSame($expectedResult, $actualResult);
    }

    /**
     * @return array
     * @see convertFromThrowsExceptionIfDatePartKeysHaveInvalidValuesSpecified
     */
    public function invalidDatePartKeyValuesDataProvider(): array
    {
        return [
            [['day' => '13.0', 'month' => '10', 'year' => '2010']],
            [['day' => '13', 'month' => '10.0', 'year' => '2010']],
            [['day' => '13', 'month' => '10', 'year' => '2010.0']],
            [['day' => '-13', 'month' => '10', 'year' => '2010']],
            [['day' => '13', 'month' => '-10', 'year' => '2010']],
            [['day' => '13', 'month' => '10', 'year' => '-2010']],
        ];
    }

    /**
     * @test
     * @dataProvider invalidDatePartKeyValuesDataProvider
     */
    public function convertFromThrowsExceptionIfDatePartKeysHaveInvalidValuesSpecified($source): void
    {
        $this->expectException(TypeConverterException::class);
        $this->expectExceptionCode(1308003914);
        $this->converter->convertFrom($source, 'DateTime');
    }

    /**
     * @test
     */
    public function convertFromProperlyConvertsArrayWithDateAsArray(): void
    {
        $source = ['day' => '13', 'month' => '10', 'year' => '2010'];
        $mappingConfiguration = new PropertyMappingConfiguration();
        $mappingConfiguration->setTypeConverterOption(
            DateTimeConverter::class,
            DateTimeConverter::CONFIGURATION_DATE_FORMAT,
            'Y-m-d'
        );

        $date = $this->converter->convertFrom($source, 'DateTime', [], $mappingConfiguration);
        $actualResult = $date->format('Y-m-d');
        self::assertSame('2010-10-13', $actualResult);
    }

    /**
     * @test
     */
    public function convertFromAllowsToOverrideTheTime(): void
    {
        $source = [
            'date' => '2011-06-16',
            'dateFormat' => 'Y-m-d',
            'hour' => '12',
            'minute' => '30',
            'second' => '59',
        ];
        $date = $this->converter->convertFrom($source, 'DateTime');
        self::assertSame('2011-06-16', $date->format('Y-m-d'));
        self::assertSame('12', $date->format('H'));
        self::assertSame('30', $date->format('i'));
        self::assertSame('59', $date->format('s'));
    }

    /**
     * @test
     */
    public function convertFromAllowsToOverrideTheTimezone(): void
    {
        $source = [
            'date' => '2011-06-16 12:30:59',
            'dateFormat' => 'Y-m-d H:i:s',
            'timezone' => 'Atlantic/Reykjavik',
        ];
        $date = $this->converter->convertFrom($source, 'DateTime');
        self::assertSame('2011-06-16', $date->format('Y-m-d'));
        self::assertSame('12', $date->format('H'));
        self::assertSame('30', $date->format('i'));
        self::assertSame('59', $date->format('s'));
        self::assertSame('Atlantic/Reykjavik', $date->getTimezone()->getName());
    }

    /**
     * @test
     */
    public function convertFromThrowsExceptionIfSpecifiedTimezoneIsInvalid(): void
    {
        $this->expectException(TypeConverterException::class);
        $this->expectExceptionCode(1308240974);
        $source = [
            'date' => '2011-06-16',
            'dateFormat' => 'Y-m-d',
            'timezone' => 'Invalid/Timezone',
        ];
        $this->converter->convertFrom($source, 'DateTime');
    }

    /**
     * @test
     */
    public function convertFromArrayThrowsExceptionForEmptyArray(): void
    {
        $this->expectException(TypeConverterException::class);
        $this->expectExceptionCode(1308003914);
        $this->converter->convertFrom([], 'DateTime', [], null);
    }

    /**
     * @test
     */
    public function convertFromArrayReturnsNullForEmptyDate(): void
    {
        self::assertNull($this->converter->convertFrom(['date' => ''], 'DateTime', [], null));
    }

    /**
     * @return array
     * @see convertFromArrayTests()
     */
    public function convertFromArrayDataProvider(): array
    {
        return [
            [['date' => '2005-08-15T15:52:01+01:00'], true, '2005-08-15T15:52:01+01:00'],
            [['date' => '1308174051', 'dateFormat' => ''], false, null],
            [['date' => '13-12-1980', 'dateFormat' => 'd.m.Y'], false, null],
            [['date' => '1308174051', 'dateFormat' => 'Y-m-d'], false, null],
            [['date' => '12:13', 'dateFormat' => 'H:i'], true, null],
            [['date' => '13.12.1980', 'dateFormat' => 'd.m.Y'], true, null],
            [['date' => '2005-08-15T15:52:01+00:00', 'dateFormat' => ''], true, '2005-08-15T15:52:01+00:00'],
            [['date' => '2005-08-15T15:52:01+00:00', 'dateFormat' => \DateTimeInterface::ATOM], true, '2005-08-15T15:52:01+00:00'],
            [['date' => '1308174051', 'dateFormat' => 'U'], true, '2011-06-15T21:40:51+00:00'],
            [['date' => 1308174051, 'dateFormat' => 'U'], true, '2011-06-15T21:40:51+00:00'],
            [['date' => -1308174051, 'dateFormat' => 'U'], true, '1928-07-19T02:19:09+00:00'],
        ];
    }

    /**
     * @param array $source the array to be converted
     * @param bool $isValid TRUE if the conversion is expected to be successful, otherwise FALSE
     * @param string|null $expectedResult
     * @test
     * @dataProvider convertFromArrayDataProvider
     */
    public function convertFromArrayTests(array $source, $isValid, ?string $expectedResult): void
    {
        $dateFormat = isset($source['dateFormat']) && $source['dateFormat'] !== '' ? $source['dateFormat'] : null;
        if ($dateFormat !== null) {
            $mockMappingConfiguration = $this->createMock(PropertyMappingConfigurationInterface::class);
            $mockMappingConfiguration
                    ->expects(self::atLeastOnce())
                    ->method('getConfigurationValue')
                    ->with(DateTimeConverter::class, DateTimeConverter::CONFIGURATION_DATE_FORMAT)
                    ->willReturn($dateFormat);
        } else {
            $mockMappingConfiguration = null;
        }
        $date = $this->converter->convertFrom($source, 'DateTime', [], $mockMappingConfiguration);

        if ($isValid !== true) {
            self::assertInstanceOf(Error::class, $date);
            return;
        }

        self::assertInstanceOf('DateTime', $date);
        if ($dateFormat === null) {
            $dateFormat = DateTimeConverter::DEFAULT_DATE_FORMAT;
        }
        $dateAsString = isset($source['date']) ? (string)$source['date'] : '';
        self::assertSame($dateAsString, $date->format($dateFormat));
        if ($expectedResult !== null) {
            self::assertSame($expectedResult, $date->format('c'));
        }
    }

    /**
     * @test
     */
    public function convertFromSupportsDateTimeSubClasses(): void
    {
        $className = DateTimeSubFixture::class;
        $date = $this->converter->convertFrom('2005-08-15T15:52:01+00:00', $className);

        self::assertInstanceOf($className, $date);
        self::assertSame('Bar', $date->foo());
    }
}
