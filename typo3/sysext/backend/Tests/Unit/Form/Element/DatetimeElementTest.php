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

namespace TYPO3\CMS\Backend\Tests\Unit\Form\Element;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Form\Element\DatetimeElement;
use TYPO3\CMS\Backend\Form\NodeExpansion\FieldInformation;
use TYPO3\CMS\Backend\Form\NodeFactory;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class DatetimeElementTest extends UnitTestCase
{
    /**
     * @var string Selected timezone backup
     */
    protected string $timezoneBackup = '';

    /**
     * We're fiddling with hard timestamps in the tests, but time methods in
     * the system under test do use timezone settings. Therefore we backup the
     * current timezone setting, set it to UTC explicitly and reconstitute it
     * again in tearDown()
     */
    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['BE_USER'] = new BackendUserAuthentication();
        $this->timezoneBackup = date_default_timezone_get();
    }

    protected function tearDown(): void
    {
        date_default_timezone_set($this->timezoneBackup);
        parent::tearDown();
    }

    /**
     * Data provider for renderAppliesCorrectTimestampConversion
     */
    public static function renderAppliesCorrectTimestampConversionDataProvider(): array
    {
        // Three elements: input (UTC), timezone of output, expected output
        return [
            // German standard time (without DST) is one hour ahead of UTC
            'date in 2016 in German timezone' => [
                1457103519, 'Europe/Berlin', '2016-03-04T15:58:39+00:00',
            ],
            'date in 1969 in German timezone' => [
                -7200, 'Europe/Berlin', '1969-12-31T23:00:00+00:00',
            ],
            // Los Angeles is 8 hours behind UTC
            'date in 2016 in Los Angeles timezone' => [
                1457103519, 'America/Los_Angeles', '2016-03-04T06:58:39+00:00',
            ],
            'date in UTC' => [
                1457103519, 'UTC', '2016-03-04T14:58:39+00:00',
            ],
        ];
    }

    #[DataProvider('renderAppliesCorrectTimestampConversionDataProvider')]
    #[Test]
    public function renderAppliesCorrectTimestampConversion(int $input, string $serverTimezone, string $expectedOutput): void
    {
        date_default_timezone_set($serverTimezone);
        $data = [
            'tableName' => 'table_foo',
            'fieldName' => 'field_bar',
            'databaseRow' => [
                'uid' => 5,
            ],
            'parameterArray' => [
                'tableName' => 'table_foo',
                'fieldName' => 'field_bar',
                'fieldConf' => [
                    'label' => 'foo',
                    'config' => [
                        'type' => 'datetime',
                        'dbType' => 'datetime',
                    ],
                ],
                'itemFormElName' => 'myItemFormElName',
                'itemFormElValue' => $input,
            ],
        ];
        $iconFactoryMock = $this->createMock(IconFactory::class);
        $iconMock = $this->createMock(Icon::class);
        $iconMock->method('render')->willReturn('');
        $iconFactoryMock->method('getIcon')->with(self::anything())->willReturn($iconMock);
        $nodeFactoryMock = $this->createMock(NodeFactory::class);
        $fieldInformationMock = $this->createMock(FieldInformation::class);
        $fieldInformationMock->method('render')->willReturn(['html' => '']);
        $nodeFactoryMock->method('create')->with(self::anything())->willReturn($fieldInformationMock);
        $GLOBALS['LANG'] = $this->createMock(LanguageService::class);

        $subject = new DatetimeElement($iconFactoryMock);
        $subject->injectNodeFactory($nodeFactoryMock);
        $subject->setData($data);
        $result = $subject->render();

        self::assertStringContainsString('<input type="hidden" name="myItemFormElName" value="' . $expectedOutput . '" />', $result['html']);
    }

    /**
     * Data provider for renderAppliesCorrectTimestampConversionOnHourAndMinuteNullable
     *
     * On NULLABLE input fields, we can distinguish a "00:00" (or "0") input from "" (NULL).
     * This is where the expectation is, that an entered "00:00" input will be
     * properly restored as a timezone input.
     */
    public static function renderAppliesCorrectTimestampConversionOnHourAndMinuteNullableDataProvider(): array
    {
        // Three elements: input (UTC), timezone of output, expected output
        // NOTE: Timezone is always UTC because for HOUR+MINUTE input we
        //       always go from 1970-01-01, and timezones are irrelevant
        //       (if timezones were factored in, the timestamps of "expected"
        //       would not match, because DatetimeElement() performs timezone
        //       offsetting on 'datetime', but NOT on 'time' or 'timesec').
        return [
            'zero-start date in UTC, midnight' => [
                3519, 'UTC', '1970-01-01T00:58:39+00:00',
            ],
            'zero-start date in UTC, midday' => [
                53919, 'UTC', '1970-01-01T14:58:39+00:00',
            ],
            'zero-start date in UTC, origin' => [
                0, 'UTC', '1970-01-01T00:00:00+00:00',
            ],
            'zero-start date in UTC, origin + 1 sec' => [
                1, 'UTC', '1970-01-01T00:00:01+00:00',
            ],
            'zero-start date in UTC, origin + 1 min' => [
                60, 'UTC', '1970-01-01T00:01:00+00:00',
            ],
        ];
    }

    #[DataProvider('renderAppliesCorrectTimestampConversionOnHourAndMinuteNullableDataProvider')]
    #[Test]
    public function renderAppliesCorrectTimestampConversionOnHourAndMinuteNullable(int $input, string $serverTimezone, string $expectedOutput): void
    {
        date_default_timezone_set($serverTimezone);
        $data = [
            'tableName' => 'table_foo',
            'fieldName' => 'field_bar',
            'databaseRow' => [
                'uid' => 5,
            ],
            'parameterArray' => [
                'tableName' => 'table_foo',
                'fieldName' => 'field_bar',
                'fieldConf' => [
                    'label' => 'foo',
                    'config' => [
                        'type' => 'datetime',
                        'dbType' => 'datetime',
                        'format' => 'time',
                        'nullable' => true,
                    ],
                ],
                'itemFormElName' => 'myItemFormElName',
                'itemFormElValue' => $input,
            ],
        ];
        $iconFactoryMock = $this->createMock(IconFactory::class);
        $iconMock = $this->createMock(Icon::class);
        $iconMock->method('render')->willReturn('');
        $iconFactoryMock->method('getIcon')->with(self::anything())->willReturn($iconMock);
        $nodeFactoryMock = $this->createMock(NodeFactory::class);
        $fieldInformationMock = $this->createMock(FieldInformation::class);
        $fieldInformationMock->method('render')->willReturn(['html' => '']);
        $nodeFactoryMock->method('create')->with(self::anything())->willReturn($fieldInformationMock);
        $GLOBALS['LANG'] = $this->createMock(LanguageService::class);

        $subject = new DatetimeElement($iconFactoryMock);
        $subject->injectNodeFactory($nodeFactoryMock);
        $subject->setData($data);
        $result = $subject->render();

        self::assertStringContainsString('<input type="hidden" name="myItemFormElName" value="' . $expectedOutput . '" />', $result['html']);
    }

    /**
     * Data provider for renderAppliesCorrectTimestampConversionOnHourAndMinuteNotNullable
     *
     * On NOT NULLABLE input fields, we can NOT distinguish a "00:00" or empty input from
     * a stored "0" value. We cannot know, if this TCA has allowed to save an entry that
     * was meant as "00:00" or meant as "empty input".
     *
     * Thus, the expectation here is to present a "0" input as an EMPTY input. Integrations/
     * Developers are required to use NULLABLE input fields if they want to differentiate
     * between "entered, and 00:00" or "not entered". This is the way.
     */
    public static function renderAppliesCorrectTimestampConversionOnHourAndMinuteNotNullableDataProvider(): array
    {
        // For notation description, see renderAppliesCorrectTimestampConversionOnHourAndMinuteDataNullableProvider
        return [
            'zero-start date in UTC, midnight' => [
                3519, 'UTC', '1970-01-01T00:58:39+00:00',
            ],
            'zero-start date in UTC, midday' => [
                53919, 'UTC', '1970-01-01T14:58:39+00:00',
            ],
            'zero-start date in UTC, origin' => [
                // This is the important difference between NotNullable and Nullable!
                0, 'UTC', '',
            ],
            'zero-start date in UTC, origin + 1 sec' => [
                1, 'UTC', '1970-01-01T00:00:01+00:00',
            ],
            'zero-start date in UTC, origin + 1 min' => [
                60, 'UTC', '1970-01-01T00:01:00+00:00',
            ],
        ];
    }

    #[DataProvider('renderAppliesCorrectTimestampConversionOnHourAndMinuteNotNullableDataProvider')]
    #[Test]
    public function renderAppliesCorrectTimestampConversionOnHourAndMinuteNotNullable(string|int $input, string $serverTimezone, string $expectedOutput): void
    {
        date_default_timezone_set($serverTimezone);
        $data = [
            'tableName' => 'table_foo',
            'fieldName' => 'field_bar',
            'databaseRow' => [
                'uid' => 5,
            ],
            'parameterArray' => [
                'tableName' => 'table_foo',
                'fieldName' => 'field_bar',
                'fieldConf' => [
                    'label' => 'foo',
                    'config' => [
                        'type' => 'datetime',
                        'dbType' => 'datetime',
                        'format' => 'time',
                        'nullable' => false,
                    ],
                ],
                'itemFormElName' => 'myItemFormElName',
                'itemFormElValue' => $input,
            ],
        ];
        $iconFactoryMock = $this->createMock(IconFactory::class);
        $iconMock = $this->createMock(Icon::class);
        $iconMock->method('render')->willReturn('');
        $iconFactoryMock->method('getIcon')->with(self::anything())->willReturn($iconMock);
        $nodeFactoryMock = $this->createMock(NodeFactory::class);
        $fieldInformationMock = $this->createMock(FieldInformation::class);
        $fieldInformationMock->method('render')->willReturn(['html' => '']);
        $nodeFactoryMock->method('create')->with(self::anything())->willReturn($fieldInformationMock);
        $GLOBALS['LANG'] = $this->createMock(LanguageService::class);

        $subject = new DatetimeElement($iconFactoryMock);
        $subject->injectNodeFactory($nodeFactoryMock);
        $subject->setData($data);
        $result = $subject->render();

        self::assertStringContainsString('<input type="hidden" name="myItemFormElName" value="' . $expectedOutput . '" />', $result['html']);
    }
}
