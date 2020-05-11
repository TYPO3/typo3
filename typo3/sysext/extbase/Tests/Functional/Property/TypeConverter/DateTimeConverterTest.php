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

use TYPO3\CMS\Extbase\Property\Exception;
use TYPO3\CMS\Extbase\Property\PropertyMapper;
use TYPO3\CMS\Extbase\Property\PropertyMappingConfiguration;
use TYPO3\CMS\Extbase\Property\TypeConverter\DateTimeConverter;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class DateTimeConverterTest extends FunctionalTestCase
{
    /**
     * @test
     */
    public function convertFromReturnsAnErrorWhenConvertingIntegersToDateTime()
    {
        $propertyMapper = $this->getContainer()->get(PropertyMapper::class);

        $dateTime = $propertyMapper->convert(0, \DateTime::class);

        self::assertNull($dateTime);
        self::assertTrue($propertyMapper->getMessages()->hasErrors());
        self::assertSame(
            'The date "%s" was not recognized (for format "%s").',
            $propertyMapper->getMessages()->getFirstError()->getMessage()
        );
    }

    /**
     * @test
     */
    public function convertFromReturnsNullIfSourceIsAnEmptyString()
    {
        $propertyMapper = $this->getContainer()->get(PropertyMapper::class);

        $dateTime = $propertyMapper->convert('', \DateTime::class);

        self::assertNull($dateTime);
        self::assertFalse($propertyMapper->getMessages()->hasErrors());
    }

    /**
     * @test
     */
    public function convertDefaultDateFormatString()
    {
        $propertyMapper = $this->getContainer()->get(PropertyMapper::class);

        $dateTime = $propertyMapper->convert('2019-12-07T19:07:02+00:00', \DateTime::class);

        self::assertInstanceOf(\DateTime::class, $dateTime);
        self::assertSame(1575745622, $dateTime->getTimestamp());
    }

    /**
     * @test
     */
    public function convertCustomDateFormatString()
    {
        $propertyMapperConfiguration = new PropertyMappingConfiguration();
        $propertyMapperConfiguration->setTypeConverterOption(
            DateTimeConverter::class,
            DateTimeConverter::CONFIGURATION_DATE_FORMAT,
            \DateTime::RFC7231
        );

        $propertyMapper = $this->getContainer()->get(PropertyMapper::class);

        $dateTime = $propertyMapper->convert(
            'Sat, 07 Dec 2019 19:15:45 GMT',
            \DateTime::class,
            $propertyMapperConfiguration
        );

        self::assertInstanceOf(\DateTime::class, $dateTime);
        self::assertSame(1575746145, $dateTime->getTimestamp());
    }

    /**
     * @test
     */
    public function convertThrowsInvalidPropertyMappingConfigurationExceptionIfDateFormatIsNotAString()
    {
        static::expectException(Exception::class);
        static::expectExceptionCode(1297759968);
        static::expectExceptionMessage('Exception while property mapping at property path "": CONFIGURATION_DATE_FORMAT must be of type string, "array" given');

        $propertyMapperConfiguration = new PropertyMappingConfiguration();
        $propertyMapperConfiguration->setTypeConverterOption(
            DateTimeConverter::class,
            DateTimeConverter::CONFIGURATION_DATE_FORMAT,
            []
        );

        $propertyMapper = $this->getContainer()->get(PropertyMapper::class);

        $propertyMapper->convert(
            'Sat, 07 Dec 2019 19:15:45 GMT',
            \DateTime::class,
            $propertyMapperConfiguration
        );
    }

    /**
     * @test
     */
    public function convertWithArraySourceWithStringDate()
    {
        $propertyMapper = $this->getContainer()->get(PropertyMapper::class);

        $dateTime = $propertyMapper->convert(
            [
                'date' => '2019-12-07T19:07:02+00:00'
            ],
            \DateTime::class
        );

        self::assertInstanceOf(\DateTime::class, $dateTime);
        self::assertSame(1575745622, $dateTime->getTimestamp());
    }

    /**
     * @test
     */
    public function convertWithArraySourceWithIntegerDate()
    {
        $propertyMapperConfiguration = new PropertyMappingConfiguration();
        $propertyMapperConfiguration->setTypeConverterOption(
            DateTimeConverter::class,
            DateTimeConverter::CONFIGURATION_DATE_FORMAT,
            'U'
        );

        $propertyMapper = $this->getContainer()->get(PropertyMapper::class);

        $dateTime = $propertyMapper->convert(
            [
                'date' => 1575745622
            ],
            \DateTime::class,
            $propertyMapperConfiguration
        );

        self::assertInstanceOf(\DateTime::class, $dateTime);
        self::assertSame(1575745622, $dateTime->getTimestamp());
    }

    /**
     * @test
     */
    public function convertWithArraySourceWithDayMonthAndYearSet()
    {
        $propertyMapperConfiguration = new PropertyMappingConfiguration();
        $propertyMapperConfiguration->setTypeConverterOption(
            DateTimeConverter::class,
            DateTimeConverter::CONFIGURATION_DATE_FORMAT,
            'Y-m-d'
        );

        $propertyMapper = $this->getContainer()->get(PropertyMapper::class);

        $dateTime = $propertyMapper->convert(
            [
                'day' => '12',
                'month' => '12',
                'year' => '2019',
            ],
            \DateTime::class,
            $propertyMapperConfiguration
        );

        self::assertInstanceOf(\DateTime::class, $dateTime);
        self::assertSame('2019-12-12', $dateTime->format('Y-m-d'));
    }

    /**
     * @test
     */
    public function convertWithArraySourceWithDayMonthYearAndDateFormatSet()
    {
        $propertyMapper = $this->getContainer()->get(PropertyMapper::class);

        $dateTime = $propertyMapper->convert(
            [
                'day' => '12',
                'month' => '12',
                'year' => '2019',
                'dateFormat' => 'Y-m-d',
            ],
            \DateTime::class
        );

        self::assertInstanceOf(\DateTime::class, $dateTime);
        self::assertSame('2019-12-12', $dateTime->format('Y-m-d'));
    }

    /**
     * @test
     */
    public function convertWithArraySourceWithDayMonthYearHourMinuteAndSecondSet()
    {
        $propertyMapper = $this->getContainer()->get(PropertyMapper::class);

        $dateTime = $propertyMapper->convert(
            [
                'day' => '12',
                'month' => '12',
                'year' => '2019',
                'hour' => '15',
                'minute' => '5',
                'second' => '54',
                'dateFormat' => 'Y-m-d',
            ],
            \DateTime::class
        );

        self::assertInstanceOf(\DateTime::class, $dateTime);
        self::assertSame('2019-12-12 15:05:54', $dateTime->format('Y-m-d H:i:s'));
        self::assertSame(1576163154, $dateTime->getTimestamp());
    }

    /**
     * @test
     */
    public function convertWithArraySourceWithDayMonthYearAndTimeZoneSetWithDateThatIncludesTimezone()
    {
        // Hint:
        // The timezone parameter and the current timezone are ignored when the time parameter
        // either contains a UNIX timestamp (e.g. 946684800) or specifies a timezone (e.g. 2010-01-28T15:00:00+02:00).

        $propertyMapper = $this->getContainer()->get(PropertyMapper::class);

        $dateTime = $propertyMapper->convert(
            [
                'date' => '2019-12-07T19:07:02+00:00',
                'timezone' => 'Pacific/Midway',
            ],
            \DateTime::class
        );

        self::assertInstanceOf(\DateTime::class, $dateTime);
        self::assertSame('2019-12-07T19:07:02+00:00', $dateTime->format(\DateTime::W3C));
        self::assertSame(1575745622, $dateTime->getTimestamp());
    }

    /**
     * @test
     */
    public function convertWithArraySourceWithDayMonthYearAndTimeZoneSet()
    {
        $propertyMapperConfiguration = new PropertyMappingConfiguration();
        $propertyMapperConfiguration->setTypeConverterOption(
            DateTimeConverter::class,
            DateTimeConverter::CONFIGURATION_DATE_FORMAT,
            'Y-m-d H:i:s'
        );

        $propertyMapper = $this->getContainer()->get(PropertyMapper::class);

        $dateTime = $propertyMapper->convert(
            [
                'date' => '2019-12-07 19:07:02',
                'timezone' => 'Pacific/Midway',
            ],
            \DateTime::class,
            $propertyMapperConfiguration
        );

        self::assertInstanceOf(\DateTime::class, $dateTime);
        self::assertSame('2019-12-07T19:07:02-11:00', $dateTime->format(\DateTime::W3C));
        self::assertSame(1575785222, $dateTime->getTimestamp());
    }

    /**
     * @test
     */
    public function convertFromReturnsErrorIfSourceIsAnArrayAndEitherDayMonthOrYearAreLowerThanOne()
    {
        $propertyMapper = $this->getContainer()->get(PropertyMapper::class);

        $dateTime = $propertyMapper->convert(
            [
                'day' => '0',
                'month' => '1',
                'year' => '1',
            ],
            \DateTime::class
        );

        self::assertNull($dateTime);
        self::assertTrue($propertyMapper->getMessages()->hasErrors());
        self::assertSame(
            'Could not convert the given date parts into a DateTime object because one or more parts were 0.',
            $propertyMapper->getMessages()->getFirstError()->getMessage()
        );
    }

    /**
     * @test
     */
    public function convertFromThrowsTypeConverterExceptionIfSourceIsAnInvalidArraySource()
    {
        static::expectException(Exception::class);
        static::expectExceptionCode(1297759968);
        static::expectExceptionMessage('Exception while property mapping at property path "": Could not convert the given source into a DateTime object because it was not an array with a valid date as a string');

        $this->getContainer()->get(PropertyMapper::class)->convert([], \DateTime::class);
    }

    /**
     * @test
     */
    public function convertFromThrowsTypeConverterExceptionIfGivenDateTimeZoneIsInvalid()
    {
        static::expectException(Exception::class);
        static::expectExceptionCode(1297759968);
        static::expectExceptionMessage('Exception while property mapping at property path "": The specified timezone "foo" is invalid.');

        $propertyMapperConfiguration = new PropertyMappingConfiguration();
        $propertyMapperConfiguration->setTypeConverterOption(
            DateTimeConverter::class,
            DateTimeConverter::CONFIGURATION_DATE_FORMAT,
            'Y-m-d H:i:s'
        );

        $propertyMapper = $this->getContainer()->get(PropertyMapper::class);

        $propertyMapper->convert(
            [
                'date' => '2019-12-07 19:07:02',
                'timezone' => 'foo',
            ],
            \DateTime::class,
            $propertyMapperConfiguration
        );
    }
}
