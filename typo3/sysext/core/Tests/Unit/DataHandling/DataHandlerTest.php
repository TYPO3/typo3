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
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Tests\Unit\DataHandling\Fixtures\AllowAccessHookFixture;
use TYPO3\CMS\Core\Tests\Unit\DataHandling\Fixtures\InvalidHookFixture;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Database\DatabaseConnection;

/**
 * Test case
 */
class DataHandlerTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var array A backup of registered singleton instances
	 */
	protected $singletonInstances = array();

	/**
	 * @var DataHandler|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface
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
	protected function setUp() {
		$GLOBALS['TCA'] = array();
		$this->singletonInstances = GeneralUtility::getSingletonInstances();
		$this->backEndUser = $this->getMock(BackendUserAuthentication::class);
		$this->mockDatabaseConnection = $this->getMock(DatabaseConnection::class, array(), array(), '', FALSE);
		$GLOBALS['TYPO3_DB'] = $this->mockDatabaseConnection;
		$this->subject = $this->getAccessibleMock(DataHandler::class, ['dummy']);
		$this->subject->start(array(), '', $this->backEndUser);
	}

	/**
	 * Tear down the tests
	 */
	protected function tearDown() {
		GeneralUtility::resetSingletonInstances($this->singletonInstances);
		parent::tearDown();
	}

	//////////////////////////////////////
	// Tests for the basic functionality
	//////////////////////////////////////
	/**
	 * @test
	 */
	public function fixtureCanBeCreated() {
		$this->assertTrue($this->subject instanceof DataHandler);
	}

	//////////////////////////////////////////
	// Test concerning checkModifyAccessList
	//////////////////////////////////////////
	/**
	 * @test
	 */
	public function adminIsAllowedToModifyNonAdminTable() {
		$this->subject->admin = TRUE;
		$this->assertTrue($this->subject->checkModifyAccessList('tt_content'));
	}

	/**
	 * @test
	 */
	public function nonAdminIsNorAllowedToModifyNonAdminTable() {
		$this->subject->admin = FALSE;
		$this->assertFalse($this->subject->checkModifyAccessList('tt_content'));
	}

	/**
	 * @test
	 */
	public function nonAdminWithTableModifyAccessIsAllowedToModifyNonAdminTable() {
		$this->subject->admin = FALSE;
		$this->backEndUser->groupData['tables_modify'] = 'tt_content';
		$this->assertTrue($this->subject->checkModifyAccessList('tt_content'));
	}

	/**
	 * @test
	 */
	public function adminIsAllowedToModifyAdminTable() {
		$this->subject->admin = TRUE;
		$this->assertTrue($this->subject->checkModifyAccessList('be_users'));
	}

	/**
	 * @test
	 */
	public function nonAdminIsNotAllowedToModifyAdminTable() {
		$this->subject->admin = FALSE;
		$this->assertFalse($this->subject->checkModifyAccessList('be_users'));
	}

	/**
	 * @test
	 */
	public function nonAdminWithTableModifyAccessIsNotAllowedToModifyAdminTable() {
		$tableName = $this->getUniqueId('aTable');
		$GLOBALS['TCA'] = array(
			$tableName => array(
				'ctrl' => array(
					'adminOnly' => TRUE,
				),
			),
		);
		$this->subject->admin = FALSE;
		$this->backEndUser->groupData['tables_modify'] = $tableName;
		$this->assertFalse($this->subject->checkModifyAccessList($tableName));
	}

	/**
	 * @test
	 */
	public function evalCheckValueDouble2() {
		$testData = array(
			'-0,5' => '-0.50',
			'1000' => '1000.00',
			'1000,10' => '1000.10',
			'1000,0' => '1000.00',
			'600.000.000,00' => '600000000.00',
			'60aaa00' => '6000.00'
		);
		foreach ($testData as $value => $expectedReturnValue) {
			$returnValue = $this->subject->checkValue_input_Eval($value, array('double2'), '');
			$this->assertSame($returnValue['value'], $expectedReturnValue);
		}
	}

	/**
	 * Data provider for inputValueCheckRecognizesStringValuesAsIntegerValuesCorrectly
	 *
	 * @return array
	 */
	public function inputValuesStringsDataProvider() {
		return array(
			'"0" returns zero as integer' => array(
				'0',
				0
			),
			'"-1999999" is interpreted correctly as -1999999 and is lot lower than -200000' => array(
				'-1999999',
				-1999999
			),
			'"3000000" is interpreted correctly as 3000000 but is higher then 200000 and set to 200000' => array(
				'3000000',
				2000000
			),
		);
	}

	/**
	 * @test
	 * @dataProvider inputValuesStringsDataProvider
	 * @param string $value
	 * @param int $expectedReturnValue
	 */
	public function inputValueCheckRecognizesStringValuesAsIntegerValuesCorrectly($value, $expectedReturnValue) {
		$tcaFieldConf = array(
			'input' => array(),
			'eval' => 'int',
			'range' => array(
				'lower' => '-2000000',
				'upper' => '2000000'
			)
		);
		$returnValue = $this->subject->_call('checkValueForInput', $value, $tcaFieldConf, '', 0, 0, '');
		$this->assertSame($returnValue['value'], $expectedReturnValue);
	}

	/**
	 * @return array
	 */
	public function inputValueCheckCallsGetDateTimeFormatsForDatetimeFieldsDataProvider() {
		return array(
			'dbType = date' => array(
				'date'
			),
			'dbType = datetime' => array(
				'datetime'
			)
		);
	}

	/**
	 * @test
	 * @dataProvider inputValueCheckCallsGetDateTimeFormatsForDatetimeFieldsDataProvider
	 * @param string $dbType
	 */
	public function inputValueCheckCallsGetDateTimeFormatsForDatetimeFields($dbType) {
		$tcaFieldConf = array(
			'input' => array(),
			'dbType' => $dbType
		);
		$this->mockDatabaseConnection->expects($this->once())->method('getDateTimeFormats');
		$this->subject->_call('checkValueForInput', '', $tcaFieldConf, '', 0, 0, '');
	}

	/**
	 * @return array
	 */
	public function inputValueCheckDoesNotCallGetDateTimeFormatsForNonDatetimeFieldsDataProvider() {
		return array(
			'tca without dbType' => array(
				array(
					'input' => array()
				)
			),
			'tca with dbType != date/datetime' => array(
				array(
					'input' => array(),
					'dbType' => 'foo'
				)
			)
		);
	}

	/**
	 * @test
	 * @param array $tcaFieldConf
	 * @dataProvider inputValueCheckDoesNotCallGetDateTimeFormatsForNonDatetimeFieldsDataProvider
	 */
	public function inputValueCheckDoesNotCallGetDateTimeFormatsForNonDatetimeFields($tcaFieldConf) {
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
	public function doesCheckModifyAccessListThrowExceptionOnWrongHookInterface() {
		$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['checkModifyAccessList'][] = InvalidHookFixture::class;
		$this->subject->checkModifyAccessList('tt_content');
	}

	/**
	 * Tests whether the 'checkModifyAccessList' hook is called correctly.
	 *
	 * @test
	 */
	public function doesCheckModifyAccessListHookGetsCalled() {
		$hookClass = $this->getUniqueId('tx_coretest');
		$hookMock = $this->getMock(\TYPO3\CMS\Core\DataHandling\DataHandlerCheckModifyAccessListHookInterface::class, array('checkModifyAccessList'), array(), $hookClass);
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
	public function doesCheckModifyAccessListHookModifyAccessAllowed() {
		$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['checkModifyAccessList'][] = AllowAccessHookFixture::class;
		$this->assertTrue($this->subject->checkModifyAccessList('tt_content'));
	}

	/////////////////////////////////////
	// Tests concerning process_datamap
	/////////////////////////////////////
	/**
	 * @test
	 */
	public function processDatamapForFrozenNonZeroWorkspaceReturnsFalse() {
		/** @var DataHandler $subject */
		$subject = $this->getMock(DataHandler::class, array('newlog'));
		$this->backEndUser->workspace = 1;
		$this->backEndUser->workspaceRec = array('freeze' => TRUE);
		$subject->BE_USER = $this->backEndUser;
		$this->assertFalse($subject->process_datamap());
	}

	/**
	 * @test
	 */
	public function processDatamapWhenEditingRecordInWorkspaceCreatesNewRecordInWorkspace() {
		// Unset possible hooks on method under test
		// @TODO: Can be removed if unit test boostrap is fixed to not load LocalConfiguration anymore
		$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'] = array();

		$GLOBALS['TCA'] = array(
			'pages' => array(
				'columns' => array(),
			),
		);

		/** @var $subject DataHandler|\PHPUnit_Framework_MockObject_MockObject */
		$subject = $this->getMock(
			DataHandler::class,
			array('newlog', 'checkModifyAccessList', 'tableReadOnly', 'checkRecordUpdateAccess')
		);

		$subject->bypassWorkspaceRestrictions = FALSE;
		$subject->datamap = array(
			'pages' => array(
				'1' => array(
					'header' => 'demo'
				)
			)
		);
		$subject->expects($this->once())->method('checkModifyAccessList')->with('pages')->will($this->returnValue(TRUE));
		$subject->expects($this->once())->method('tableReadOnly')->with('pages')->will($this->returnValue(FALSE));
		$subject->expects($this->once())->method('checkRecordUpdateAccess')->will($this->returnValue(TRUE));

		/** @var BackendUserAuthentication|\PHPUnit_Framework_MockObject_MockObject $backEndUser */
		$backEndUser = $this->getMock(BackendUserAuthentication::class);
		$backEndUser->workspace = 1;
		$backEndUser->workspaceRec = array('freeze' => FALSE);
		$backEndUser->expects($this->once())->method('workspaceAllowAutoCreation')->will($this->returnValue(TRUE));
		$backEndUser->expects($this->once())->method('workspaceCannotEditRecord')->will($this->returnValue(TRUE));
		$backEndUser->expects($this->once())->method('recordEditAccessInternals')->with('pages', 1)->will($this->returnValue(TRUE));
		$subject->BE_USER = $backEndUser;
		$createdTceMain = $this->getMock(DataHandler::class, array());
		$createdTceMain->expects($this->once())->method('start')->with(array(), array(
			'pages' => array(
				1 => array(
					'version' => array(
						'action' => 'new',
						'treeLevels' => -1,
						'label' => 'Auto-created for WS #1'
					)
				)
			)
		));
		$createdTceMain->expects($this->never())->method('process_datamap');
		$createdTceMain->expects($this->once())->method('process_cmdmap');
		GeneralUtility::addInstance(DataHandler::class, $createdTceMain);
		$subject->process_datamap();
	}

	/**
	 * @test
	 */
	public function doesCheckFlexFormValueHookGetsCalled() {
		$hookClass = $this->getUniqueId('tx_coretest');
		$hookMock = $this->getMock($hookClass, array('checkFlexFormValue_beforeMerge'));
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
	public function logCallsWriteLogOfBackendUserIfLoggingIsEnabled() {
		$backendUser = $this->getMock(BackendUserAuthentication::class);
		$backendUser->expects($this->once())->method('writelog');
		$this->subject->enableLogging = TRUE;
		$this->subject->BE_USER = $backendUser;
		$this->subject->log('', 23, 0, 42, 0, 'details');
	}

	/**
	 * @test
	 */
	public function logDoesNotCallWriteLogOfBackendUserIfLoggingIsDisabled() {
		$backendUser = $this->getMock(BackendUserAuthentication::class);
		$backendUser->expects($this->never())->method('writelog');
		$this->subject->enableLogging = FALSE;
		$this->subject->BE_USER = $backendUser;
		$this->subject->log('', 23, 0, 42, 0, 'details');
	}

	/**
	 * @test
	 */
	public function logAddsEntryToLocalErrorLogArray() {
		$backendUser = $this->getMock(BackendUserAuthentication::class);
		$this->subject->BE_USER = $backendUser;
		$this->subject->enableLogging = TRUE;
		$this->subject->errorLog = array();
		$logDetailsUnique = $this->getUniqueId('details');
		$this->subject->log('', 23, 0, 42, 1, $logDetailsUnique);
		$this->assertStringEndsWith($logDetailsUnique, $this->subject->errorLog[0]);
	}

	/**
	 * @test
	 */
	public function logFormatsDetailMessageWithAdditionalDataInLocalErrorArray() {
		$backendUser = $this->getMock(BackendUserAuthentication::class);
		$this->subject->BE_USER = $backendUser;
		$this->subject->enableLogging = TRUE;
		$this->subject->errorLog = array();
		$logDetails = $this->getUniqueId('details');
		$this->subject->log('', 23, 0, 42, 1, '%1$s' . $logDetails . '%2$s', -1, array('foo', 'bar'));
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
	public function equalSubmittedAndStoredValuesAreDetermined($expected, $submittedValue, $storedValue, $storedType, $allowNull) {
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
	public function equalSubmittedAndStoredValuesAreDeterminedDataProvider() {
		return array(
			// String
			'string value "" vs. ""' => array(
				TRUE,
				'', '', 'string', FALSE
			),
			'string value 0 vs. "0"' => array(
				TRUE,
				0, '0', 'string', FALSE
			),
			'string value 1 vs. "1"' => array(
				TRUE,
				1, '1', 'string', FALSE
			),
			'string value "0" vs. ""' => array(
				FALSE,
				'0', '', 'string', FALSE
			),
			'string value 0 vs. ""' => array(
				FALSE,
				0, '', 'string', FALSE
			),
			'string value null vs. ""' => array(
				TRUE,
				NULL, '', 'string', FALSE
			),
			// Integer
			'integer value 0 vs. 0' => array(
				TRUE,
				0, 0, 'int', FALSE
			),
			'integer value "0" vs. "0"' => array(
				TRUE,
				'0', '0', 'int', FALSE
			),
			'integer value 0 vs. "0"' => array(
				TRUE,
				0, '0', 'int', FALSE
			),
			'integer value "" vs. "0"' => array(
				TRUE,
				'', '0', 'int', FALSE
			),
			'integer value "" vs. 0' => array(
				TRUE,
				'', 0, 'int', FALSE
			),
			'integer value "0" vs. 0' => array(
				TRUE,
				'0', 0, 'int', FALSE
			),
			'integer value 1 vs. 1' => array(
				TRUE,
				1, 1, 'int', FALSE
			),
			'integer value 1 vs. "1"' => array(
				TRUE,
				1, '1', 'int', FALSE
			),
			'integer value "1" vs. "1"' => array(
				TRUE,
				'1', '1', 'int', FALSE
			),
			'integer value "1" vs. 1' => array(
				TRUE,
				'1', 1, 'int', FALSE
			),
			'integer value "0" vs. "1"' => array(
				FALSE,
				'0', '1', 'int', FALSE
			),
			// String with allowed NULL values
			'string with allowed null value "" vs. ""' => array(
				TRUE,
				'', '', 'string', TRUE
			),
			'string with allowed null value 0 vs. "0"' => array(
				TRUE,
				0, '0', 'string', TRUE
			),
			'string with allowed null value 1 vs. "1"' => array(
				TRUE,
				1, '1', 'string', TRUE
			),
			'string with allowed null value "0" vs. ""' => array(
				FALSE,
				'0', '', 'string', TRUE
			),
			'string with allowed null value 0 vs. ""' => array(
				FALSE,
				0, '', 'string', TRUE
			),
			'string with allowed null value null vs. ""' => array(
				FALSE,
				NULL, '', 'string', TRUE
			),
			'string with allowed null value "" vs. null' => array(
				FALSE,
				'', NULL, 'string', TRUE
			),
			'string with allowed null value null vs. null' => array(
				TRUE,
				NULL, NULL, 'string', TRUE
			),
			// Integer with allowed NULL values
			'integer with allowed null value 0 vs. 0' => array(
				TRUE,
				0, 0, 'int', TRUE
			),
			'integer with allowed null value "0" vs. "0"' => array(
				TRUE,
				'0', '0', 'int', TRUE
			),
			'integer with allowed null value 0 vs. "0"' => array(
				TRUE,
				0, '0', 'int', TRUE
			),
			'integer with allowed null value "" vs. "0"' => array(
				TRUE,
				'', '0', 'int', TRUE
			),
			'integer with allowed null value "" vs. 0' => array(
				TRUE,
				'', 0, 'int', TRUE
			),
			'integer with allowed null value "0" vs. 0' => array(
				TRUE,
				'0', 0, 'int', TRUE
			),
			'integer with allowed null value 1 vs. 1' => array(
				TRUE,
				1, 1, 'int', TRUE
			),
			'integer with allowed null value "1" vs. "1"' => array(
				TRUE,
				'1', '1', 'int', TRUE
			),
			'integer with allowed null value "1" vs. 1' => array(
				TRUE,
				'1', 1, 'int', TRUE
			),
			'integer with allowed null value 1 vs. "1"' => array(
				TRUE,
				1, '1', 'int', TRUE
			),
			'integer with allowed null value "0" vs. "1"' => array(
				FALSE,
				'0', '1', 'int', TRUE
			),
			'integer with allowed null value null vs. ""' => array(
				FALSE,
				NULL, '', 'int', TRUE
			),
			'integer with allowed null value "" vs. null' => array(
				FALSE,
				'', NULL, 'int', TRUE
			),
			'integer with allowed null value null vs. null' => array(
				TRUE,
				NULL, NULL, 'int', TRUE
			),
			'integer with allowed null value null vs. "0"' => array(
				FALSE,
				NULL, '0', 'int', TRUE
			),
			'integer with allowed null value null vs. 0' => array(
				FALSE,
				NULL, 0, 'int', TRUE
			),
			'integer with allowed null value "0" vs. null' => array(
				FALSE,
				'0', NULL, 'int', TRUE
			),
		);
	}

	/**
	 * @param bool $expected
	 * @param array $eval
	 * @dataProvider getPlaceholderTitleForTableLabelReturnsLabelThatsMatchesLabelFieldConditionsDataProvider
	 * @test
	 */
	public function getPlaceholderTitleForTableLabelReturnsLabelThatsMatchesLabelFieldConditions($expected, $eval) {
		$table = 'phpunit_dummy';

		/** @var DataHandler|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface $subject */
		$subject = $this->getAccessibleMock(
			DataHandler::class,
			array('dummy')
		);

		$backendUser = $this->getMock(BackendUserAuthentication::class);
		$subject->BE_USER = $backendUser;
		$subject->BE_USER->workspace = 1;

		$GLOBALS['TCA'][$table] = array();
		$GLOBALS['TCA'][$table]['ctrl'] = array('label' => 'dummy');
		$GLOBALS['TCA'][$table]['columns'] = array(
			'dummy' => array(
				'config' => array(
					'eval' => $eval
				)
			)
		);

		$this->assertEquals($expected, $subject->_call('getPlaceholderTitleForTableLabel', $table));
	}

	/**
	 * @return array
	 */
	public function getPlaceholderTitleForTableLabelReturnsLabelThatsMatchesLabelFieldConditionsDataProvider() {
		return array(
			array(
				0.10,
				'double2'
			),
			array(
				0,
				'int'
			),
			array(
				'0',
				'datetime'
			),
			array(
				'[PLACEHOLDER, WS#1]',
				''
			)
		);
	}

	/**
	 * @test
	 */
	public function deleteRecord_procBasedOnFieldTypeRespectsEnableCascadingDelete() {
		$table = $this->getUniqueId('foo_');
		$conf = array(
			'type' => 'inline',
			'foreign_table' => $this->getUniqueId('foreign_foo_'),
			'behaviour' => array(
				'enableCascadingDelete' => 0,
			)
		);

		/** @var \TYPO3\CMS\Core\Database\RelationHandler $mockRelationHandler */
		$mockRelationHandler = $this->getMock(\TYPO3\CMS\Core\Database\RelationHandler::class, array(), array(), '', FALSE);
		$mockRelationHandler->itemArray = array(
			'1' => array('table' => $this->getUniqueId('bar_'), 'id' => 67)
		);

		/** @var DataHandler|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface $mockDataHandler */
		$mockDataHandler = $this->getAccessibleMock(DataHandler::class, array('getInlineFieldType', 'deleteAction', 'createRelationHandlerInstance'), array(), '', FALSE);
		$mockDataHandler->expects($this->once())->method('getInlineFieldType')->will($this->returnValue('field'));
		$mockDataHandler->expects($this->once())->method('createRelationHandlerInstance')->will($this->returnValue($mockRelationHandler));
		$mockDataHandler->expects($this->never())->method('deleteAction');
		$mockDataHandler->deleteRecord_procBasedOnFieldType($table, 42, 'foo', 'bar', $conf);
	}

	/**
	 * @return array
	 */
	public function checkValue_checkReturnsExpectedValuesDataProvider() {
		return array(
			'None item selected' => array(
				0,
				0
			),
			'All items selected' => array(
				7,
				7
			),
			'Item 1 and 2 are selected' => array(
				3,
				3
			),
			'Value is higher than allowed' => array(
				15,
				7
			),
			'Negative value' => array(
				-5,
				0
			)
		);
	}

	/**
	 * @param string $value
	 * @param string $expectedValue
	 *
	 * @dataProvider checkValue_checkReturnsExpectedValuesDataProvider
	 * @test
	 */
	public function checkValue_checkReturnsExpectedValues($value, $expectedValue) {
		$expectedResult = array(
			'value' => $expectedValue
		);
		$result = array();
		$tcaFieldConfiguration = array(
			'items' => array(
				array('Item 1', 0),
				array('Item 2', 0),
				array('Item 3', 0)
			)
		);
		$this->assertSame($expectedResult, $this->subject->_call('checkValueForCheck', $result, $value, $tcaFieldConfiguration, '', 0, 0, ''));
	}

	/**
	 * @test
	 */
	public function checkValueForInputConvertsNullToEmptyString() {
		$previousLanguageService = $GLOBALS['LANG'];
		$GLOBALS['LANG'] = GeneralUtility::makeInstance(\TYPO3\CMS\Lang\LanguageService::class);
		$GLOBALS['LANG']->init('default');
		$expectedResult = array('value' => '');
		$this->assertSame($expectedResult, $this->subject->_call('checkValueForInput', NULL, array('type' => 'string', 'max' => 40), 'tt_content', 'NEW55c0e67f8f4d32.04974534', 89, 'table_caption'));
		$GLOBALS['LANG'] = $previousLanguageService;
	}

}
