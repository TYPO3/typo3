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

use Prophecy\Argument;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools;
use TYPO3\CMS\Core\Database\RelationHandler;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\DataHandling\DataHandlerCheckModifyAccessListHookInterface;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\SysLog\Action as SystemLogGenericAction;
use TYPO3\CMS\Core\SysLog\Error as SystemLogErrorClassification;
use TYPO3\CMS\Core\Tests\Unit\DataHandling\Fixtures\AllowAccessHookFixture;
use TYPO3\CMS\Core\Tests\Unit\DataHandling\Fixtures\InvalidHookFixture;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\TestingFramework\Core\AccessibleObjectInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class DataHandlerTest extends UnitTestCase
{
    use \Prophecy\PhpUnit\ProphecyTrait;
    /**
     * @var bool Reset singletons created by subject
     */
    protected bool $resetSingletonInstances = true;

    /**
     * A backup of registered singleton instances
     */
    protected array $singletonInstances = [];

    /**
     * @var DataHandler|\PHPUnit\Framework\MockObject\MockObject|AccessibleObjectInterface
     */
    protected $subject;

    /**
     * @var BackendUserAuthentication a mock logged-in back-end user
     */
    protected $backEndUser;

    /**
     * Set up the tests
     */
    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['TCA'] = [];
        $cacheManagerProphecy = $this->prophesize(CacheManager::class);
        GeneralUtility::setSingletonInstance(CacheManager::class, $cacheManagerProphecy->reveal());
        $cacheFrontendProphecy = $this->prophesize(FrontendInterface::class);
        $cacheManagerProphecy->getCache('runtime')->willReturn($cacheFrontendProphecy->reveal());
        $this->backEndUser = $this->createMock(BackendUserAuthentication::class);
        $this->subject = $this->getAccessibleMock(DataHandler::class, ['dummy']);
        $this->subject->start([], '', $this->backEndUser);
    }

    /**
     * @test
     */
    public function fixtureCanBeCreated(): void
    {
        self::assertInstanceOf(DataHandler::class, $this->subject);
    }

    //////////////////////////////////////////
    // Test concerning checkModifyAccessList
    //////////////////////////////////////////
    /**
     * @test
     */
    public function adminIsAllowedToModifyNonAdminTable(): void
    {
        $this->subject->admin = true;
        self::assertTrue($this->subject->checkModifyAccessList('tt_content'));
    }

    /**
     * @test
     */
    public function nonAdminIsNorAllowedToModifyNonAdminTable(): void
    {
        $this->subject->admin = false;
        self::assertFalse($this->subject->checkModifyAccessList('tt_content'));
    }

    /**
     * @test
     */
    public function nonAdminWithTableModifyAccessIsAllowedToModifyNonAdminTable(): void
    {
        $this->subject->admin = false;
        $this->backEndUser->groupData['tables_modify'] = 'tt_content';
        self::assertTrue($this->subject->checkModifyAccessList('tt_content'));
    }

    /**
     * @test
     */
    public function adminIsAllowedToModifyAdminTable(): void
    {
        $this->subject->admin = true;
        self::assertTrue($this->subject->checkModifyAccessList('be_users'));
    }

    /**
     * @test
     */
    public function nonAdminIsNotAllowedToModifyAdminTable(): void
    {
        $this->subject->admin = false;
        self::assertFalse($this->subject->checkModifyAccessList('be_users'));
    }

    /**
     * @test
     */
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
        $this->backEndUser->groupData['tables_modify'] = $tableName;
        self::assertFalse($this->subject->checkModifyAccessList($tableName));
    }

    /**
     * @test
     */
    public function checkValueInputEvalWithEvalDouble2(): void
    {
        $testData = [
            '-0,5' => '-0.50',
            '1000' => '1000.00',
            '1000,10' => '1000.10',
            '1000,0' => '1000.00',
            '600.000.000,00' => '600000000.00',
            '60aaa00' => '6000.00',
        ];
        foreach ($testData as $value => $expectedReturnValue) {
            $returnValue = $this->subject->checkValue_input_Eval($value, ['double2'], '');
            self::assertSame($expectedReturnValue, $returnValue['value']);
        }
    }

    /**
     * @return array
     */
    public function checkValueInputEvalWithEvalDatetimeDataProvider(): array
    {
        // Three elements: input, timezone of input, expected output (UTC)
        return [
            'timestamp is passed through, as it is UTC' => [
                1457103519, 'Europe/Berlin', 1457103519,
            ],
            'ISO date is interpreted as local date and is output as correct timestamp' => [
                '2017-06-07T00:10:00Z', 'Europe/Berlin', 1496787000,
            ],
        ];
    }

    /**
     * @test
     * @dataProvider checkValueInputEvalWithEvalDatetimeDataProvider
     */
    public function checkValueInputEvalWithEvalDatetime($input, $serverTimezone, $expectedOutput): void
    {
        $oldTimezone = date_default_timezone_get();
        date_default_timezone_set($serverTimezone);

        $output = $this->subject->checkValue_input_Eval($input, ['datetime'], '');

        // set before the assertion is performed, so it is restored even for failing tests
        date_default_timezone_set($oldTimezone);

        self::assertEquals($expectedOutput, $output['value']);
    }

    /**
     * @test
     */
    public function checkValueInputEvalWithSaltedPasswordKeepsExistingHash(): void
    {
        // Note the involved salted passwords are NOT mocked since the factory is static
        $subject = new DataHandler();
        $inputValue = '$1$GNu9HdMt$RwkPb28pce4nXZfnplVZY/';
        $result = $subject->checkValue_input_Eval($inputValue, ['saltedPassword'], '', 'be_users');
        self::assertSame($inputValue, $result['value']);
    }

    /**
     * @test
     */
    public function checkValueInputEvalWithSaltedPasswordReturnsHashForSaltedPassword(): void
    {
        // Note the involved salted passwords are NOT mocked since the factory is static
        $inputValue = 'myPassword';
        $subject = new DataHandler();
        $result = $subject->checkValue_input_Eval($inputValue, ['saltedPassword'], '', 'be_users');
        self::assertNotSame($inputValue, $result['value']);
    }

    /**
     * Data provider for inputValueCheckRecognizesStringValuesAsIntegerValuesCorrectly
     *
     * @return array
     */
    public function inputValuesStringsDataProvider(): array
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

    /**
     * @test
     * @dataProvider inputValuesStringsDataProvider
     * @param string $value
     * @param int $expectedReturnValue
     */
    public function inputValueCheckRecognizesStringValuesAsIntegerValuesCorrectly(string $value, int $expectedReturnValue): void
    {
        $tcaFieldConf = [
            'type' => 'input',
            'eval' => 'int',
            'range' => [
                'lower' => '-2000000',
                'upper' => '2000000',
            ],
        ];
        $returnValue = $this->subject->_call('checkValueForInput', $value, $tcaFieldConf, '', 0, 0, '');
        self::assertSame($expectedReturnValue, $returnValue['value']);
    }

    /**
     * Data provider for inputValuesRangeDoubleDataProvider
     *
     * @return array
     */
    public function inputValuesRangeDoubleDataProvider(): array
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
                0,
            ],
            '"0.5" is interpreted correctly as 0.5 and is equal to 0.5' => [
                '0.5',
                '0.50',
            ],
            '"39.9" is interpreted correctly as 39.9 and is equal to 39.9' => [
                '39.9',
                '39.90',
            ],
            '"42.3" is interpreted correctly as 42.3 but is greater then 42 and set to 42' => [
                '42.3',
                42,
            ],
        ];
    }

    /**
     * @test
     * @dataProvider inputValuesRangeDoubleDataProvider
     * @param string $value
     * @param int $expectedReturnValue
     */
    public function inputValueCheckRespectsRightLowerAndUpperLimitForDouble($value, $expectedReturnValue): void
    {
        $tcaFieldConf = [
            'type' => 'input',
            'eval' => 'double2',
            'range' => [
                'lower' => '0',
                'upper' => '42',
            ],
        ];
        $returnValue = $this->subject->_call('checkValueForInput', $value, $tcaFieldConf, '', 0, 0, '');
        self::assertSame($expectedReturnValue, $returnValue['value']);
    }

    /**
     * @test
     * @dataProvider inputValuesRangeDoubleDataProvider
     * @param string $value
     * @param int $expectedReturnValue
     */
    public function inputValueCheckRespectsRightLowerAndUpperLimitWithDefaultValueForDouble($value, $expectedReturnValue): void
    {
        $tcaFieldConf = [
            'type' => 'input',
            'eval' => 'double2',
            'range' => [
                'lower' => '0',
                'upper' => '42',
            ],
            'default' => 0,
        ];
        $returnValue = $this->subject->_call('checkValueForInput', $value, $tcaFieldConf, '', 0, 0, '');
        self::assertSame($expectedReturnValue, $returnValue['value']);
    }

    /**
     * @return array
     */
    public function inputValuesDataTimeDataProvider(): array
    {
        return [
            'undershot date adjusted' => [
                '2018-02-28T00:00:00Z',
                1519862400,
            ],
            'exact lower date accepted' => [
                '2018-03-01T00:00:00Z',
                1519862400,
            ],
            'exact upper date accepted' => [
                '2018-03-31T23:59:59Z',
                1522540799,
            ],
            'exceeded date adjusted' => [
                '2018-04-01T00:00:00Z',
                1522540799,
            ],
        ];
    }

    /**
     * @param string $value
     * @param int $expected
     *
     * @test
     * @dataProvider inputValuesDataTimeDataProvider
     */
    public function inputValueCheckRecognizesDateTimeValuesAsIntegerValuesCorrectly(string $value, int $expected): void
    {
        $tcaFieldConf = [
            'type' => 'input',
            'eval' => 'datetime',
            'range' => [
                // unix timestamp: 1519862400
                'lower' => gmmktime(0, 0, 0, 3, 1, 2018),
                // unix timestamp: 1522540799
                'upper' => gmmktime(23, 59, 59, 3, 31, 2018),
            ],
        ];

        // @todo Switch to UTC since otherwise DataHandler removes timezone offset
        $previousTimezone = date_default_timezone_get();
        date_default_timezone_set('UTC');

        $returnValue = $this->subject->_call('checkValueForInput', $value, $tcaFieldConf, '', 0, 0, '');

        date_default_timezone_set($previousTimezone);

        self::assertSame($expected, $returnValue['value']);
    }

    public function inputValueRangeCheckIsIgnoredWhenDefaultIsZeroAndInputValueIsEmptyDataProvider(): array
    {
        return [
            'Empty string returns empty string or the number zero' => [
                '',
                '',
                0,
            ],
            'Zero returns zero as a string or the number zero' => [
                0,
                '0',
                0,
            ],
            'Zero as a string returns zero as a string or the number zero' => [
                '0',
                '0',
                0,
            ],
        ];
    }

    /**
     * @dataProvider inputValueRangeCheckIsIgnoredWhenDefaultIsZeroAndInputValueIsEmptyDataProvider
     * @test
     * @param $inputValue
     * @param $expected
     * @param $expectedEvalInt
     */
    public function inputValueRangeCheckIsIgnoredWhenDefaultIsZeroAndInputValueIsEmpty($inputValue, $expected, $expectedEvalInt): void
    {
        $tcaFieldConf = [
            'type' => 'input',
            'eval' => 'datetime',
            'default' => 0,
            'range' => [
                'lower' => 1627077600,
            ],
        ];

        $tcaFieldConfEvalInt = [
            'type' => 'input',
            'eval' => 'datetime,int',
            'default' => '0',
            'range' => [
                'lower' => 1627077600,
            ],
        ];

        $returnValue = $this->subject->_call('checkValueForInput', $inputValue, $tcaFieldConf, '', 0, 0, '');
        self::assertSame($expected, $returnValue['value']);

        $returnValue = $this->subject->_call('checkValueForInput', $inputValue, $tcaFieldConfEvalInt, '', 0, 0, '');
        self::assertSame($expectedEvalInt, $returnValue['value']);
    }

    /**
     * @return array
     */
    public function inputValueCheckDoesNotCallGetDateTimeFormatsForNonDatetimeFieldsDataProvider(): array
    {
        return [
            'tca without dbType' => [
                [
                    'type' => 'input',
                ],
            ],
            'tca with dbType != date/datetime/time' => [
                [
                    'type' => 'input',
                    'dbType' => 'foo',
                ],
            ],
        ];
    }

    /**
     * @test
     * @param array $tcaFieldConf
     * @dataProvider inputValueCheckDoesNotCallGetDateTimeFormatsForNonDatetimeFieldsDataProvider
     */
    public function inputValueCheckDoesNotCallGetDateTimeFormatsForNonDatetimeFields(array $tcaFieldConf): void
    {
        $this->subject->_call('checkValueForInput', '', $tcaFieldConf, '', 0, 0, '');
    }

    /**
     * @returns array
     */
    public function inputValueCheckDbtypeIsIndependentFromTimezoneDataProvider(): array
    {
        return [
            // Values of this kind are passed in from the inputDateTime control
            'time from inputDateTime' => [
                '1970-01-01T18:54:00Z',
                'time',
                '18:54:00',
            ],
            'date from inputDateTime' => [
                '2020-11-25T00:00:00Z',
                'date',
                '2020-11-25',
            ],
            'datetime from inputDateTime' => [
                '2020-11-25T18:54:00Z',
                'datetime',
                '2020-11-25 18:54:00',
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
        ];
    }

    /**
     * Tests whether native dbtype inputs are parsed independent from the server timezone.
     *
     * @param $value
     * @param $dbtype
     * @param $expectedOutput
     * @test
     * @dataProvider inputValueCheckDbtypeIsIndependentFromTimezoneDataProvider
     */
    public function inputValueCheckDbtypeIsIndependentFromTimezone($value, $dbtype, $expectedOutput): void
    {
        $tcaFieldConf = [
            'type' => 'input',
            'dbType' => $dbtype,
        ];

        $oldTimezone = date_default_timezone_get();
        date_default_timezone_set('Europe/Berlin');

        $returnValue = $this->subject->_call('checkValueForInput', $value, $tcaFieldConf, '', 0, 0, '');

        // set before the assertion is performed, so it is restored even for failing tests
        date_default_timezone_set($oldTimezone);

        self::assertEquals($expectedOutput, $returnValue['value']);
    }

    ///////////////////////////////////////////
    // Tests concerning checkModifyAccessList
    ///////////////////////////////////////////
    //
    /**
     * Tests whether a wrong interface on the 'checkModifyAccessList' hook throws an exception.
     * @test
     */
    public function doesCheckModifyAccessListThrowExceptionOnWrongHookInterface(): void
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionCode(1251892472);

        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['checkModifyAccessList'][] = InvalidHookFixture::class;
        $this->subject->checkModifyAccessList('tt_content');
    }

    /**
     * Tests whether the 'checkModifyAccessList' hook is called correctly.
     *
     * @test
     */
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
        $this->subject->checkModifyAccessList('tt_content');
    }

    /**
     * Tests whether the 'checkModifyAccessList' hook modifies the $accessAllowed variable.
     *
     * @test
     */
    public function doesCheckModifyAccessListHookModifyAccessAllowed(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['checkModifyAccessList'][] = AllowAccessHookFixture::class;
        self::assertTrue($this->subject->checkModifyAccessList('tt_content'));
    }

    /////////////////////////////////////
    // Tests concerning process_datamap
    /////////////////////////////////////
    /**
     * @test
     */
    public function processDatamapForFrozenNonZeroWorkspaceReturnsFalse(): void
    {
        /** @var DataHandler $subject */
        $subject = $this->getMockBuilder(DataHandler::class)
            ->onlyMethods(['log'])
            ->getMock();
        $this->backEndUser->workspace = 1;
        $this->backEndUser->workspaceRec = ['freeze' => true];
        $subject->BE_USER = $this->backEndUser;
        self::assertFalse($subject->process_datamap());
    }

    /**
     * @test
     */
    public function doesCheckFlexFormValueHookGetsCalled(): void
    {
        $hookClass = \stdClass::class;
        $hookMock = $this->getMockBuilder($hookClass)
            ->addMethods(['checkFlexFormValue_beforeMerge'])
            ->getMock();
        $hookMock->expects(self::once())->method('checkFlexFormValue_beforeMerge');
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['checkFlexFormValue'][] = $hookClass;
        GeneralUtility::addInstance($hookClass, $hookMock);
        $flexFormToolsProphecy = $this->prophesize(FlexFormTools::class);
        $flexFormToolsProphecy->getDataStructureIdentifier(Argument::cetera())->willReturn('anIdentifier');
        $flexFormToolsProphecy->parseDataStructureByIdentifier('anIdentifier')->willReturn([]);
        GeneralUtility::addInstance(FlexFormTools::class, $flexFormToolsProphecy->reveal());
        $this->subject->_call('checkValueForFlex', [], [], [], '', 0, '', '', 0, 0, 0, '');
    }

    /////////////////////////////////////
    // Tests concerning log
    /////////////////////////////////////
    /**
     * @test
     */
    public function logCallsWriteLogOfBackendUserIfLoggingIsEnabled(): void
    {
        $backendUser = $this->createMock(BackendUserAuthentication::class);
        $backendUser->expects(self::once())->method('writelog');
        $this->subject->enableLogging = true;
        $this->subject->BE_USER = $backendUser;
        $this->subject->log('', 23, SystemLogGenericAction::UNDEFINED, 42, SystemLogErrorClassification::MESSAGE, 'details');
    }

    /**
     * @test
     */
    public function logDoesNotCallWriteLogOfBackendUserIfLoggingIsDisabled(): void
    {
        $backendUser = $this->createMock(BackendUserAuthentication::class);
        $backendUser->expects(self::never())->method('writelog');
        $this->subject->enableLogging = false;
        $this->subject->BE_USER = $backendUser;
        $this->subject->log('', 23, SystemLogGenericAction::UNDEFINED, 42, SystemLogErrorClassification::MESSAGE, 'details');
    }

    /**
     * @test
     */
    public function logAddsEntryToLocalErrorLogArray(): void
    {
        $backendUser = $this->createMock(BackendUserAuthentication::class);
        $this->subject->BE_USER = $backendUser;
        $this->subject->enableLogging = true;
        $this->subject->errorLog = [];
        $logDetailsUnique = StringUtility::getUniqueId('details');
        $this->subject->log('', 23, SystemLogGenericAction::UNDEFINED, 42, SystemLogErrorClassification::USER_ERROR, $logDetailsUnique);
        self::assertStringEndsWith($logDetailsUnique, $this->subject->errorLog[0]);
    }

    /**
     * @test
     */
    public function logFormatsDetailMessageWithAdditionalDataInLocalErrorArray(): void
    {
        $backendUser = $this->createMock(BackendUserAuthentication::class);
        $this->subject->BE_USER = $backendUser;
        $this->subject->enableLogging = true;
        $this->subject->errorLog = [];
        $logDetails = StringUtility::getUniqueId('details');
        $this->subject->log('', 23, SystemLogGenericAction::UNDEFINED, 42, SystemLogErrorClassification::USER_ERROR, '%1$s' . $logDetails . '%2$s', -1, ['foo', 'bar']);
        $expected = 'foo' . $logDetails . 'bar';
        self::assertStringEndsWith($expected, $this->subject->errorLog[0]);
    }

    /**
     * @param bool $expected
     * @param string $submittedValue
     * @param string $storedValue
     * @param string $storedType
     * @param bool $allowNull
     *
     * @dataProvider equalSubmittedAndStoredValuesAreDeterminedDataProvider
     * @test
     */
    public function equalSubmittedAndStoredValuesAreDetermined($expected, $submittedValue, $storedValue, $storedType, $allowNull): void
    {
        $result = \Closure::bind(function () use ($submittedValue, $storedValue, $storedType, $allowNull) {
            return $this->isSubmittedValueEqualToStoredValue($submittedValue, $storedValue, $storedType, $allowNull);
        }, $this->subject, DataHandler::class)();
        self::assertEquals($expected, $result);
    }

    /**
     * @return array
     */
    public function equalSubmittedAndStoredValuesAreDeterminedDataProvider(): array
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

    /**
     * @test
     */
    public function deletePagesOnRootLevelIsDenied(): void
    {
        /** @var DataHandler|\PHPUnit\Framework\MockObject\MockObject|AccessibleObjectInterface $dataHandlerMock */
        $dataHandlerMock = $this->getMockBuilder(DataHandler::class)
            ->onlyMethods(['canDeletePage', 'log'])
            ->getMock();
        $dataHandlerMock
            ->expects(self::never())
            ->method('canDeletePage');
        $dataHandlerMock
            ->expects(self::once())
            ->method('log')
            ->with('pages', 0, 3, 0, 2, 'Deleting all pages starting from the root-page is disabled.', -1, [], 0);

        $dataHandlerMock->deletePages(0);
    }

    /**
     * @test
     */
    public function deleteRecord_procBasedOnFieldTypeRespectsEnableCascadingDelete(): void
    {
        $table = StringUtility::getUniqueId('foo_');
        $conf = [
            'type' => 'inline',
            'foreign_table' => StringUtility::getUniqueId('foreign_foo_'),
            'behaviour' => [
                'enableCascadingDelete' => 0,
            ],
        ];

        /** @var \TYPO3\CMS\Core\Database\RelationHandler $mockRelationHandler */
        $mockRelationHandler = $this->createMock(RelationHandler::class);
        $mockRelationHandler->itemArray = [
            '1' => ['table' => StringUtility::getUniqueId('bar_'), 'id' => 67],
        ];

        /** @var DataHandler|\PHPUnit\Framework\MockObject\MockObject|AccessibleObjectInterface $mockDataHandler */
        $mockDataHandler = $this->getAccessibleMock(DataHandler::class, ['getInlineFieldType', 'deleteAction', 'createRelationHandlerInstance'], [], '', false);
        $mockDataHandler->expects(self::once())->method('getInlineFieldType')->willReturn('field');
        $mockDataHandler->expects(self::once())->method('createRelationHandlerInstance')->willReturn($mockRelationHandler);
        $mockDataHandler->expects(self::never())->method('deleteAction');
        $mockDataHandler->deleteRecord_procBasedOnFieldType($table, 42, 'bar', $conf);
    }

    /**
     * @return array
     */
    public function checkValue_checkReturnsExpectedValuesDataProvider(): array
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

    /**
     * @param string $value
     * @param string $expectedValue
     *
     * @dataProvider checkValue_checkReturnsExpectedValuesDataProvider
     * @test
     * @todo Specifying the datatype in the parameter list results in test bench failures (runTest.ch)
     */
    public function checkValue_checkReturnsExpectedValues($value, $expectedValue): void
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

    /**
     * @test
     */
    public function checkValueForInputConvertsNullToEmptyString(): void
    {
        $expectedResult = ['value' => ''];
        self::assertSame($expectedResult, $this->subject->_call('checkValueForInput', null, ['type' => 'input', 'max' => 40], 'tt_content', 'NEW55c0e67f8f4d32.04974534', 89, 'table_caption'));
    }

    /**
     * @param mixed $value
     * @param array $configuration
     * @param int|string $expected
     * @test
     * @dataProvider referenceValuesAreCastedDataProvider
     */
    public function referenceValuesAreCasted($value, array $configuration, $expected): void
    {
        self::assertEquals(
            $expected,
            $this->subject->_call('castReferenceValue', $value, $configuration)
        );
    }

    /**
     * @return array
     */
    public function referenceValuesAreCastedDataProvider(): array
    {
        return [
            'all empty' => [
                '', [], '',
            ],
            'cast zero with MM table' => [
                '', ['MM' => 'table'], 0,
            ],
            'cast zero with MM table with default value' => [
                '', ['MM' => 'table', 'default' => 13], 0,
            ],
            'cast zero with foreign field' => [
                '', ['foreign_field' => 'table', 'default' => 13], 0,
            ],
            'cast zero with foreign field with default value' => [
                '', ['foreign_field' => 'table'], 0,
            ],
            'pass zero' => [
                '0', [], '0',
            ],
            'pass value' => [
                '1', ['default' => 13], '1',
            ],
            'use default value' => [
                '', ['default' => 13], 13,
            ],
        ];
    }

    /**
     * @return array
     */
    public function clearPrefixFromValueRemovesPrefixDataProvider(): array
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

    /**
     * @test
     * @dataProvider clearPrefixFromValueRemovesPrefixDataProvider
     * @param string $input
     * @param string $expected
     */
    public function clearPrefixFromValueRemovesPrefix(string $input, string $expected): void
    {
        $languageServiceProphecy = $this->prophesize(LanguageService::class);
        $languageServiceProphecy->sL('testLabel')->willReturn('(copy %s)');
        $GLOBALS['LANG'] = $languageServiceProphecy->reveal();
        $GLOBALS['TCA']['testTable']['ctrl']['prependAtCopy'] = 'testLabel';
        self::assertEquals($expected, (new DataHandler())->clearPrefixFromValue('testTable', $input));
    }
}
