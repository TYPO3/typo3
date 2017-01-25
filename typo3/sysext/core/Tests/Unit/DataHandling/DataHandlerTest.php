<?php
namespace TYPO3\CMS\Core\Tests\Unit\DataHandler;

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

use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Tests\AccessibleObjectInterface;
use TYPO3\CMS\Core\Tests\Unit\DataHandling\Fixtures\AllowAccessHookFixture;
use TYPO3\CMS\Core\Tests\Unit\DataHandling\Fixtures\InvalidHookFixture;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Test case
 */
class DataHandlerTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @var array A backup of registered singleton instances
     */
    protected $singletonInstances = [];

    /**
     * @var DataHandler|\PHPUnit_Framework_MockObject_MockObject|AccessibleObjectInterface
     */
    protected $subject;

    /**
     * @var BackendUserAuthentication a mock logged-in back-end user
     */
    protected $backEndUser;

    /**
     * @var DatabaseConnection|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockDatabaseConnection;

    /**
     * Set up the tests
     */
    protected function setUp()
    {
        $GLOBALS['TCA'] = [];
        $this->singletonInstances = GeneralUtility::getSingletonInstances();
        $this->backEndUser = $this->getMock(BackendUserAuthentication::class);
        $this->mockDatabaseConnection = $this->getMock(DatabaseConnection::class, [], [], '', false);
        $GLOBALS['TYPO3_DB'] = $this->mockDatabaseConnection;
        $this->subject = $this->getAccessibleMock(DataHandler::class, ['dummy']);
        $this->subject->start([], '', $this->backEndUser);
    }

    /**
     * Tear down the tests
     */
    protected function tearDown()
    {
        GeneralUtility::resetSingletonInstances($this->singletonInstances);
        parent::tearDown();
    }

    //////////////////////////////////////
    // Tests for the basic functionality
    //////////////////////////////////////
    /**
     * @test
     */
    public function fixtureCanBeCreated()
    {
        $this->assertTrue($this->subject instanceof DataHandler);
    }

    //////////////////////////////////////////
    // Test concerning checkModifyAccessList
    //////////////////////////////////////////
    /**
     * @test
     */
    public function adminIsAllowedToModifyNonAdminTable()
    {
        $this->subject->admin = true;
        $this->assertTrue($this->subject->checkModifyAccessList('tt_content'));
    }

    /**
     * @test
     */
    public function nonAdminIsNorAllowedToModifyNonAdminTable()
    {
        $this->subject->admin = false;
        $this->assertFalse($this->subject->checkModifyAccessList('tt_content'));
    }

    /**
     * @test
     */
    public function nonAdminWithTableModifyAccessIsAllowedToModifyNonAdminTable()
    {
        $this->subject->admin = false;
        $this->backEndUser->groupData['tables_modify'] = 'tt_content';
        $this->assertTrue($this->subject->checkModifyAccessList('tt_content'));
    }

    /**
     * @test
     */
    public function adminIsAllowedToModifyAdminTable()
    {
        $this->subject->admin = true;
        $this->assertTrue($this->subject->checkModifyAccessList('be_users'));
    }

    /**
     * @test
     */
    public function nonAdminIsNotAllowedToModifyAdminTable()
    {
        $this->subject->admin = false;
        $this->assertFalse($this->subject->checkModifyAccessList('be_users'));
    }

    /**
     * @test
     */
    public function nonAdminWithTableModifyAccessIsNotAllowedToModifyAdminTable()
    {
        $tableName = $this->getUniqueId('aTable');
        $GLOBALS['TCA'] = [
            $tableName => [
                'ctrl' => [
                    'adminOnly' => true,
                ],
            ],
        ];
        $this->subject->admin = false;
        $this->backEndUser->groupData['tables_modify'] = $tableName;
        $this->assertFalse($this->subject->checkModifyAccessList($tableName));
    }

    /**
     * @test
     */
    public function evalCheckValueDouble2()
    {
        $testData = [
            '-0,5' => '-0.50',
            '1000' => '1000.00',
            '1000,10' => '1000.10',
            '1000,0' => '1000.00',
            '600.000.000,00' => '600000000.00',
            '60aaa00' => '6000.00'
        ];
        foreach ($testData as $value => $expectedReturnValue) {
            $returnValue = $this->subject->checkValue_input_Eval($value, ['double2'], '');
            $this->assertSame($returnValue['value'], $expectedReturnValue);
        }
    }

    public function dataProviderDatetime()
    {
        // Three elements: input, timezone of input, expected output (UTC)
        return [
            // German standard time (without DST) is one hour ahead of UTC
            'date in 2016 in German timezone' => [
                1457103519, 'Europe/Berlin', 1457103519 - 3600
            ],
            'date in 1969 in German timezone' => [
                -7200, 'Europe/Berlin', -10800
            ],
            // Los Angeles is 8 hours behind UTC
            'date in 2016 in Los Angeles timezone' => [
                1457103519, 'America/Los_Angeles', 1457103519 + 28800
            ],
            'date in UTC' => [
                1457103519, 'UTC', 1457103519
            ]
        ];
    }

    /**
     * @test
     * @dataProvider dataProviderDatetime
     */
    public function evalCheckValueDatetime($input, $serverTimezone, $expectedOutput)
    {
        $oldTimezone = date_default_timezone_get();
        date_default_timezone_set($serverTimezone);

        $output = $this->subject->checkValue_input_Eval($input, ['datetime'], '');

        // set before the assertion is performed, so it is restored even for failing tests
        date_default_timezone_set($oldTimezone);

        $this->assertEquals($expectedOutput, $output['value']);
    }

    /**
     * Data provider for inputValueCheckRecognizesStringValuesAsIntegerValuesCorrectly
     *
     * @return array
     */
    public function inputValuesStringsDataProvider()
    {
        return [
            '"0" returns zero as integer' => [
                '0',
                0
            ],
            '"-1999999" is interpreted correctly as -1999999 and is lot lower than -200000' => [
                '-1999999',
                -1999999
            ],
            '"3000000" is interpreted correctly as 3000000 but is higher then 200000 and set to 200000' => [
                '3000000',
                2000000
            ],
        ];
    }

    /**
     * @test
     * @dataProvider inputValuesStringsDataProvider
     * @param string $value
     * @param int $expectedReturnValue
     */
    public function inputValueCheckRecognizesStringValuesAsIntegerValuesCorrectly($value, $expectedReturnValue)
    {
        $tcaFieldConf = [
            'input' => [],
            'eval' => 'int',
            'range' => [
                'lower' => '-2000000',
                'upper' => '2000000'
            ]
        ];
        $returnValue = $this->subject->_call('checkValueForInput', $value, $tcaFieldConf, '', 0, 0, '');
        $this->assertSame($returnValue['value'], $expectedReturnValue);
    }

    /**
     * @return array
     */
    public function inputValueCheckCallsGetDateTimeFormatsForDatetimeFieldsDataProvider()
    {
        return [
            'dbType = date' => [
                'date'
            ],
            'dbType = datetime' => [
                'datetime'
            ]
        ];
    }

    /**
     * @test
     * @dataProvider inputValueCheckCallsGetDateTimeFormatsForDatetimeFieldsDataProvider
     * @param string $dbType
     */
    public function inputValueCheckCallsNotGetDateTimeFormatsForDatetimeFieldsWithEmptyValue($dbType)
    {
        $tcaFieldConf = [
            'input' => [],
            'dbType' => $dbType
        ];
        $this->mockDatabaseConnection->expects($this->never())->method('getDateTimeFormats');
        $this->subject->_call('checkValueForInput', '', $tcaFieldConf, '', 0, 0, '');
    }

    /**
     * @test
     * @dataProvider inputValueCheckCallsGetDateTimeFormatsForDatetimeFieldsDataProvider
     * @param string $dbType
     */
    public function inputValueCheckCallsGetDateTimeFormatsForDatetimeFieldsWithNonEmptyValue($dbType)
    {
        $dateTimeFormats = [
            'date' => [
                'empty' => '0000-00-00',
                'format' => 'Y-m-d'
            ],
            'datetime' => [
                'empty' => '0000-00-00 00:00:00',
                'format' => 'Y-m-d H:i:s'
            ]
        ];
        $tcaFieldConf = [
            'input' => [],
            'dbType' => $dbType
        ];
        $this->mockDatabaseConnection->expects($this->once())->method('getDateTimeFormats')->willReturn($dateTimeFormats);
        $this->subject->_call('checkValueForInput', $dateTimeFormats[$dbType]['empty'], $tcaFieldConf, '', 0, 0, '');
    }

    /**
     * @return array
     */
    public function inputValueCheckDoesNotCallGetDateTimeFormatsForNonDatetimeFieldsDataProvider()
    {
        return [
            'tca without dbType' => [
                [
                    'input' => []
                ]
            ],
            'tca with dbType != date/datetime' => [
                [
                    'input' => [],
                    'dbType' => 'foo'
                ]
            ]
        ];
    }

    /**
     * @test
     * @param array $tcaFieldConf
     * @dataProvider inputValueCheckDoesNotCallGetDateTimeFormatsForNonDatetimeFieldsDataProvider
     */
    public function inputValueCheckDoesNotCallGetDateTimeFormatsForNonDatetimeFields($tcaFieldConf)
    {
        $this->mockDatabaseConnection->expects($this->never())->method('getDateTimeFormats');
        $this->subject->_call('checkValueForInput', '', $tcaFieldConf, '', 0, 0, '');
    }

    ///////////////////////////////////////////
    // Tests concerning checkModifyAccessList
    ///////////////////////////////////////////
    //
    /**
     * Tests whether a wrong interface on the 'checkModifyAccessList' hook throws an exception.
     *
     * @test
     * @expectedException \UnexpectedValueException
     */
    public function doesCheckModifyAccessListThrowExceptionOnWrongHookInterface()
    {
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['checkModifyAccessList'][] = InvalidHookFixture::class;
        $this->subject->checkModifyAccessList('tt_content');
    }

    /**
     * Tests whether the 'checkModifyAccessList' hook is called correctly.
     *
     * @test
     */
    public function doesCheckModifyAccessListHookGetsCalled()
    {
        $hookClass = $this->getUniqueId('tx_coretest');
        $hookMock = $this->getMock(\TYPO3\CMS\Core\DataHandling\DataHandlerCheckModifyAccessListHookInterface::class, ['checkModifyAccessList'], [], $hookClass);
        $hookMock->expects($this->once())->method('checkModifyAccessList');
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['checkModifyAccessList'][] = $hookClass;
        $GLOBALS['T3_VAR']['getUserObj'][$hookClass] = $hookMock;
        $this->subject->checkModifyAccessList('tt_content');
    }

    /**
     * Tests whether the 'checkModifyAccessList' hook modifies the $accessAllowed variable.
     *
     * @test
     */
    public function doesCheckModifyAccessListHookModifyAccessAllowed()
    {
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['checkModifyAccessList'][] = AllowAccessHookFixture::class;
        $this->assertTrue($this->subject->checkModifyAccessList('tt_content'));
    }

    /////////////////////////////////////
    // Tests concerning process_datamap
    /////////////////////////////////////
    /**
     * @test
     */
    public function processDatamapForFrozenNonZeroWorkspaceReturnsFalse()
    {
        /** @var DataHandler $subject */
        $subject = $this->getMock(DataHandler::class, ['newlog']);
        $this->backEndUser->workspace = 1;
        $this->backEndUser->workspaceRec = ['freeze' => true];
        $subject->BE_USER = $this->backEndUser;
        $this->assertFalse($subject->process_datamap());
    }

    /**
     * @test
     */
    public function processDatamapWhenEditingRecordInWorkspaceCreatesNewRecordInWorkspace()
    {
        // Unset possible hooks on method under test
        // @TODO: Can be removed if unit test boostrap is fixed to not load LocalConfiguration anymore
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'] = [];

        $GLOBALS['TCA'] = [
            'pages' => [
                'columns' => [],
            ],
        ];

        /** @var $subject DataHandler|\PHPUnit_Framework_MockObject_MockObject */
        $subject = $this->getMock(
            DataHandler::class,
            ['newlog', 'checkModifyAccessList', 'tableReadOnly', 'checkRecordUpdateAccess']
        );

        $subject->bypassWorkspaceRestrictions = false;
        $subject->datamap = [
            'pages' => [
                '1' => [
                    'header' => 'demo'
                ]
            ]
        ];
        $subject->expects($this->once())->method('checkModifyAccessList')->with('pages')->will($this->returnValue(true));
        $subject->expects($this->once())->method('tableReadOnly')->with('pages')->will($this->returnValue(false));
        $subject->expects($this->once())->method('checkRecordUpdateAccess')->will($this->returnValue(true));

        /** @var BackendUserAuthentication|\PHPUnit_Framework_MockObject_MockObject $backEndUser */
        $backEndUser = $this->getMock(BackendUserAuthentication::class);
        $backEndUser->workspace = 1;
        $backEndUser->workspaceRec = ['freeze' => false];
        $backEndUser->expects($this->once())->method('workspaceAllowAutoCreation')->will($this->returnValue(true));
        $backEndUser->expects($this->once())->method('workspaceCannotEditRecord')->will($this->returnValue(true));
        $backEndUser->expects($this->once())->method('recordEditAccessInternals')->with('pages', 1)->will($this->returnValue(true));
        $subject->BE_USER = $backEndUser;
        $createdTceMain = $this->getMock(DataHandler::class, []);
        $createdTceMain->expects($this->once())->method('start')->with([], [
            'pages' => [
                1 => [
                    'version' => [
                        'action' => 'new',
                        'treeLevels' => -1,
                        'label' => 'Auto-created for WS #1'
                    ]
                ]
            ]
        ]);
        $createdTceMain->expects($this->never())->method('process_datamap');
        $createdTceMain->expects($this->once())->method('process_cmdmap');
        GeneralUtility::addInstance(DataHandler::class, $createdTceMain);
        $subject->process_datamap();
    }

    /**
     * @test
     */
    public function doesCheckFlexFormValueHookGetsCalled()
    {
        $hookClass = $this->getUniqueId('tx_coretest');
        $hookMock = $this->getMock($hookClass, ['checkFlexFormValue_beforeMerge']);
        $hookMock->expects($this->once())->method('checkFlexFormValue_beforeMerge');
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['checkFlexFormValue'][] = $hookClass;
        $GLOBALS['T3_VAR']['getUserObj'][$hookClass] = $hookMock;
        $this->subject->_call('checkValueForFlex', [], [], [], '', 0, '', '', 0, 0, 0, [], '');
    }

    /////////////////////////////////////
    // Tests concerning log
    /////////////////////////////////////
    /**
     * @test
     */
    public function logCallsWriteLogOfBackendUserIfLoggingIsEnabled()
    {
        $backendUser = $this->getMock(BackendUserAuthentication::class);
        $backendUser->expects($this->once())->method('writelog');
        $this->subject->enableLogging = true;
        $this->subject->BE_USER = $backendUser;
        $this->subject->log('', 23, 0, 42, 0, 'details');
    }

    /**
     * @test
     */
    public function logDoesNotCallWriteLogOfBackendUserIfLoggingIsDisabled()
    {
        $backendUser = $this->getMock(BackendUserAuthentication::class);
        $backendUser->expects($this->never())->method('writelog');
        $this->subject->enableLogging = false;
        $this->subject->BE_USER = $backendUser;
        $this->subject->log('', 23, 0, 42, 0, 'details');
    }

    /**
     * @test
     */
    public function logAddsEntryToLocalErrorLogArray()
    {
        $backendUser = $this->getMock(BackendUserAuthentication::class);
        $this->subject->BE_USER = $backendUser;
        $this->subject->enableLogging = true;
        $this->subject->errorLog = [];
        $logDetailsUnique = $this->getUniqueId('details');
        $this->subject->log('', 23, 0, 42, 1, $logDetailsUnique);
        $this->assertStringEndsWith($logDetailsUnique, $this->subject->errorLog[0]);
    }

    /**
     * @test
     */
    public function logFormatsDetailMessageWithAdditionalDataInLocalErrorArray()
    {
        $backendUser = $this->getMock(BackendUserAuthentication::class);
        $this->subject->BE_USER = $backendUser;
        $this->subject->enableLogging = true;
        $this->subject->errorLog = [];
        $logDetails = $this->getUniqueId('details');
        $this->subject->log('', 23, 0, 42, 1, '%1$s' . $logDetails . '%2$s', -1, ['foo', 'bar']);
        $expected = 'foo' . $logDetails . 'bar';
        $this->assertStringEndsWith($expected, $this->subject->errorLog[0]);
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
    public function equalSubmittedAndStoredValuesAreDetermined($expected, $submittedValue, $storedValue, $storedType, $allowNull)
    {
        $result = $this->callInaccessibleMethod(
            $this->subject,
            'isSubmittedValueEqualToStoredValue',
            $submittedValue, $storedValue, $storedType, $allowNull
        );
        $this->assertEquals($expected, $result);
    }

    /**
     * @return array
     */
    public function equalSubmittedAndStoredValuesAreDeterminedDataProvider()
    {
        return [
            // String
            'string value "" vs. ""' => [
                true,
                '', '', 'string', false
            ],
            'string value 0 vs. "0"' => [
                true,
                0, '0', 'string', false
            ],
            'string value 1 vs. "1"' => [
                true,
                1, '1', 'string', false
            ],
            'string value "0" vs. ""' => [
                false,
                '0', '', 'string', false
            ],
            'string value 0 vs. ""' => [
                false,
                0, '', 'string', false
            ],
            'string value null vs. ""' => [
                true,
                null, '', 'string', false
            ],
            // Integer
            'integer value 0 vs. 0' => [
                true,
                0, 0, 'int', false
            ],
            'integer value "0" vs. "0"' => [
                true,
                '0', '0', 'int', false
            ],
            'integer value 0 vs. "0"' => [
                true,
                0, '0', 'int', false
            ],
            'integer value "" vs. "0"' => [
                true,
                '', '0', 'int', false
            ],
            'integer value "" vs. 0' => [
                true,
                '', 0, 'int', false
            ],
            'integer value "0" vs. 0' => [
                true,
                '0', 0, 'int', false
            ],
            'integer value 1 vs. 1' => [
                true,
                1, 1, 'int', false
            ],
            'integer value 1 vs. "1"' => [
                true,
                1, '1', 'int', false
            ],
            'integer value "1" vs. "1"' => [
                true,
                '1', '1', 'int', false
            ],
            'integer value "1" vs. 1' => [
                true,
                '1', 1, 'int', false
            ],
            'integer value "0" vs. "1"' => [
                false,
                '0', '1', 'int', false
            ],
            // String with allowed NULL values
            'string with allowed null value "" vs. ""' => [
                true,
                '', '', 'string', true
            ],
            'string with allowed null value 0 vs. "0"' => [
                true,
                0, '0', 'string', true
            ],
            'string with allowed null value 1 vs. "1"' => [
                true,
                1, '1', 'string', true
            ],
            'string with allowed null value "0" vs. ""' => [
                false,
                '0', '', 'string', true
            ],
            'string with allowed null value 0 vs. ""' => [
                false,
                0, '', 'string', true
            ],
            'string with allowed null value null vs. ""' => [
                false,
                null, '', 'string', true
            ],
            'string with allowed null value "" vs. null' => [
                false,
                '', null, 'string', true
            ],
            'string with allowed null value null vs. null' => [
                true,
                null, null, 'string', true
            ],
            // Integer with allowed NULL values
            'integer with allowed null value 0 vs. 0' => [
                true,
                0, 0, 'int', true
            ],
            'integer with allowed null value "0" vs. "0"' => [
                true,
                '0', '0', 'int', true
            ],
            'integer with allowed null value 0 vs. "0"' => [
                true,
                0, '0', 'int', true
            ],
            'integer with allowed null value "" vs. "0"' => [
                true,
                '', '0', 'int', true
            ],
            'integer with allowed null value "" vs. 0' => [
                true,
                '', 0, 'int', true
            ],
            'integer with allowed null value "0" vs. 0' => [
                true,
                '0', 0, 'int', true
            ],
            'integer with allowed null value 1 vs. 1' => [
                true,
                1, 1, 'int', true
            ],
            'integer with allowed null value "1" vs. "1"' => [
                true,
                '1', '1', 'int', true
            ],
            'integer with allowed null value "1" vs. 1' => [
                true,
                '1', 1, 'int', true
            ],
            'integer with allowed null value 1 vs. "1"' => [
                true,
                1, '1', 'int', true
            ],
            'integer with allowed null value "0" vs. "1"' => [
                false,
                '0', '1', 'int', true
            ],
            'integer with allowed null value null vs. ""' => [
                false,
                null, '', 'int', true
            ],
            'integer with allowed null value "" vs. null' => [
                false,
                '', null, 'int', true
            ],
            'integer with allowed null value null vs. null' => [
                true,
                null, null, 'int', true
            ],
            'integer with allowed null value null vs. "0"' => [
                false,
                null, '0', 'int', true
            ],
            'integer with allowed null value null vs. 0' => [
                false,
                null, 0, 'int', true
            ],
            'integer with allowed null value "0" vs. null' => [
                false,
                '0', null, 'int', true
            ],
        ];
    }

    /**
     * @param bool $expected
     * @param array $eval
     * @dataProvider getPlaceholderTitleForTableLabelReturnsLabelThatsMatchesLabelFieldConditionsDataProvider
     * @test
     */
    public function getPlaceholderTitleForTableLabelReturnsLabelThatsMatchesLabelFieldConditions($expected, $eval)
    {
        $table = 'phpunit_dummy';

        /** @var DataHandler|\PHPUnit_Framework_MockObject_MockObject|AccessibleObjectInterface $subject */
        $subject = $this->getAccessibleMock(
            DataHandler::class,
            ['dummy']
        );

        $backendUser = $this->getMock(BackendUserAuthentication::class);
        $subject->BE_USER = $backendUser;
        $subject->BE_USER->workspace = 1;

        $GLOBALS['TCA'][$table] = [];
        $GLOBALS['TCA'][$table]['ctrl'] = ['label' => 'dummy'];
        $GLOBALS['TCA'][$table]['columns'] = [
            'dummy' => [
                'config' => [
                    'eval' => $eval
                ]
            ]
        ];

        $this->assertEquals($expected, $subject->_call('getPlaceholderTitleForTableLabel', $table));
    }

    /**
     * @return array
     */
    public function getPlaceholderTitleForTableLabelReturnsLabelThatsMatchesLabelFieldConditionsDataProvider()
    {
        return [
            [
                0.10,
                'double2'
            ],
            [
                0,
                'int'
            ],
            [
                '0',
                'datetime'
            ],
            [
                '[PLACEHOLDER, WS#1]',
                ''
            ]
        ];
    }

    /**
     * @test
     */
    public function deletePagesOnRootLevelIsDenied()
    {
        /** @var DataHandler|\PHPUnit_Framework_MockObject_MockObject|AccessibleObjectInterface $dataHandlerMock */
        $dataHandlerMock = $this->getMock(DataHandler::class, ['canDeletePage', 'newlog2']);
        $dataHandlerMock
            ->expects($this->never())
            ->method('canDeletePage');
        $dataHandlerMock
            ->expects($this->once())
            ->method('newlog2')
            ->with('Deleting all pages starting from the root-page is disabled.', 'pages', 0, 0, 2);

        $dataHandlerMock->deletePages(0);
    }

    /**
     * @test
     */
    public function deleteRecord_procBasedOnFieldTypeRespectsEnableCascadingDelete()
    {
        $table = $this->getUniqueId('foo_');
        $conf = [
            'type' => 'inline',
            'foreign_table' => $this->getUniqueId('foreign_foo_'),
            'behaviour' => [
                'enableCascadingDelete' => 0,
            ]
        ];

        /** @var \TYPO3\CMS\Core\Database\RelationHandler $mockRelationHandler */
        $mockRelationHandler = $this->getMock(\TYPO3\CMS\Core\Database\RelationHandler::class, [], [], '', false);
        $mockRelationHandler->itemArray = [
            '1' => ['table' => $this->getUniqueId('bar_'), 'id' => 67]
        ];

        /** @var DataHandler|\PHPUnit_Framework_MockObject_MockObject|AccessibleObjectInterface $mockDataHandler */
        $mockDataHandler = $this->getAccessibleMock(DataHandler::class, ['getInlineFieldType', 'deleteAction', 'createRelationHandlerInstance'], [], '', false);
        $mockDataHandler->expects($this->once())->method('getInlineFieldType')->will($this->returnValue('field'));
        $mockDataHandler->expects($this->once())->method('createRelationHandlerInstance')->will($this->returnValue($mockRelationHandler));
        $mockDataHandler->expects($this->never())->method('deleteAction');
        $mockDataHandler->deleteRecord_procBasedOnFieldType($table, 42, 'foo', 'bar', $conf);
    }

    /**
     * @return array
     */
    public function checkValue_checkReturnsExpectedValuesDataProvider()
    {
        return [
            'None item selected' => [
                0,
                0
            ],
            'All items selected' => [
                7,
                7
            ],
            'Item 1 and 2 are selected' => [
                3,
                3
            ],
            'Value is higher than allowed (all checkboxes checked)' => [
                15,
                7
            ],
            'Value is higher than allowed (some checkboxes checked)' => [
                11,
                3
            ],
            'Negative value' => [
                -5,
                0
            ]
        ];
    }

    /**
     * @param string $value
     * @param string $expectedValue
     *
     * @dataProvider checkValue_checkReturnsExpectedValuesDataProvider
     * @test
     */
    public function checkValue_checkReturnsExpectedValues($value, $expectedValue)
    {
        $expectedResult = [
            'value' => $expectedValue
        ];
        $result = [];
        $tcaFieldConfiguration = [
            'items' => [
                ['Item 1', 0],
                ['Item 2', 0],
                ['Item 3', 0]
            ]
        ];
        $this->assertSame($expectedResult, $this->subject->_call('checkValueForCheck', $result, $value, $tcaFieldConfiguration, '', 0, 0, ''));
    }

    /**
     * @test
     */
    public function checkValueForInputConvertsNullToEmptyString()
    {
        $previousLanguageService = $GLOBALS['LANG'];
        $GLOBALS['LANG'] = GeneralUtility::makeInstance(\TYPO3\CMS\Lang\LanguageService::class);
        $GLOBALS['LANG']->init('default');
        $expectedResult = ['value' => ''];
        $this->assertSame($expectedResult, $this->subject->_call('checkValueForInput', null, ['type' => 'string', 'max' => 40], 'tt_content', 'NEW55c0e67f8f4d32.04974534', 89, 'table_caption'));
        $GLOBALS['LANG'] = $previousLanguageService;
    }

    /**
     * @param mixed $value
     * @param array $configuration
     * @param int|string $expected
     * @test
     * @dataProvider referenceValuesAreCastedDataProvider
     */
    public function referenceValuesAreCasted($value, array $configuration, $expected)
    {
        $this->assertEquals(
            $expected,
            $this->subject->_call('castReferenceValue', $value, $configuration)
        );
    }

    /**
     * @return array
     */
    public function referenceValuesAreCastedDataProvider()
    {
        return [
            'all empty' => [
                '', [], ''
            ],
            'cast zero with MM table' => [
                '', ['MM' => 'table'], 0
            ],
            'cast zero with MM table with default value' => [
                '', ['MM' => 'table', 'default' => 13], 0
            ],
            'cast zero with foreign field' => [
                '', ['foreign_field' => 'table', 'default' => 13], 0
            ],
            'cast zero with foreign field with default value' => [
                '', ['foreign_field' => 'table'], 0
            ],
            'pass zero' => [
                '0', [], '0'
            ],
            'pass value' => [
                '1', ['default' => 13], '1'
            ],
            'use default value' => [
                '', ['default' => 13], 13
            ],
        ];
    }
}
