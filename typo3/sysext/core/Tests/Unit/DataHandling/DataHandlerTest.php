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

namespace TYPO3\CMS\Core\Tests\Unit\DataHandling;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Uid\Uuid;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Cache\Frontend\PhpFrontend;
use TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools;
use TYPO3\CMS\Core\Crypto\PasswordHashing\PasswordHashFactory;
use TYPO3\CMS\Core\Crypto\Random;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\DataHandling\DataHandlerCheckModifyAccessListHookInterface;
use TYPO3\CMS\Core\DataHandling\PageDoktypeRegistry;
use TYPO3\CMS\Core\DataHandling\PagePermissionAssembler;
use TYPO3\CMS\Core\DataHandling\ReferenceIndexUpdater;
use TYPO3\CMS\Core\EventDispatcher\NoopEventDispatcher;
use TYPO3\CMS\Core\LinkHandling\TypoLinkCodecService;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\PasswordPolicy\Event\EnrichPasswordValidationContextDataEvent;
use TYPO3\CMS\Core\PasswordPolicy\Validator\Dto\ContextData;
use TYPO3\CMS\Core\Schema\FieldTypeFactory;
use TYPO3\CMS\Core\Schema\RelationMapBuilder;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Service\OpcodeCacheService;
use TYPO3\CMS\Core\SysLog\Action;
use TYPO3\CMS\Core\SysLog\Error;
use TYPO3\CMS\Core\Tests\Unit\DataHandling\Fixtures\AllowAccessHookFixture;
use TYPO3\CMS\Core\Tests\Unit\DataHandling\Fixtures\InvalidHookFixture;
use TYPO3\CMS\Core\Tests\Unit\DataHandling\Fixtures\UserOddNumberFilter;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\TestingFramework\Core\AccessibleObjectInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class DataHandlerTest extends UnitTestCase
{
    protected DataHandler&MockObject&AccessibleObjectInterface $subject;
    protected BackendUserAuthentication&MockObject $backendUserMock;
    protected TcaSchemaFactory $tcaSchemaFactory;

    protected function setUp(): void
    {
        parent::setUp();
        $cacheMock = $this->createMock(PhpFrontend::class);
        $cacheMock->method('has')->with(self::isString())->willReturn(false);
        $this->tcaSchemaFactory = new TcaSchemaFactory(
            new RelationMapBuilder($this->createMock(FlexFormTools::class)),
            new FieldTypeFactory(),
            '',
            $cacheMock
        );
        $constructorArguments = [
            new NoopEventDispatcher(),
            $this->createMock(CacheManager::class),
            $this->createMock(FrontendInterface::class),
            $this->createMock(ConnectionPool::class),
            $this->createMock(LoggerInterface::class),
            new PagePermissionAssembler(),
            $this->tcaSchemaFactory,
            new PageDoktypeRegistry($this->tcaSchemaFactory),
            $this->createMock(FlexFormTools::class),
            new PasswordHashFactory(),
            new Random(),
            new TypoLinkCodecService(new NoopEventDispatcher()),
            new OpcodeCacheService(),
            $this->createMock(FlashMessageService::class),
        ];
        $this->subject = $this->getAccessibleMock(DataHandler::class, null, $constructorArguments);
        $this->backendUserMock = $this->createMock(BackendUserAuthentication::class);
        $this->subject->start([], [], $this->backendUserMock, $this->createMock(ReferenceIndexUpdater::class));
    }

    #[Test]
    public function adminIsAllowedToModifyNonAdminTable(): void
    {
        $this->subject->admin = true;
        self::assertTrue($this->subject->_call('checkModifyAccessList', 'tt_content'));
    }

    #[Test]
    public function nonAdminIsNorAllowedToModifyNonAdminTable(): void
    {
        $this->subject->admin = false;
        self::assertFalse($this->subject->_call('checkModifyAccessList', 'tt_content'));
    }

    #[Test]
    public function nonAdminWithTableModifyAccessIsAllowedToModifyNonAdminTable(): void
    {
        $this->subject->admin = false;
        $this->backendUserMock->groupData['tables_modify'] = 'tt_content';
        self::assertTrue($this->subject->_call('checkModifyAccessList', 'tt_content'));
    }

    #[Test]
    public function adminIsAllowedToModifyAdminTable(): void
    {
        $this->subject->admin = true;
        self::assertTrue($this->subject->_call('checkModifyAccessList', 'be_users'));
    }

    #[Test]
    public function nonAdminIsNotAllowedToModifyAdminTable(): void
    {
        $this->subject->admin = false;
        self::assertFalse($this->subject->_call('checkModifyAccessList', 'be_users'));
    }

    #[Test]
    public function nonAdminWithTableModifyAccessIsNotAllowedToModifyAdminTable(): void
    {
        $tableName = StringUtility::getUniqueId('aTable');
        $GLOBALS['TCA'] = [
            $tableName => [
                'ctrl' => [
                    'adminOnly' => true,
                ],
            ],
        ];
        $this->subject->admin = false;
        $this->backendUserMock->groupData['tables_modify'] = $tableName;
        $this->tcaSchemaFactory->load($GLOBALS['TCA'], true);
        self::assertFalse($this->subject->_call('checkModifyAccessList', $tableName));
    }

    public static function checkValueForDatetimeDataProvider(): array
    {
        // Three elements: input, timezone of input, expected output (UTC)
        return [
            'timestamp is passed through, as it is UTC' => [
                1457103519, 'Europe/Berlin', 1457103519,
            ],
            'unqualified ISO local is interpreted as local date and is output as correct timestamp' => [
                // 1496787000 = 1496794200 - 2 * 3600
                '2017-06-07T00:10:00', 'Europe/Berlin', 1496787000,
            ],
            'qualified ISO date with 0 (Z) offset is respected and is output as correct timestamp' => [
                '2017-06-07T00:10:00Z', 'Europe/Berlin', 1496794200,
            ],
            'qualified ISO date with 0 offset is respected and is output as correct timestamp' => [
                '2017-06-07T00:10:00+00:00', 'Europe/Berlin', 1496794200,
            ],
            'qualified ISO date with 2 hour offset is respected and is output as correct timestamp' => [
                '2017-06-07T02:10:00+02:00', 'Europe/Berlin', 1496794200,
            ],
            'qualified ISO date with 4 hour offset is respected and is output as correct timestamp' => [
                '2017-06-07T04:10:00+04:00', 'Europe/Berlin', 1496794200,
            ],
            'qualified ISO date with -2 hour offset is respected and is output as correct timestamp' => [
                '2017-06-06T22:10:00-02:00', 'Europe/Berlin', 1496794200,
            ],
        ];
    }

    #[DataProvider('checkValueForDatetimeDataProvider')]
    #[Test]
    public function checkValueForDatetime($input, $serverTimezone, $expectedOutput): void
    {
        $oldTimezone = date_default_timezone_get();
        date_default_timezone_set($serverTimezone);

        $output = $this->subject->_call('checkValueForDatetime', $input, ['type' => 'datetime']);

        // set before the assertion is performed, so it is restored even for failing tests
        date_default_timezone_set($oldTimezone);

        self::assertEquals($expectedOutput, $output['value']);
    }

    public static function checkValueForColorDataProvider(): array
    {
        // Three elements: input, timezone of input, expected output (UTC)
        return [
            'trim is applied' => [
                ' #FF8700 ', '#FF8700',
            ],
            'max string length is respected' => [
                '#FF8700123', '#FF8700',
            ],
            'required is checked' => [
                '', null, ['required' => true],
            ],
        ];
    }

    #[DataProvider('checkValueForColorDataProvider')]
    #[Test]
    public function checkValueForColor(string $input, mixed $expected, array $additionalFieldConfig = []): void
    {
        $output = $this->subject->_call(
            'checkValueForColor',
            $input,
            array_replace_recursive(['type' => 'datetime'], $additionalFieldConfig)
        );

        self::assertEquals($expected, $output['value'] ?? null);
    }

    #[Test]
    public function checkValuePasswordWithSaltedPasswordKeepsExistingHash(): void
    {
        // Note the involved salted passwords are NOT mocked since the factory is static
        $inputValue = '$1$GNu9HdMt$RwkPb28pce4nXZfnplVZY/';
        $result = $this->subject->_call('checkValueForPassword', $inputValue, [], 'be_users', 0, 0);
        self::assertSame($inputValue, $result['value']);
    }

    #[Test]
    public function checkValuePasswordWithSaltedPasswordReturnsHashForSaltedPassword(): void
    {
        $inputValue = 'myPassword';
        $result = $this->subject->_call('checkValueForPassword', $inputValue, [], 'be_users', 0, 0);
        self::assertNotSame($inputValue, $result['value']);
    }

    #[Test]
    public function checkValuePasswordWithSaltedPasswordDispatchesEvent(): void
    {
        $event = new EnrichPasswordValidationContextDataEvent(new ContextData(), [], '');
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher->expects(self::once())->method('dispatch')->willReturn($event);
        $constructorArguments = [
            $eventDispatcher,
            $this->createMock(CacheManager::class),
            $this->createMock(FrontendInterface::class),
            $this->createMock(ConnectionPool::class),
            $this->createMock(LoggerInterface::class),
            new PagePermissionAssembler(),
            $this->tcaSchemaFactory,
            new PageDoktypeRegistry($this->tcaSchemaFactory),
            $this->createMock(FlexFormTools::class),
            new PasswordHashFactory(),
            new Random(),
            new TypoLinkCodecService(new NoopEventDispatcher()),
            new OpcodeCacheService(),
            $this->createMock(FlashMessageService::class),
        ];
        $subject = $this->getAccessibleMock(DataHandler::class, null, $constructorArguments, '');
        $inputValue = 'myPassword';
        $result = $subject->_call('checkValueForPassword', $inputValue, [], 'be_users', 0, 0);
        self::assertNotSame($inputValue, $result['value']);
    }

    public static function numberValueCheckRecognizesStringValuesAsIntegerValuesCorrectlyDataProvider(): array
    {
        return [
            'Empty string returns zero as integer' => [
                '',
                0,
            ],
            '"0" returns zero as integer' => [
                '0',
                0,
            ],
            '"-2000001" is interpreted correctly as -2000001 but is lower than -2000000 and set to -2000000' => [
                '-2000001',
                -2000000,
            ],
            '"-2000000" is interpreted correctly as -2000000 and is equal to -2000000' => [
                '-2000000',
                -2000000,
            ],
            '"2000000" is interpreted correctly as 2000000 and is equal to 2000000' => [
                '2000000',
                2000000,
            ],
            '"2000001" is interpreted correctly as 2000001 but is greater then 2000000 and set to 2000000' => [
                '2000001',
                2000000,
            ],
        ];
    }

    #[DataProvider('numberValueCheckRecognizesStringValuesAsIntegerValuesCorrectlyDataProvider')]
    #[Test]
    public function numberValueCheckRecognizesStringValuesAsIntegerValuesCorrectly(string $value, int $expectedReturnValue): void
    {
        $tcaFieldConf = [
            'type' => 'number',
            'range' => [
                'lower' => '-2000000',
                'upper' => '2000000',
            ],
        ];
        $returnValue = $this->subject->_call('checkValueForNumber', $value, $tcaFieldConf, '', 0, 0, '');
        self::assertSame($expectedReturnValue, $returnValue['value']);
    }

    public static function numberValueCheckRecognizesDecimalStringValuesAsFloatValuesCorrectlyDataProvider(): iterable
    {
        yield 'simple negative decimal value with comma and one position' => [
            'input' => '-0,5',
            'expected' => '-0.50',
        ];

        yield 'simple integer' => [
            'input' => '1000',
            'expected' => '1000.00',
        ];

        yield 'positive decimal value with one position' => [
            'input' => '1000,0',
            'expected' => '1000.00',
        ];

        yield 'positive decimal value with 2 positions and trailing zero' => [
            'input' => '1000,10',
            'expected' => '1000.10',
        ];

        yield 'positive decimal value with 2 positions without trailing zero' => [
            'input' => '1000,11',
            'expected' => '1000.11',
        ];

        yield 'number separated with dots' => [
            'input' => '600.000.000,00',
            'expected' => '600000000.00',
        ];

        yield 'invalid input with characters between the number' => [
            'input' => '60aaa00',
            'expected' => '6000.00',
        ];
    }

    #[DataProvider('numberValueCheckRecognizesDecimalStringValuesAsFloatValuesCorrectlyDataProvider')]
    #[Test]
    public function numberValueCheckRecognizesDecimalStringValuesAsFloatValuesCorrectly(string $input, string $expected): void
    {
        $tcaFieldConf = [
            'type' => 'number',
            'format' => 'decimal',
        ];
        $returnValue = $this->subject->_call('checkValueForNumber', $input, $tcaFieldConf);
        self::assertSame($expected, $returnValue['value']);
    }

    public static function inputValuesRangeDoubleDataProvider(): array
    {
        return [
            'Empty string returns zero as string' => [
                '',
                '0.00',
            ],
            '"0" returns zero as string' => [
                '0',
                '0.00',
            ],
            '"-0.5" is interpreted correctly as -0.5 but is lower than 0 and set to 0' => [
                '-0.5',
                0.0,
            ],
            '"0.5" is interpreted correctly as 0.5 and is equal to 0.5' => [
                '0.5',
                '0.50',
            ],
            '"39.9" is interpreted correctly as 39.9 and is equal to 39.9' => [
                '39.9',
                '39.90',
            ],
            '"43.3" is interpreted correctly as 43.3 but is greater then 42 and set to 42' => [
                '43.3',
                42.0,
            ],
        ];
    }

    #[DataProvider('inputValuesRangeDoubleDataProvider')]
    #[Test]
    public function inputValueCheckRespectsRightLowerAndUpperLimitForDouble(string $value, string|int|float $expectedReturnValue): void
    {
        $tcaFieldConf = [
            'type' => 'number',
            'format' => 'decimal',
            'range' => [
                'lower' => '0',
                'upper' => '42',
            ],
        ];
        $returnValue = $this->subject->_call('checkValueForNumber', $value, $tcaFieldConf);
        self::assertSame($expectedReturnValue, $returnValue['value']);
    }

    #[DataProvider('inputValuesRangeDoubleDataProvider')]
    #[Test]
    public function inputValueCheckRespectsRightLowerAndUpperLimitWithDefaultValueForDouble(string $value, string|int|float $expectedReturnValue): void
    {
        $tcaFieldConf = [
            'type' => 'number',
            'format' => 'decimal',
            'range' => [
                'lower' => '0',
                'upper' => '42',
            ],
            'default' => 0,
        ];
        $returnValue = $this->subject->_call('checkValueForNumber', $value, $tcaFieldConf, '', 0, 0, '');
        self::assertSame($expectedReturnValue, $returnValue['value']);
    }

    public static function datetimeValuesDataProvider(): array
    {
        return [
            'undershot date adjusted in UTC' => [
                '2018-02-28T00:00:00',
                1519862400,
                'UTC',
            ],
            'exact lower date accepted in UTC' => [
                '2018-03-01T00:00:00',
                1519862400,
                'UTC',
            ],
            'exact upper date accepted in UTC' => [
                '2018-03-31T23:59:59',
                1522540799,
                'UTC',
            ],
            'exceeded date adjusted in UTC' => [
                '2018-04-01T00:00:00',
                1522540799,
                'UTC',
            ],
            'undershot date adjusted in Europe/Berlin' => [
                '2018-02-28T00:00:00',
                1519858800,
                'Europe/Berlin',
            ],
            'exact lower date accepted in Europe/Berlin' => [
                '2018-03-01T00:00:00',
                1519858800,
                'Europe/Berlin',
            ],
            'exact upper date accepted in Europe/Berlin' => [
                '2018-03-31T23:59:59',
                1522533599,
                'Europe/Berlin',
            ],
            'exceeded date adjusted in Europe/Berlin' => [
                '2018-04-01T00:00:00',
                1522533599,
                'Europe/Berlin',
            ],
        ];
    }

    #[DataProvider('datetimeValuesDataProvider')]
    #[Test]
    public function valueCheckRecognizesDatetimeValuesAsIntegerValuesCorrectly(string $value, int $expected, string $timezone): void
    {
        $tcaFieldConf = [
            'type' => 'datetime',
            'range' => [
                // unix timestamp: 1519862400 if timezone is UTC, 1519858800 if timezone is Europe/Berlin
                'lower' => \DateTime::createFromFormat('Y-m-d\\TH:i:s', '2018-03-01T00:00:00', new \DateTimeZone($timezone))->getTimestamp(),
                // unix timestamp: 1522540799 if timezone is UTC, 1522533599 if timezone is Europe/Berlin
                'upper' => \DateTime::createFromFormat('Y-m-d\\TH:i:s', '2018-03-31T23:59:59', new \DateTimeZone($timezone))->getTimestamp(),
            ],
        ];

        $previousTimezone = date_default_timezone_get();
        date_default_timezone_set($timezone);

        $returnValue = $this->subject->_call('checkValueForDatetime', $value, $tcaFieldConf);

        date_default_timezone_set($previousTimezone);

        self::assertSame($expected, $returnValue['value']);
    }

    public static function inputValueRangeCheckIsIgnoredWhenDefaultIsZeroAndInputValueIsEmptyDataProvider(): array
    {
        return [
            'Empty string returns null if nullable, zero otherwise' => [
                '',
                0,
                null,
            ],
            'Zero returns zero' => [
                0,
                0,
                '2021-07-23 22:00:00',
            ],
            'Zero as a string returns zero' => [
                '0',
                0,
                '2021-07-23 22:00:00',
            ],
        ];
    }

    #[DataProvider('inputValueRangeCheckIsIgnoredWhenDefaultIsZeroAndInputValueIsEmptyDataProvider')]
    #[Test]
    public function inputValueRangeCheckIsIgnoredWhenDefaultIsZeroAndInputValueIsEmpty(
        string|int $inputValue,
        int $expectedForTimestampField,
        ?string $expectedForNativeField,
    ): void {
        $tcaFieldConf = [
            'type' => 'datetime',
            'default' => 0,
            'range' => [
                'lower' => 1627077600,
            ],
        ];

        $returnValue = $this->subject->_call('checkValueForDatetime', $inputValue, $tcaFieldConf);
        self::assertSame($expectedForTimestampField, $returnValue['value']);

        $returnValue = $this->subject->_call('checkValueForDatetime', $inputValue, [...$tcaFieldConf, 'dbType' => 'datetime']);
        self::assertSame($expectedForNativeField, $returnValue['value']);
    }

    public static function datetimeValueCheckIsIndependentFromTimezoneDataProvider(): array
    {
        return [
            // Values of this kind are passed in from the DateTime control
            'time from ISO8601 LOCALTIME' => [
                '1970-01-01T18:54:00',
                'time',
                '18:54:00',
            ],
            'date from ISO8601 LOCALTIME' => [
                '2020-11-25T00:00:00',
                'date',
                '2020-11-25',
            ],
            'datetime from ISO8601 LOCALTIME' => [
                '2020-11-25T18:54:00',
                'datetime',
                '2020-11-25 18:54:00',
            ],
            'timestamp from ISO8601 LOCALTIME' => [
                '1970-01-01T18:54:00',
                '',
                64440,
            ],
            // Values of this kind are passed in when a data record is copied
            'time from copying a record' => [
                '18:54:00',
                'time',
                '18:54:00',
            ],
            'date from copying a record' => [
                '2020-11-25',
                'date',
                '2020-11-25',
            ],
            'datetime from copying a record' => [
                '2020-11-25 18:54:00',
                'datetime',
                '2020-11-25 18:54:00',
            ],
            'timestamp from copying a record' => [
                '2020-11-25 18:54:00',
                '',
                1606326840,
            ],
            // Values of this kind are passed in when DataHandler is used as peristence layer/API
            'time from ISO8601 UTC-0' => [
                '1970-01-01T18:54:00Z',
                'time',
                // Apply time from DateTimeString as-is (no conversion to LOCALTIME!)
                '18:54:00',
            ],
            'date from ISO8601 UTC-0' => [
                '2020-11-25T00:00:00Z',
                'date',
                '2020-11-25',
            ],
            'datetime from ISO8601 UTC-0' => [
                // DateTimeString is persisted as server LOCALTIME
                '2020-11-25T18:54:00Z',
                'datetime',
                // DateTimeString is persisted as server LOCALTIME
                '2020-11-25 19:54:00',
            ],
            'timestamp from ISO8601 UTC-0' => [
                '2020-11-25T18:54:00Z',
                '',
                // timestamp is persisted in UTC
                1606330440,
            ],

            'time from ISO8601 UTC+2' => [
                // DateTimeString is taken as-is (offsets are not shifted!)
                // @todo this is discussable
                '1970-01-01T18:54:00+02:00',
                'time',
                '18:54:00',
            ],
            'date from ISO8601 UTC+2' => [
                '2020-11-25T00:00:00+02:00',
                'date',
                // HEADS UP! Input is UTC+2 that means converted to the server timezone
                // (Europe/Berlin is CET in November which is +01:00)
                // that'd be 2020-11-24T23:00:00+01:00,
                // but we still expect the "intended date" to be honored by DataHandler
                '2020-11-25',
            ],
            'datetime from ISO8601 UTC+2' => [
                '2020-11-25T18:54:00+02:00',
                'datetime',
                // November has +01:00 offset (CET) in Europe/Berlin,
                // that means the input (+02:00) is off by one hour to the server local time
                '2020-11-25 17:54:00',
            ],
            'timestamp from ISO8601 UTC+2' => [
                '2020-11-25T18:54:00+02:00',
                '',
                1606323240,
            ],
        ];
    }

    #[DataProvider('datetimeValueCheckIsIndependentFromTimezoneDataProvider')]
    #[Test]
    public function datetimeValueCheckIsIndependentFromTimezone(string $value, string $dbtype, string|int $expectedOutput): void
    {
        $tcaFieldConf = [
            'type' => 'datetime',
            'dbType' => $dbtype,
        ];

        $oldTimezone = date_default_timezone_get();
        date_default_timezone_set('Europe/Berlin');

        $returnValue = $this->subject->_call('checkValueForDatetime', $value, $tcaFieldConf);

        // set before the assertion is performed, so it is restored even for failing tests
        date_default_timezone_set($oldTimezone);

        self::assertEquals($expectedOutput, $returnValue['value']);
    }

    public static function inputValueCheckNativeDbTypeDataProvider(): array
    {
        return [
            'Datetime at unix epoch' => [
                '1970-01-01T00:00:00',
                'datetime',
                '1970-01-01 00:00:00',
                '1970-01-01 00:00:00',
            ],
            'Default datetime' => [
                '0000-00-00 00:00:00',
                'datetime',
                null,
                '0000-00-00 00:00:00',
            ],
            'Default date' => [
                '0000-00-00',
                'date',
                null,
                '0000-00-00',
            ],
            'Default time' => [
                '00:00:00',
                'time',
                '00:00:00',
                '00:00:00',
            ],
            'Null time' => [
                null,
                'time',
                null,
                '00:00:00',
            ],
            'Minimum mysql datetime' => [
                '1000-01-01 00:00:00',
                'datetime',
                '1000-01-01 00:00:00',
                '1000-01-01 00:00:00',
            ],
            'Maximum mysql datetime' => [
                '9999-12-31 23:59:59',
                'datetime',
                '9999-12-31 23:59:59',
                '9999-12-31 23:59:59',
            ],
        ];
    }

    #[DataProvider('inputValueCheckNativeDbTypeDataProvider')]
    #[Test]
    public function inputValueCheckNativeDbType(
        ?string $value,
        string $dbType,
        ?string $expectedNullableOutput,
        string $expectedNotNullableOutput
    ): void {
        // Explicit nullable
        $tcaFieldConf = [
            'input' => [],
            'dbType' => $dbType,
            'format' => $dbType,
            'nullable' => true,
        ];

        $returnValue = $this->subject->_call('checkValueForDatetime', $value, $tcaFieldConf);
        self::assertEquals($expectedNullableOutput, $returnValue['value']);

        // Implicit nullable
        $tcaFieldConf = [
            'input' => [],
            'dbType' => $dbType,
            'format' => $dbType,
        ];

        $returnValue = $this->subject->_call('checkValueForDatetime', $value, $tcaFieldConf);
        self::assertEquals($expectedNullableOutput, $returnValue['value']);

        // Not null
        $tcaFieldConf = [
            'input' => [],
            'dbType' => $dbType,
            'format' => $dbType,
            'nullable' => false,
        ];

        $returnValue = $this->subject->_call('checkValueForDatetime', $value, $tcaFieldConf);
        self::assertEquals($expectedNotNullableOutput, $returnValue['value']);
    }

    public static function inputValueCheckDatetimeFormatTimeAsTimestampDataProvider(): array
    {
        return [
            'Null on nullable timesec' => [
                null,
                'timesec',
                true,
                null,
            ],
            'Null on not nullable timesec' => [
                null,
                'timesec',
                false,
                0,
            ],

            'Null on nullable time' => [
                null,
                'time',
                true,
                null,
            ],
            'Null on not nullable time' => [
                null,
                'time',
                false,
                0,
            ],

            'timesec as time string' => [
                '14:38:05',
                'timesec',
                false,
                52685,
            ],
            'timesec as ISO8601 LOCALTIME' => [
                '1970-01-01T14:38:05',
                'timesec',
                false,
                52685,
            ],
            'timesec with offset' => [
                '1970-01-01T14:38:05+04:00',
                'timesec',
                false,
                52685,
            ],
            'timesec with offset and invalid date' => [
                '2024-11-07T14:38:05+04:00',
                'timesec',
                false,
                52685,
            ],

            'time as time string' => [
                '14:38:00',
                'time',
                false,
                52680,
            ],
            'time as time string with seconds to be ignored' => [
                '14:38:05',
                'time',
                false,
                52680,
            ],
            'time as ISO8601 LOCALTIME' => [
                '1970-01-01T14:38:00',
                'time',
                false,
                52680,
            ],
            'time as ISO8601 LOCALTIME with seconds to be ignored' => [
                '1970-01-01T14:38:05',
                'time',
                false,
                52680,
            ],
            'time with offset' => [
                '1970-01-01T14:38:00+04:00',
                'time',
                false,
                52680,
            ],
            'time with offset and seconds to be ignored' => [
                '1970-01-01T14:38:05+04:00',
                'time',
                false,
                52680,
            ],
            'time with offset and invalid date' => [
                '2024-11-07T14:38:05+04:00',
                'time',
                false,
                52680,
            ],
        ];
    }

    #[DataProvider('inputValueCheckDatetimeFormatTimeAsTimestampDataProvider')]
    #[Test]
    public function inputValueCheckDatetimeFormatTimeAsTimestamp(?string $value, string $format, bool $nullable, ?int $expectedOutput): void
    {
        $tcaFieldConf = [
            'input' => [],
            'format' => $format,
            'nullable' => $nullable,
        ];

        $oldTimezone = date_default_timezone_get();
        date_default_timezone_set('Europe/Berlin');

        $returnValue = $this->subject->_call('checkValueForDatetime', $value, $tcaFieldConf);

        // set before the assertion is performed, so it is restored even for failing tests
        date_default_timezone_set($oldTimezone);

        self::assertEquals($expectedOutput, $returnValue['value']);
    }

    #[Test]
    public function doesCheckModifyAccessListThrowExceptionOnWrongHookInterface(): void
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionCode(1251892472);

        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['checkModifyAccessList'][] = InvalidHookFixture::class;
        $this->subject->_call('checkModifyAccessList', 'tt_content');
    }

    #[Test]
    public function doesCheckModifyAccessListHookGetsCalled(): void
    {
        $hookClass = StringUtility::getUniqueId('tx_coretest');
        $hookMock = $this->getMockBuilder(DataHandlerCheckModifyAccessListHookInterface::class)
            ->onlyMethods(['checkModifyAccessList'])
            ->setMockClassName($hookClass)
            ->getMock();
        $hookMock->expects(self::once())->method('checkModifyAccessList');
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['checkModifyAccessList'][] = $hookClass;
        GeneralUtility::addInstance($hookClass, $hookMock);
        $this->subject->_call('checkModifyAccessList', 'tt_content');
    }

    #[Test]
    public function doesCheckModifyAccessListHookModifyAccessAllowed(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['checkModifyAccessList'][] = AllowAccessHookFixture::class;
        self::assertTrue($this->subject->_call('checkModifyAccessList', 'tt_content'));
    }

    public static function checkValue_flex_procInData_travDSDataProvider(): iterable
    {
        yield 'Flat structure' => [
            'dataValues' => [
                'field1' => [
                    'vDEF' => 'wrong input',
                ],
            ],
            'DSelements' => [
                'field1' => [
                    'label' => 'A field',
                    'config' => [
                        'type' => 'number',
                        'required' => true,
                    ],
                ],
            ],
            'expected' => [
                'field1' => [
                    'vDEF' => 0,
                ],
            ],
        ];

        yield 'Array structure' => [
            'dataValues' => [
                'section' => [
                    'el' => [
                        '1' => [
                            'container1' => [
                                'el' => [
                                    'field1' => [
                                        'vDEF' => 'wrong input',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'DSelements' => [
                'section' => [
                    'type' => 'array',
                    'section' => true,
                    'el' => [
                        'container1' => [
                            'type' => 'array',
                            'el' => [
                                'field1' => [
                                    'label' => 'A field',
                                    'config' => [
                                        'type' => 'number',
                                        'required' => true,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'expected' => [
                'section' => [
                    'el' => [
                        '1' => [
                            'container1' => [
                                'el' => [
                                    'field1' => [
                                        'vDEF' => 0,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * This test ensures, that the eval method checkValue_SW is called on flexform structures.
     */
    #[DataProvider('checkValue_flex_procInData_travDSDataProvider')]
    #[Test]
    public function checkValue_flex_procInData_travDS(array $dataValues, array $DSelements, array $expected): void
    {
        $pParams = [
            'tt_content',
            777,
            '<?xml ... ?>',
            'update',
            1,
            'tt_content:777:pi_flexform',
            0,
        ];

        $GLOBALS['LANG'] = $this->createMock(LanguageService::class);
        $this->subject->checkValue_flex_procInData_travDS($dataValues, [], $DSelements, $pParams, '', '');
        self::assertSame($expected, $dataValues);
    }

    #[Test]
    public function logCallsWriteLogOfBackendUserIfLoggingIsEnabled(): void
    {
        $backendUser = $this->createMock(BackendUserAuthentication::class);
        $backendUser->expects(self::once())->method('writelog');
        $this->subject->enableLogging = true;
        $this->subject->BE_USER = $backendUser;
        $this->subject->log('', 23, Action::UNDEFINED, null, Error::MESSAGE, 'details');
    }

    #[Test]
    public function logDoesNotCallWriteLogOfBackendUserIfLoggingIsDisabled(): void
    {
        $backendUser = $this->createMock(BackendUserAuthentication::class);
        $backendUser->expects(self::never())->method('writelog');
        $this->subject->enableLogging = false;
        $this->subject->BE_USER = $backendUser;
        $this->subject->log('', 23, Action::UNDEFINED, null, Error::MESSAGE, 'details');
    }

    #[Test]
    public function logAddsEntryToLocalErrorLogArray(): void
    {
        $backendUser = $this->createMock(BackendUserAuthentication::class);
        $this->subject->BE_USER = $backendUser;
        $this->subject->enableLogging = true;
        $this->subject->errorLog = [];
        $logDetailsUnique = StringUtility::getUniqueId('details');
        $this->subject->log('', 23, Action::UNDEFINED, null, Error::USER_ERROR, $logDetailsUnique);
        self::assertArrayHasKey(0, $this->subject->errorLog);
        self::assertStringEndsWith($logDetailsUnique, $this->subject->errorLog[0]);
    }

    #[Test]
    public function logFormatsDetailMessageWithAdditionalDataInLocalErrorArray(): void
    {
        $subject = new DataHandler(
            new NoopEventDispatcher(),
            $this->createMock(CacheManager::class),
            $this->createMock(FrontendInterface::class),
            $this->createMock(ConnectionPool::class),
            $this->createMock(LoggerInterface::class),
            new PagePermissionAssembler(),
            $this->tcaSchemaFactory,
            new PageDoktypeRegistry($this->tcaSchemaFactory),
            $this->createMock(FlexFormTools::class),
            new PasswordHashFactory(),
            new Random(),
            new TypoLinkCodecService(new NoopEventDispatcher()),
            new OpcodeCacheService(),
            $this->createMock(FlashMessageService::class),
        );
        $subject->start([], [], $this->createMock(BackendUserAuthentication::class), $this->createMock(ReferenceIndexUpdater::class));
        $logDetails = StringUtility::getUniqueId('details');
        $subject->log('', 23, Action::UNDEFINED, null, Error::USER_ERROR, '%1$s' . $logDetails . '%2$s', null, ['foo', 'bar']);
        $expected = 'foo' . $logDetails . 'bar';
        self::assertStringEndsWith($expected, $subject->errorLog[0]);
    }

    #[Test]
    public function logFormatsDetailMessageWithPlaceholders(): void
    {
        $subject = new DataHandler(
            new NoopEventDispatcher(),
            $this->createMock(CacheManager::class),
            $this->createMock(FrontendInterface::class),
            $this->createMock(ConnectionPool::class),
            $this->createMock(LoggerInterface::class),
            new PagePermissionAssembler(),
            $this->tcaSchemaFactory,
            new PageDoktypeRegistry($this->tcaSchemaFactory),
            $this->createMock(FlexFormTools::class),
            new PasswordHashFactory(),
            new Random(),
            new TypoLinkCodecService(new NoopEventDispatcher()),
            new OpcodeCacheService(),
            $this->createMock(FlashMessageService::class),
        );
        $subject->start([], [], $this->createMock(BackendUserAuthentication::class), $this->createMock(ReferenceIndexUpdater::class));
        $logDetails = 'An error occurred on {table}:{uid} when localizing';
        $subject->log('', 23, Action::UNDEFINED, null, Error::USER_ERROR, $logDetails, null, ['table' => 'tx_sometable', 0 => 'some random value']);
        // UID is kept as non-replaced, and other properties are not replaced.
        $expected = 'An error occurred on tx_sometable:{uid} when localizing';
        self::assertStringEndsWith($expected, $subject->errorLog[0]);
    }

    public static function equalSubmittedAndStoredValuesAreDeterminedDataProvider(): array
    {
        return [
            // String
            'string value "" vs. ""' => [
                true,
                '', '', 'string', false,
            ],
            'string value 0 vs. "0"' => [
                true,
                0, '0', 'string', false,
            ],
            'string value 1 vs. "1"' => [
                true,
                1, '1', 'string', false,
            ],
            'string value "0" vs. ""' => [
                false,
                '0', '', 'string', false,
            ],
            'string value 0 vs. ""' => [
                false,
                0, '', 'string', false,
            ],
            'string value null vs. ""' => [
                true,
                null, '', 'string', false,
            ],
            // Integer
            'integer value 0 vs. 0' => [
                true,
                0, 0, 'int', false,
            ],
            'integer value "0" vs. "0"' => [
                true,
                '0', '0', 'int', false,
            ],
            'integer value 0 vs. "0"' => [
                true,
                0, '0', 'int', false,
            ],
            'integer value "" vs. "0"' => [
                true,
                '', '0', 'int', false,
            ],
            'integer value "" vs. 0' => [
                true,
                '', 0, 'int', false,
            ],
            'integer value "0" vs. 0' => [
                true,
                '0', 0, 'int', false,
            ],
            'integer value 1 vs. 1' => [
                true,
                1, 1, 'int', false,
            ],
            'integer value 1 vs. "1"' => [
                true,
                1, '1', 'int', false,
            ],
            'integer value "1" vs. "1"' => [
                true,
                '1', '1', 'int', false,
            ],
            'integer value "1" vs. 1' => [
                true,
                '1', 1, 'int', false,
            ],
            'integer value "0" vs. "1"' => [
                false,
                '0', '1', 'int', false,
            ],
            // String with allowed NULL values
            'string with allowed null value "" vs. ""' => [
                true,
                '', '', 'string', true,
            ],
            'string with allowed null value 0 vs. "0"' => [
                true,
                0, '0', 'string', true,
            ],
            'string with allowed null value 1 vs. "1"' => [
                true,
                1, '1', 'string', true,
            ],
            'string with allowed null value "0" vs. ""' => [
                false,
                '0', '', 'string', true,
            ],
            'string with allowed null value 0 vs. ""' => [
                false,
                0, '', 'string', true,
            ],
            'string with allowed null value null vs. ""' => [
                false,
                null, '', 'string', true,
            ],
            'string with allowed null value "" vs. null' => [
                false,
                '', null, 'string', true,
            ],
            'string with allowed null value null vs. null' => [
                true,
                null, null, 'string', true,
            ],
            // Integer with allowed NULL values
            'integer with allowed null value 0 vs. 0' => [
                true,
                0, 0, 'int', true,
            ],
            'integer with allowed null value "0" vs. "0"' => [
                true,
                '0', '0', 'int', true,
            ],
            'integer with allowed null value 0 vs. "0"' => [
                true,
                0, '0', 'int', true,
            ],
            'integer with allowed null value "" vs. "0"' => [
                true,
                '', '0', 'int', true,
            ],
            'integer with allowed null value "" vs. 0' => [
                true,
                '', 0, 'int', true,
            ],
            'integer with allowed null value "0" vs. 0' => [
                true,
                '0', 0, 'int', true,
            ],
            'integer with allowed null value 1 vs. 1' => [
                true,
                1, 1, 'int', true,
            ],
            'integer with allowed null value "1" vs. "1"' => [
                true,
                '1', '1', 'int', true,
            ],
            'integer with allowed null value "1" vs. 1' => [
                true,
                '1', 1, 'int', true,
            ],
            'integer with allowed null value 1 vs. "1"' => [
                true,
                1, '1', 'int', true,
            ],
            'integer with allowed null value "0" vs. "1"' => [
                false,
                '0', '1', 'int', true,
            ],
            'integer with allowed null value null vs. ""' => [
                false,
                null, '', 'int', true,
            ],
            'integer with allowed null value "" vs. null' => [
                false,
                '', null, 'int', true,
            ],
            'integer with allowed null value null vs. null' => [
                true,
                null, null, 'int', true,
            ],
            'integer with allowed null value null vs. "0"' => [
                false,
                null, '0', 'int', true,
            ],
            'integer with allowed null value null vs. 0' => [
                false,
                null, 0, 'int', true,
            ],
            'integer with allowed null value "0" vs. null' => [
                false,
                '0', null, 'int', true,
            ],
        ];
    }

    #[DataProvider('equalSubmittedAndStoredValuesAreDeterminedDataProvider')]
    #[Test]
    public function equalSubmittedAndStoredValuesAreDetermined(bool $expected, string|int|null $submittedValue, string|int|null $storedValue, string $storedType, bool $allowNull): void
    {
        $result = \Closure::bind(function () use ($submittedValue, $storedValue, $storedType, $allowNull) {
            return $this->isSubmittedValueEqualToStoredValue($submittedValue, $storedValue, $storedType, $allowNull);
        }, $this->subject, DataHandler::class)();
        self::assertEquals($expected, $result);
    }

    public static function checkValue_checkReturnsExpectedValuesDataProvider(): array
    {
        return [
            'None item selected' => [
                0,
                0,
            ],
            'All items selected' => [
                7,
                7,
            ],
            'Item 1 and 2 are selected' => [
                3,
                3,
            ],
            'Value is higher than allowed (all checkboxes checked)' => [
                15,
                7,
            ],
            'Value is higher than allowed (some checkboxes checked)' => [
                11,
                3,
            ],
            'Negative value' => [
                -5,
                0,
            ],
        ];
    }

    #[DataProvider('checkValue_checkReturnsExpectedValuesDataProvider')]
    #[Test]
    public function checkValue_checkReturnsExpectedValues(string|int $value, string|int $expectedValue): void
    {
        $expectedResult = [
            'value' => $expectedValue,
        ];
        $result = [];
        $tcaFieldConfiguration = [
            'items' => [
                ['Item 1', 0],
                ['Item 2', 0],
                ['Item 3', 0],
            ],
        ];
        self::assertSame($expectedResult, $this->subject->_call('checkValueForCheck', $result, $value, $tcaFieldConfiguration, '', 0, 0, ''));
    }

    #[Test]
    public function checkValueForRadioAcceptsUndefinedItems(): void
    {
        $expectedResult = [];
        $tcaConfig = [];
        $value = 0;
        $result = $this->subject->_call('checkValueForRadio', [], $value, $tcaConfig, '', 0, 0, '');
        self::assertSame($expectedResult, $result);
    }

    #[Test]
    public function checkValueForInputConvertsNullToEmptyString(): void
    {
        $expectedResult = ['value' => ''];
        self::assertSame($expectedResult, $this->subject->_call('checkValueForInput', null, ['type' => 'input', 'max' => 40], 'tt_content', 'NEW55c0e67f8f4d32.04974534', 89, 'table_caption'));
    }

    public static function checkValueForJsonDataProvider(): \Generator
    {
        yield 'Converts empty string to array' => [
            '',
            ['value' => []],
        ];
        yield 'Converts null to array' => [
            null,
            ['value' => []],
        ];
        yield 'Handles invalid JSON' => [
            '_-invalid-_',
            [],
        ];
        yield 'Decodes JSON' => [
            '{"foo":"bar"}',
            ['value' => ['foo' => 'bar']],
        ];
        yield 'Array is not decoded' => [
            ['foo' => 'bar'],
            ['value' => ['foo' => 'bar']],
        ];
    }

    #[DataProvider('checkValueForJsonDataProvider')]
    #[Test]
    public function checkValueForJson(string|array|null $input, array $expected): void
    {
        self::assertSame(
            $expected,
            $this->subject->_call(
                'checkValueForJson',
                $input,
                ['type' => 'json']
            )
        );
    }

    #[Test]
    public function checkValueForUuidReturnsValidUuidUnmodified(): void
    {
        self::assertEquals(
            'b3190536-1431-453e-afbb-25b8c5022513',
            Uuid::isValid($this->subject->_call('checkValueForUuid', 'b3190536-1431-453e-afbb-25b8c5022513', [])['value'])
        );
    }

    #[Test]
    public function checkValueForUuidCreatesValidUuidValueForReqiredFieldsWithInvalidUuidGiven(): void
    {
        self::assertTrue(Uuid::isValid($this->subject->_call('checkValueForUuid', '', [])['value']));
        self::assertTrue(Uuid::isValid($this->subject->_call('checkValueForUuid', '-_invalid_-', [])['value']));
    }

    #[Test]
    public function checkValueForUuidDiscardsInvalidUuidIfFieldIsNotRequired(): void
    {
        self::assertEmpty($this->subject->_call('checkValueForUuid', '', ['required' => false]));
        self::assertEmpty($this->subject->_call('checkValueForUuid', '-_invalid_-', ['required' => false]));
    }

    #[Test]
    public function checkValueForUuidCreatesValidUuidValueWithDefinedVersion(): void
    {
        self::assertEquals(6, (int)$this->subject->_call('checkValueForUuid', '', ['version' => 6])['value'][14]);
        self::assertEquals(7, (int)$this->subject->_call('checkValueForUuid', '', ['version' => 7])['value'][14]);
        self::assertEquals(4, (int)$this->subject->_call('checkValueForUuid', '', ['version' => 4])['value'][14]);
        // Defaults to 4
        self::assertEquals(4, (int)$this->subject->_call('checkValueForUuid', '', ['version' => 123456678])['value'][14]);
        // Defaults to 4
        self::assertEquals(4, (int)$this->subject->_call('checkValueForUuid', '', [])['value'][14]);
    }

    #[DataProvider('referenceValuesAreCastedDataProvider')]
    #[Test]
    public function referenceValuesAreCasted(string $value, array $configuration, bool $isNew, int|string $expected): void
    {
        self::assertEquals(
            $expected,
            $this->subject->_call('castReferenceValue', $value, $configuration, $isNew)
        );
    }

    public static function referenceValuesAreCastedDataProvider(): array
    {
        return [
            'all empty' => [
                '', [], true, '',
            ],
            'cast zero with MM table' => [
                '', ['MM' => 'table'], true, 0,
            ],
            'cast zero with MM table with default value' => [
                '', ['MM' => 'table', 'default' => 13], true, 0,
            ],
            'cast zero with foreign field' => [
                '', ['foreign_field' => 'table', 'default' => 13], true, 0,
            ],
            'cast zero with foreign field with default value' => [
                '', ['foreign_field' => 'table'], true, 0,
            ],
            'pass zero' => [
                '0', [], true, '0',
            ],
            'pass value' => [
                '1', ['default' => 13], true, '1',
            ],
            'use default value' => [
                '', ['default' => 13], true, 13,
            ],
            'use empty value if available as option' => [
                '', [
                    'items' => [
                        ['label' => 'labelA', 'value' => 'someValue'],
                        ['default' => 'default', 'value' => ''],
                    ],
                    'default' => 'somevalue',
                ], false, '',
            ],
        ];
    }

    public static function clearPrefixFromValueRemovesPrefixDataProvider(): array
    {
        return [
            'normal case' => ['Test (copy 42)', 'Test'],
            // all cases below look fishy and indicate bugs
            'with double spaces before' => ['Test  (copy 42)', 'Test '],
            'with three spaces before' => ['Test   (copy 42)', 'Test  '],
            'with space after' => ['Test (copy 42) ', 'Test (copy 42) '],
            'with double spaces after' => ['Test (copy 42)  ', 'Test (copy 42)  '],
            'with three spaces after' => ['Test (copy 42)   ', 'Test (copy 42)   '],
            'with double tab before' => ['Test' . "\t" . '(copy 42)', 'Test'],
            'with double tab after' => ['Test (copy 42)' . "\t", 'Test (copy 42)' . "\t"],
        ];
    }

    #[DataProvider('clearPrefixFromValueRemovesPrefixDataProvider')]
    #[Test]
    public function clearPrefixFromValueRemovesPrefix(string $input, string $expected): void
    {
        $languageServiceMock = $this->createMock(LanguageService::class);
        $languageServiceMock->method('sL')->with('testLabel')->willReturn('(copy %s)');
        $GLOBALS['LANG'] = $languageServiceMock;
        $GLOBALS['TCA']['testTable']['ctrl']['prependAtCopy'] = 'testLabel';
        $this->tcaSchemaFactory->load($GLOBALS['TCA'], true);
        $subject = new DataHandler(
            new NoopEventDispatcher(),
            $this->createMock(CacheManager::class),
            $this->createMock(FrontendInterface::class),
            $this->createMock(ConnectionPool::class),
            $this->createMock(LoggerInterface::class),
            new PagePermissionAssembler(),
            $this->tcaSchemaFactory,
            new PageDoktypeRegistry($this->tcaSchemaFactory),
            $this->createMock(FlexFormTools::class),
            new PasswordHashFactory(),
            new Random(),
            new TypoLinkCodecService(new NoopEventDispatcher()),
            new OpcodeCacheService(),
            $this->createMock(FlashMessageService::class),
        );
        self::assertEquals($expected, $subject->clearPrefixFromValue('testTable', $input));
    }

    public static function applyFiltersToValuesFiltersValuesDataProvider(): iterable
    {
        yield 'values are filtered by provided user function' => [
            'tcaFieldConfiguration' => [
                'filter' => [
                    [
                        'userFunc' => UserOddNumberFilter::class . '->filter',
                    ],
                ],
            ],
            'values' => [1, 2, 3, 4, 5],
            'expected' => [
                0 => 1,
                2 => 3,
                4 => 5,
            ],
        ];

        yield 'parameters are passed to the user function' => [
            'tcaFieldConfiguration' => [
                'filter' => [
                    [
                        'userFunc' => UserOddNumberFilter::class . '->filter',
                        'parameters' => [
                            'exclude' => 1,
                        ],
                    ],
                ],
            ],
            'values' => [1, 2, 3, 4, 5],
            'expected' => [
                2 => 3,
                4 => 5,
            ],
        ];

        yield 'no filters return value as is' => [
            'tcaFieldConfiguration' => [],
            'values' => [1, 2, 3, 4, 5],
            'expected' => [1, 2, 3, 4, 5],
        ];
    }

    #[DataProvider('applyFiltersToValuesFiltersValuesDataProvider')]
    #[Test]
    public function applyFiltersToValuesFiltersValues(array $tcaFieldConfiguration, array $values, array $expected): void
    {
        self::assertSame($expected, $this->subject->_call('applyFiltersToValues', $tcaFieldConfiguration, $values));
    }

    #[Test]
    public function applyFiltersToValuesExpectsArray(): void
    {
        $tcaFieldConfiguration = [
            'filter' => [
                [
                    'userFunc' => UserOddNumberFilter::class . '->filter',
                    'parameters' => [
                        'break' => true,
                    ],
                ],
            ],
        ];

        $values = [1, 2, 3, 4, 5];
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1336051942);
        $this->expectExceptionMessage('Expected userFunc filter "TYPO3\CMS\Core\Tests\Unit\DataHandling\Fixtures\UserOddNumberFilter->filter" to return an array. Got NULL.');
        $this->subject->_call('applyFiltersToValues', $tcaFieldConfiguration, $values);
    }

    public static function validateValueForRequiredReturnsExpectedValueDataHandler(): iterable
    {
        yield 'no required flag set with empty string' => [
            [],
            '',
            true,
        ];

        yield 'no required flag set with (string)0' => [
            [],
            '0',
            true,
        ];

        yield 'required flag set with empty string' => [
            ['required' => true],
            '',
            false,
        ];

        yield 'required flag set with null value' => [
            ['required' => true],
            null,
            false,
        ];

        yield 'required flag set with (string)0' => [
            ['required' => true],
            '0',
            true,
        ];

        yield 'required flag set with non-empty string' => [
            ['required' => true],
            'foobar',
            true,
        ];
    }

    #[DataProvider('validateValueForRequiredReturnsExpectedValueDataHandler')]
    #[Test]
    public function validateValueForRequiredReturnsExpectedValue(array $tcaFieldConfig, $input, bool $expectation): void
    {
        self::assertSame($expectation, $this->subject->_call('validateValueForRequired', $tcaFieldConfig, $input));
    }

    #[Test]
    #[DataProvider('newFieldArrayExpectedValues')]
    public function newFieldArraySetDefaultValues(string $column, mixed $expected): void
    {
        $GLOBALS['TCA'] = [
            'tx_my_testtable' => [
                'columns' => [
                    'slug_1' => [
                        'config' => [
                            'type' => 'slug',
                        ],
                    ],
                    'slug_2' => [
                        'config' => [
                            'type' => 'slug',
                            'default' => 'shouldnotbeset',
                        ],
                    ],
                    'uuid_1' => [
                        'config' => [
                            'type' => 'uuid',
                        ],
                    ],
                    'uuid_2' => [
                        'config' => [
                            'type' => 'uuid',
                            'default' => 'nosuchconfig',
                        ],
                    ],
                    'input_1' => [
                        'config' => [
                            'type' => 'input',
                            'default' => 'testdefault',
                        ],
                    ],
                    'input_2' => [
                        'config' => [
                            'type' => 'input',
                            'default' => 5,
                        ],
                    ],
                    'input_3' => [
                        'config' => [
                            'type' => 'input',
                            'default' => null,
                            'nullable' => true,
                        ],
                    ],
                ],
            ],
        ];
        $this->tcaSchemaFactory->load($GLOBALS['TCA'], true);
        $defaultValues = $this->subject->_call('newFieldArray', 'tx_my_testtable');
        self::assertArrayHasKey($column, $defaultValues);
        self::assertEquals($expected, $defaultValues[$column]);
    }

    public static function newFieldArrayExpectedValues(): iterable
    {
        yield 'slug column' => [
            'slug_1',
            '',
        ];
        yield 'slug column (no such config)' => [
            'slug_2',
            '',
        ];
        yield 'uuid column' => [
            'uuid_1',
            '',
        ];
        yield 'uuid column (wrong config)' => [
            'uuid_2',
            '',
        ];
        yield 'input with default string' => [
            'input_1',
            'testdefault',
        ];
        yield 'input with default integer' => [
            'input_2',
            5,
        ];
        yield 'input with default null' => [
            'input_3',
            null,
        ];
    }

    #[Test]
    #[DataProvider('newFieldArrayExpectedNoValues')]
    public function newFieldArrayNoDefaultValues(string $column): void
    {
        $GLOBALS['TCA'] = [
            'tx_my_testtable' => [
                'columns' => [
                    // invalid configurations
                    'input_1' => [
                        'config' => [
                            'type' => 'input',
                            'default' => null,
                        ],
                    ],
                    'input_2' => [
                        'config' => [
                            'type' => 'input',
                            'default' => null,
                            'nullable' => false,
                        ],
                    ],
                    'check_1' => [
                        'config' => [
                            'type' => 'check',
                            'default' => null,
                            'nullable' => true,
                        ],
                    ],
                    'file_1' => [
                        'config' => [
                            'type' => 'file',
                            'default' => 'nosuchconfig',
                        ],
                    ],
                    // valid config to ensure, that an array has been build
                    'input_3' => [
                        'config' => [
                            'type' => 'input',
                            'default' => 'test',
                        ],
                    ],
                ],
            ],
        ];
        $this->tcaSchemaFactory->load($GLOBALS['TCA'], true);
        $defaultValues = $this->subject->_call('newFieldArray', 'tx_my_testtable');
        self::assertEquals('test', $defaultValues['input_3']);
        self::assertArrayNotHasKey($column, $defaultValues);
    }

    public static function newFieldArrayExpectedNoValues(): iterable
    {
        yield 'input default null without nullable' => [
            'input_1',
        ];
        yield 'input default null with nullable false' => [
            'input_2',
        ];
        yield 'check with nullable true and default true' => [
            'check_1',
        ];
        yield 'file with default value' => [
            'file_1',
        ];
    }

    #[Test]
    public function newFieldArrayNoTcaTable(): void
    {
        $defaultValues = $this->subject->_call('newFieldArray', 'tx_my_testtable');
        self::assertEquals([], $defaultValues);
    }

    #[Test]
    public function newFieldArrayDefaultValues(): void
    {
        $GLOBALS['TCA'] = [
            'tx_my_testtable' => [
                'columns' => [
                    // invalid configurations
                    'input_1' => [
                        'config' => [
                            'type' => 'input',
                            'default' => null,
                            'nullable' => true,
                        ],
                    ],
                ],
            ],
        ];
        $this->tcaSchemaFactory->load($GLOBALS['TCA'], true);
        $this->subject->defaultValues['tx_my_testtable']['input_1'] = 'foo';
        $defaultValues = $this->subject->_call('newFieldArray', 'tx_my_testtable');
        self::assertEquals('foo', $defaultValues['input_1']);
    }
}
