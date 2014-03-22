<?php
namespace TYPO3\CMS\Core\Tests\Unit\DataHandler;

/***************************************************************
 * Copyright notice
 *
 * (c) 2009-2013 Oliver Klee (typo3-coding@oliverklee.de)
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Test case
 *
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 * @author Tolleiv Nietsch <info@tolleiv.de>
 */
class DataHandlerTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var array A backup of registered singleton instances
	 */
	protected $singletonInstances = array();

	/**
	 * @var \TYPO3\CMS\Core\DataHandling\DataHandler
	 */
	protected $subject;

	/**
	 * @var \TYPO3\CMS\Core\Authentication\BackendUserAuthentication a mock logged-in back-end user
	 */
	protected $backEndUser;

	public function setUp() {
		$GLOBALS['TCA'] = array();
		$this->singletonInstances = \TYPO3\CMS\Core\Utility\GeneralUtility::getSingletonInstances();
		$this->backEndUser = $this->getMock('TYPO3\\CMS\\Core\\Authentication\\BackendUserAuthentication');
		$this->subject = new \TYPO3\CMS\Core\DataHandling\DataHandler();
		$this->subject->start(array(), '', $this->backEndUser);
	}

	public function tearDown() {
		\TYPO3\CMS\Core\Utility\GeneralUtility::resetSingletonInstances($this->singletonInstances);
		parent::tearDown();
	}

	//////////////////////////////////////
	// Tests for the basic functionality
	//////////////////////////////////////
	/**
	 * @test
	 */
	public function fixtureCanBeCreated() {
		$this->assertTrue($this->subject instanceof \TYPO3\CMS\Core\DataHandling\DataHandler);
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
		$tableName = uniqid('aTable');
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
			'"-1999999" is interpreted correctly as -1999999 and is lot lower then -200000' => array(
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
	 */
	public function inputValueCheckRecognizesStringValuesAsIntegerValuesCorrectly($value, $expectedReturnValue) {
		$GLOBALS['TYPO3_DB'] = $this->getMock('TYPO3\\CMS\\Core\\Database\\DatabaseConnection', array(), array(), '', FALSE);
		$tcaFieldConf = array(
			'input' => array(),
			'eval' => 'int',
			'range' => array(
				'lower' => '-2000000',
				'upper' => '2000000'
			)
		);
		$returnValue = $this->subject->checkValue_input(array(), $value, $tcaFieldConf, array());
		$this->assertSame($returnValue['value'], $expectedReturnValue);
	}

	///////////////////////////////////////////
	// Tests concerning checkModifyAccessList
	///////////////////////////////////////////
	//
	/**
	 * Tests whether a wrong interface on the 'checkModifyAccessList' hook throws an exception.
	 *
	 * @test
	 * @expectedException UnexpectedValueException
	 */
	public function doesCheckModifyAccessListThrowExceptionOnWrongHookInterface() {
		$hookClass = uniqid('tx_coretest');
		eval('class ' . $hookClass . ' {}');
		$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['checkModifyAccessList'][] = $hookClass;
		$this->subject->checkModifyAccessList('tt_content');
	}

	/**
	 * Tests whether the 'checkModifyAccessList' hook is called correctly.
	 *
	 * @test
	 */
	public function doesCheckModifyAccessListHookGetsCalled() {
		$hookClass = uniqid('tx_coretest');
		$hookMock = $this->getMock('TYPO3\\CMS\\Core\\DataHandling\\DataHandlerCheckModifyAccessListHookInterface', array('checkModifyAccessList'), array(), $hookClass);
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
		$hookClass = uniqid('tx_coretest');
		eval('
			class ' . $hookClass . ' implements \\TYPO3\\CMS\\Core\\DataHandling\\DataHandlerCheckModifyAccessListHookInterface {
				public function checkModifyAccessList(&$accessAllowed, $table, \\TYPO3\\CMS\\Core\\DataHandling\\DataHandler $parent) { $accessAllowed = TRUE; }
			}
		');
		$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['checkModifyAccessList'][] = $hookClass;
		$this->assertTrue($this->subject->checkModifyAccessList('tt_content'));
	}

	/////////////////////////////////////
	// Tests concerning process_datamap
	/////////////////////////////////////
	/**
	 * @test
	 */
	public function processDatamapForFrozenNonZeroWorkspaceReturnsFalse() {
		$fixture = $this->getMock('TYPO3\\CMS\\Core\\DataHandling\\DataHandler', array('newlog'));
		$this->backEndUser->workspace = 1;
		$this->backEndUser->workspaceRec = array('freeze' => TRUE);
		$fixture->BE_USER = $this->backEndUser;
		$this->assertFalse($fixture->process_datamap());
	}

	/**
	 * @test
	 */
	public function processDatamapWhenEditingRecordInWorkspaceCreatesNewRecordInWorkspace() {
		// Unset possible hooks on method under test
		// @TODO: Can be removed if unit test boostrap is fixed to not load LocalConfiguration anymore
		$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'] = array();

		$GLOBALS['TYPO3_DB'] = $this->getMock('TYPO3\\CMS\\Core\\Database\\DatabaseConnection');

		$GLOBALS['TCA'] = array(
			'pages' => array(
				'columns' => array(),
			),
		);

		/** @var $subject \TYPO3\CMS\Core\DataHandling\DataHandler|\TYPO3\CMS\Core\Tests\UnitTestCase */
		$subject = $this->getMock(
			'TYPO3\\CMS\\Core\\DataHandling\\DataHandler',
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
		$backEndUser = $this->getMock('TYPO3\\CMS\\Core\\Authentication\\BackendUserAuthentication');
		$backEndUser->workspace = 1;
		$backEndUser->workspaceRec = array('freeze' => FALSE);
		$backEndUser->expects($this->once())->method('workspaceAllowAutoCreation')->will($this->returnValue(TRUE));
		$backEndUser->expects($this->once())->method('workspaceCannotEditRecord')->will($this->returnValue(TRUE));
		$backEndUser->expects($this->once())->method('recordEditAccessInternals')->with('pages', 1)->will($this->returnValue(TRUE));
		$subject->BE_USER = $backEndUser;
		$createdTceMain = $this->getMock('TYPO3\\CMS\\Core\\DataHandling\\DataHandler', array());
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
		\TYPO3\CMS\Core\Utility\GeneralUtility::addInstance('TYPO3\\CMS\\Core\\DataHandling\\DataHandler', $createdTceMain);
		$subject->process_datamap();
	}

	/**
	 * @test
	 */
	public function doesCheckFlexFormValueHookGetsCalled() {
		$GLOBALS['TYPO3_DB'] = $this->getMock('TYPO3\\CMS\\Core\\Database\\DatabaseConnection', array(), array(), '', FALSE);
		$hookClass = uniqid('tx_coretest');
		$hookMock = $this->getMock($hookClass, array('checkFlexFormValue_beforeMerge'));
		$hookMock->expects($this->once())->method('checkFlexFormValue_beforeMerge');
		$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['checkFlexFormValue'][] = $hookClass;
		$GLOBALS['T3_VAR']['getUserObj'][$hookClass] = $hookMock;
		$this->subject->checkValue_flex(array(), array(), array(), array(), array(), '');
	}

	/////////////////////////////////////
	// Tests concerning log
	/////////////////////////////////////
	/**
	 * @test
	 */
	public function logCallsWriteLogOfBackendUserIfLoggingIsEnabled() {
		$backendUser = $this->getMock('TYPO3\\CMS\\Core\\Authentication\\BackendUserAuthentication');
		$backendUser->expects($this->once())->method('writelog');
		$this->subject->enableLogging = TRUE;
		$this->subject->BE_USER = $backendUser;
		$this->subject->log('', 23, 0, 42, 0, 'details');
	}

	/**
	 * @test
	 */
	public function logDoesNotCallWriteLogOfBackendUserIfLoggingIsDisabled() {
		$backendUser = $this->getMock('TYPO3\\CMS\\Core\\Authentication\\BackendUserAuthentication');
		$backendUser->expects($this->never())->method('writelog');
		$this->subject->enableLogging = FALSE;
		$this->subject->BE_USER = $backendUser;
		$this->subject->log('', 23, 0, 42, 0, 'details');
	}

	/**
	 * @test
	 */
	public function logAddsEntryToLocalErrorLogArray() {
		$backendUser = $this->getMock('TYPO3\\CMS\\Core\\Authentication\\BackendUserAuthentication');
		$this->subject->BE_USER = $backendUser;
		$this->subject->enableLogging = TRUE;
		$this->subject->errorLog = array();
		$logDetailsUnique = uniqid('details');
		$this->subject->log('', 23, 0, 42, 1, $logDetailsUnique);
		$this->assertStringEndsWith($logDetailsUnique, $this->subject->errorLog[0]);
	}

	/**
	 * @test
	 */
	public function logFormatsDetailMessageWithAdditionalDataInLocalErrorArray() {
		$backendUser = $this->getMock('TYPO3\\CMS\\Core\\Authentication\\BackendUserAuthentication');
		$this->subject->BE_USER = $backendUser;
		$this->subject->enableLogging = TRUE;
		$this->subject->errorLog = array();
		$logDetails = uniqid('details');
		$this->subject->log('', 23, 0, 42, 1, '%1s' . $logDetails . '%2s', -1, array('foo', 'bar'));
		$expected = 'foo' . $logDetails . 'bar';
		$this->assertStringEndsWith($expected, $this->subject->errorLog[0]);
	}

	/**
	 * @param boolean $expected
	 * @param string $submittedValue
	 * @param string $storedValue
	 * @param string $storedType
	 * @param boolean $allowNull
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
	 * @test
	 */
	public function deleteRecord_procBasedOnFieldTypeRespectsEnableCascadingDelete() {
		$table = uniqid('foo_');
		$conf = array(
			'type' => 'inline',
			'foreign_table' => uniqid('foreign_foo_'),
			'behaviour' => array(
				'enableCascadingDelete' => 0,
			)
		);

		/** @var \TYPO3\CMS\Core\Database\RelationHandler $mockRelationHandler */
		$mockRelationHandler = $this->getMock('TYPO3\\CMS\\Core\\Database\\RelationHandler', array(), array(), '', FALSE);
		$mockRelationHandler->itemArray = array(
			'1' => array('table' => uniqid('bar_'), 'id' => 67)
		);

		/** @var \TYPO3\CMS\Core\DataHandling\DataHandler $mockDataHandler */
		$mockDataHandler = $this->getAccessibleMock('TYPO3\\CMS\\Core\\DataHandling\\DataHandler', array('getInlineFieldType', 'deleteAction', 'createRelationHandlerInstance'), array(), '', FALSE);
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
		$this->assertSame($expectedResult, $this->subject->checkValue_check($result, $value, $tcaFieldConfiguration, array()));
	}
}
