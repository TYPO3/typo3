<?php
namespace TYPO3\CMS\Extbase\Tests\Unit\Persistence\Storage;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2009 Jochen Rau <jochen.rau@typoplanet.de>
 *  All rights reserved
 *
 *  This class is a backport of the corresponding class of FLOW3.
 *  All credits go to the v5 team.
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
class Typo3DbBackendTest extends \TYPO3\CMS\Extbase\Tests\Unit\BaseTestCase {

	/**
	 * This is the data provider for the statement generation with a basic comparison
	 *
	 * @return array An array of data
	 */
	public function providerForBasicComparison() {
		return array(
			'equal' => array(
				\TYPO3\CMS\Extbase\Persistence\QueryInterface::OPERATOR_EQUAL_TO,
				'SELECT table_name_from_selector.* FROM table_name_from_selector WHERE table_name_from_property.foo = \'baz\''
			),
			'less' => array(
				\TYPO3\CMS\Extbase\Persistence\QueryInterface::OPERATOR_LESS_THAN,
				'SELECT table_name_from_selector.* FROM table_name_from_selector WHERE table_name_from_property.foo < \'baz\''
			),
			'less or equal' => array(
				\TYPO3\CMS\Extbase\Persistence\QueryInterface::OPERATOR_LESS_THAN_OR_EQUAL_TO,
				'SELECT table_name_from_selector.* FROM table_name_from_selector WHERE table_name_from_property.foo <= \'baz\''
			),
			'greater' => array(
				\TYPO3\CMS\Extbase\Persistence\QueryInterface::OPERATOR_GREATER_THAN,
				'SELECT table_name_from_selector.* FROM table_name_from_selector WHERE table_name_from_property.foo > \'baz\''
			),
			'greater or equal' => array(
				\TYPO3\CMS\Extbase\Persistence\QueryInterface::OPERATOR_GREATER_THAN_OR_EQUAL_TO,
				'SELECT table_name_from_selector.* FROM table_name_from_selector WHERE table_name_from_property.foo >= \'baz\''
			)
		);
	}

	/**
	 * @test
	 */
	public function getStatementWorksWithMinimalisticQueryObjectModel() {
		$this->markTestIncomplete();
	}

	/**
	 * @test
	 */
	public function getStatementWorksWithBasicEqualsCondition() {
		$this->markTestIncomplete();
	}

	/**
	 * @test
	 * @expectedException \TYPO3\CMS\Extbase\Persistence\Generic\Storage\Exception\BadConstraintException
	 */
	public function countRowsWithStatementConstraintResultsInAnException() {
		$this->markTestIncomplete();
	}

	/**
	 * @test
	 */
	public function joinStatementGenerationWorks() {
		$this->markTestIncomplete();
	}

	/**
	 * @test
	 */
	public function addSysLanguageStatementWorksForDefaultLanguage() {
		$table = uniqid('tx_coretest_table');
		$GLOBALS['TCA'][$table]['ctrl'] = array(
			'languageField' => 'sys_language_uid'
		);
		$tsfe = $this->getMock('TYPO3\\CMS\\Frontend\\Controller\\TypoScriptFrontendController', array(), array(), '', false);
		$tsfe->sys_language_uid = 0;
		$GLOBALS['TSFE'] = $tsfe;
		$sql = array();
		$mockTypo3DbBackend = $this->getAccessibleMock('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Storage\\Typo3DbBackend', array('dummy'), array(), '', false);
		$mockTypo3DbBackend->_callRef('addSysLanguageStatement', $table, $sql);
		$expectedSql = array('additionalWhereClause' => array('(' . $table . '.sys_language_uid IN (0,-1))'));
		$this->assertSame($expectedSql, $sql);
	}

	/**
	 * @test
	 */
	public function addSysLanguageStatementWorksInBackendContextWithNoGlobalTypoScriptFrontendControllerAvailable() {
		$table = uniqid('tx_coretest_table');
		$GLOBALS['TCA'][$table]['ctrl'] = array(
			'languageField' => 'sys_language_uid'
		);
		$sql = array();
		$mockTypo3DbBackend = $this->getAccessibleMock('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Storage\\Typo3DbBackend', array('dummy'), array(), '', false);
		$mockTypo3DbBackend->_callRef('addSysLanguageStatement', $table, $sql);
		$expectedSql = array('additionalWhereClause' => array('(' . $table . '.sys_language_uid IN (0,-1))'));
		$this->assertSame($expectedSql, $sql);
	}

	/**
	 * @test
	 */
	public function addSysLanguageStatementWorksForDefaultLanguageWithoutDeleteStatementReturned() {
		$table = uniqid('tx_coretest_table');
		$GLOBALS['TCA'][$table]['ctrl'] = array(
			'languageField' => 'sys_language_uid',
			'delete' => 'deleted'
		);
		$sql = array();
		$languageUid = 0;
		$mockTypo3DbBackend = $this->getAccessibleMock('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Storage\\Typo3DbBackend', array('dummy'), array(), '', false);
		$mockTypo3DbBackend->_callRef('addSysLanguageStatement', $table, $sql, $languageUid);
		$expectedSql = array('additionalWhereClause' => array('(' . $table . '.sys_language_uid IN (0,-1))'));
		$this->assertSame($expectedSql, $sql);
	}

	/**
	 * @test
	 */
	public function addSysLanguageStatementWorksForForeignLanguageWithoutSubselection() {
		$table = uniqid('tx_coretest_table');
		$GLOBALS['TCA'][$table]['ctrl'] = array(
			'languageField' => 'sys_language_uid'
		);
		$sql = array();
		$languageUid = 2;
		$mockTypo3DbBackend = $this->getAccessibleMock('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Storage\\Typo3DbBackend', array('dummy'), array(), '', false);
		$mockTypo3DbBackend->_callRef('addSysLanguageStatement', $table, $sql, $languageUid);
		$expectedSql = array('additionalWhereClause' => array('(' . $table . '.sys_language_uid IN (2,-1))'));
		$this->assertSame($expectedSql, $sql);
	}

	/**
	 * @test
	 */
	public function addSysLanguageStatementWorksForForeignLanguageWithSubselectionWithoutDeleteStatementReturned() {
		$table = uniqid('tx_coretest_table');
		$GLOBALS['TCA'][$table]['ctrl'] = array(
			'languageField' => 'sys_language_uid',
			'transOrigPointerField' => 'l10n_parent'
		);
		$sql = array();
		$languageUid = 2;
		$mockTypo3DbBackend = $this->getAccessibleMock('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Storage\\Typo3DbBackend', array('dummy'), array(), '', false);
		$mockTypo3DbBackend->_callRef('addSysLanguageStatement', $table, $sql, $languageUid);
		$expectedSql = array('additionalWhereClause' => array('(' . $table . '.sys_language_uid IN (2,-1) OR (' . $table . '.sys_language_uid=0 AND ' . $table . '.uid NOT IN (SELECT ' . $table . '.l10n_parent FROM ' . $table . ' WHERE ' . $table . '.l10n_parent>0 AND ' . $table . '.sys_language_uid>0)))'));
		$this->assertSame($expectedSql, $sql);
	}

	/**
	 * @test
	 */
	public function addSysLanguageStatementWorksForForeignLanguageWithSubselectionTakesDeleteStatementIntoAccountIfNecessary() {
		$table = uniqid('tx_coretest_table');
		$GLOBALS['TCA'][$table]['ctrl'] = array(
			'languageField' => 'sys_language_uid',
			'transOrigPointerField' => 'l10n_parent',
			'delete' => 'deleted'
		);
		$sql = array();
		$languageUid = 2;
		$mockTypo3DbBackend = $this->getAccessibleMock('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Storage\\Typo3DbBackend', array('dummy'), array(), '', false);
		$mockTypo3DbBackend->_callRef('addSysLanguageStatement', $table, $sql, $languageUid);
		$expectedSql = array('additionalWhereClause' => array(
			'(' . $table . '.sys_language_uid IN (2,-1)' .
					' OR (' . $table . '.sys_language_uid=0 AND ' . $table . '.uid NOT IN (' .
						'SELECT ' . $table . '.l10n_parent FROM ' . $table .
						' WHERE ' . $table . '.l10n_parent>0 AND ' .
							$table . '.sys_language_uid>0 AND ' .
							$table . '.deleted=0)))')
		);
		$this->assertSame($expectedSql, $sql);
	}

	/**
	 * @test
	 */
	public function addSysLanguageStatementWorksInBackendContextWithSubselectionTakesDeleteStatementIntoAccountIfNecessary() {
		$table = uniqid('tx_coretest_table');
		$GLOBALS['TCA'][$table]['ctrl'] = array(
			'languageField' => 'sys_language_uid',
			'transOrigPointerField' => 'l10n_parent',
			'delete' => 'deleted'
		);
		$sql = array();
		$languageUid = 2;
		$mockTypo3DbBackend = $this->getAccessibleMock('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Storage\\Typo3DbBackend', array('dummy'), array(), '', false);
		$mockTypo3DbBackend->_callRef('addSysLanguageStatement', $table, $sql, $languageUid);
		$expectedSql = array('additionalWhereClause' => array(
			'(' . $table . '.sys_language_uid IN (2,-1)' .
					' OR (' . $table . '.sys_language_uid=0 AND ' . $table . '.uid NOT IN (' .
						'SELECT ' . $table . '.l10n_parent FROM ' . $table .
						' WHERE ' . $table . '.l10n_parent>0 AND ' .
							$table . '.sys_language_uid>0 AND ' .
							$table . '.deleted=0)))')
		);
		$this->assertSame($expectedSql, $sql);
	}

	/**
	 * @test
	 */
	public function orderStatementGenerationWorks() {
		$mockSource = $this->getMock('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Qom\\Selector', array('getNodeTypeName'), array(), '', FALSE);
		$mockSource->expects($this->any())->method('getNodeTypeName')->will($this->returnValue('Tx_MyExt_ClassName'));
		$mockDataMapper = $this->getMock('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Mapper\\DataMapper', array('convertPropertyNameToColumnName', 'convertClassNameToTableName'), array(), '', FALSE);
		$mockDataMapper->expects($this->once())->method('convertClassNameToTableName')->with('Tx_MyExt_ClassName')->will($this->returnValue('tx_myext_tablename'));
		$mockDataMapper->expects($this->once())->method('convertPropertyNameToColumnName')->with('fooProperty', 'Tx_MyExt_ClassName')->will($this->returnValue('converted_fieldname'));
		$sql = array();
		$orderings = array('fooProperty' => \TYPO3\CMS\Extbase\Persistence\QueryInterface::ORDER_ASCENDING);
		$mockTypo3DbBackend = $this->getAccessibleMock('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Storage\\Typo3DbBackend', array('parserOrderings'), array(), '', FALSE);
		$mockTypo3DbBackend->_set('dataMapper', $mockDataMapper);
		$mockTypo3DbBackend->_callRef('parseOrderings', $orderings, $mockSource, $sql);
		$expectedSql = array('orderings' => array('tx_myext_tablename.converted_fieldname ASC'));
		$this->assertSame($expectedSql, $sql);
	}

	/**
	 * @test
	 * @expectedException \TYPO3\CMS\Extbase\Persistence\Generic\Exception\UnsupportedOrderException
	 */
	public function orderStatementGenerationThrowsExceptionOnUnsupportedOrder() {
		$mockSource = $this->getMock('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Qom\\Selector', array('getNodeTypeName'), array(), '', FALSE);
		$mockSource->expects($this->never())->method('getNodeTypeName');
		$mockDataMapper = $this->getMock('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Mapper\\DataMapper', array('convertPropertyNameToColumnName', 'convertClassNameToTableName'), array(), '', FALSE);
		$mockDataMapper->expects($this->never())->method('convertClassNameToTableName');
		$mockDataMapper->expects($this->never())->method('convertPropertyNameToColumnName');
		$sql = array();
		$orderings = array('fooProperty' => 'unsupported_order');
		$mockTypo3DbBackend = $this->getAccessibleMock('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Storage\\Typo3DbBackend', array('parserOrderings'), array(), '', FALSE);
		$mockTypo3DbBackend->_set('dataMapper', $mockDataMapper);
		$mockTypo3DbBackend->_callRef('parseOrderings', $orderings, $mockSource, $sql);
	}

	/**
	 * @test
	 */
	public function orderStatementGenerationWorksWithMultipleOrderings() {
		$mockSource = $this->getMock('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Qom\\Selector', array('getNodeTypeName'), array(), '', FALSE);
		$mockSource->expects($this->any())->method('getNodeTypeName')->will($this->returnValue('Tx_MyExt_ClassName'));
		$mockDataMapper = $this->getMock('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Mapper\\DataMapper', array('convertPropertyNameToColumnName', 'convertClassNameToTableName'), array(), '', FALSE);
		$mockDataMapper->expects($this->any())->method('convertClassNameToTableName')->with('Tx_MyExt_ClassName')->will($this->returnValue('tx_myext_tablename'));
		$mockDataMapper->expects($this->any())->method('convertPropertyNameToColumnName')->will($this->returnValue('converted_fieldname'));
		$sql = array();
		$orderings = array(
			'fooProperty' => \TYPO3\CMS\Extbase\Persistence\QueryInterface::ORDER_ASCENDING,
			'barProperty' => \TYPO3\CMS\Extbase\Persistence\QueryInterface::ORDER_DESCENDING
		);
		$mockTypo3DbBackend = $this->getAccessibleMock('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Storage\\Typo3DbBackend', array('parserOrderings'), array(), '', FALSE);
		$mockTypo3DbBackend->_set('dataMapper', $mockDataMapper);
		$mockTypo3DbBackend->_callRef('parseOrderings', $orderings, $mockSource, $sql);
		$expectedSql = array('orderings' => array('tx_myext_tablename.converted_fieldname ASC', 'tx_myext_tablename.converted_fieldname DESC'));
		$this->assertSame($expectedSql, $sql);
	}

	public function providerForVisibilityConstraintStatement() {
		return array(
			'in be: include all' => array('BE', TRUE, array(), TRUE, NULL),
			'in be: ignore enable fields but do not include deleted' => array('BE', TRUE, array(), FALSE, array('tx_foo_table.deleted_column=0')),
			'in be: respect enable fields but include deleted' => array('BE', FALSE, array(), TRUE, array('tx_foo_table.disabled_column=0 AND (tx_foo_table.starttime_column<=123456789)')),
			'in be: respect enable fields and do not include deleted' => array('BE', FALSE, array(), FALSE, array('tx_foo_table.disabled_column=0 AND (tx_foo_table.starttime_column<=123456789) AND tx_foo_table.deleted_column=0')),
			'in fe: include all' => array('FE', TRUE, array(), TRUE, NULL),
			'in fe: ignore enable fields but do not include deleted' => array('FE', TRUE, array(), FALSE, array('tx_foo_table.deleted_column=0')),
			'in fe: ignore only starttime and do not include deleted' => array('FE', TRUE, array('starttime'), FALSE, array('tx_foo_table.deleted_column=0 AND tx_foo_table.disabled_column=0')),
			'in fe: respect enable fields and do not include deleted' => array('FE', FALSE, array(), FALSE, array('tx_foo_table.deleted_column=0 AND tx_foo_table.disabled_column=0 AND tx_foo_table.starttime_column<=123456789'))
		);
	}

	/**
	 * @test
	 * @dataProvider providerForVisibilityConstraintStatement
	 */
	public function visibilityConstraintStatementIsGeneratedAccordingToTheQuerySettings($mode, $ignoreEnableFields, $enableFieldsToBeIgnored, $deletedValue, $expectedSql) {
		$tableName = 'tx_foo_table';
		$GLOBALS['TCA'][$tableName]['ctrl'] = array(
			'enablecolumns' => array(
				'disabled' => 'disabled_column',
				'starttime' => 'starttime_column'
			),
			'delete' => 'deleted_column'
		);
		$GLOBALS['TSFE'] = new \stdClass();
		$GLOBALS['TSFE']->sys_page = new \TYPO3\CMS\Frontend\Page\PageRepository();
		$GLOBALS['SIM_ACCESS_TIME'] = 123456789;
		$mockQuerySettings = $this->getMock('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Typo3QuerySettings', array('getIgnoreEnableFields', 'getEnableFieldsToBeIgnored', 'getIncludeDeleted'), array(), '', FALSE);
		$mockQuerySettings->expects($this->once())->method('getIgnoreEnableFields')->will($this->returnValue($ignoreEnableFields));
		$mockQuerySettings->expects($this->once())->method('getEnableFieldsToBeIgnored')->will($this->returnValue($enableFieldsToBeIgnored));
		$mockQuerySettings->expects($this->once())->method('getIncludeDeleted')->will($this->returnValue($deletedValue));
		$sql = array();
		$mockTypo3DbBackend = $this->getAccessibleMock('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Storage\\Typo3DbBackend', array('getTypo3Mode'), array(), '', FALSE);
		$mockTypo3DbBackend->expects($this->any())->method('getTypo3Mode')->will($this->returnValue($mode));
		$mockTypo3DbBackend->_callRef('addVisibilityConstraintStatement', $mockQuerySettings, $tableName, $sql);
		$this->assertSame($expectedSql, $sql['additionalWhereClause']);
		unset($GLOBALS['TCA'][$tableName]);
	}

	public function providerForRespectEnableFields() {
		return array(
			'in be: respectEnableFields=false' => array('BE', FALSE, NULL),
			'in be: respectEnableFields=true' => array('BE', TRUE, array('tx_foo_table.disabled_column=0 AND (tx_foo_table.starttime_column<=123456789) AND tx_foo_table.deleted_column=0')),
			'in be: respectEnableFields=false' => array('FE', FALSE, NULL),
			'in be: respectEnableFields=true' => array('FE', TRUE, array('tx_foo_table.deleted_column=0 AND tx_foo_table.disabled_column=0 AND tx_foo_table.starttime_column<=123456789'))
		);
	}

	/**
	 * @test
	 * @dataProvider providerForRespectEnableFields
	 */
	public function respectEnableFieldsSettingGeneratesCorrectStatement($mode, $respectEnableFields, $expectedSql) {
		$tableName = 'tx_foo_table';
		$GLOBALS['TCA'][$tableName]['ctrl'] = array(
			'enablecolumns' => array(
				'disabled' => 'disabled_column',
				'starttime' => 'starttime_column'
			),
			'delete' => 'deleted_column'
		);
		$GLOBALS['TSFE'] = new \stdClass();
		$GLOBALS['TSFE']->sys_page = new \TYPO3\CMS\Frontend\Page\PageRepository();
		$GLOBALS['SIM_ACCESS_TIME'] = 123456789;
		$mockQuerySettings = $this->getMock('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Typo3QuerySettings', array('dummy'), array(), '', FALSE);
		$mockQuerySettings->setRespectEnableFields($respectEnableFields);
		$sql = array();
		$mockTypo3DbBackend = $this->getAccessibleMock('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Storage\\Typo3DbBackend', array('getTypo3Mode'), array(), '', FALSE);
		$mockTypo3DbBackend->expects($this->any())->method('getTypo3Mode')->will($this->returnValue($mode));
		$mockTypo3DbBackend->_callRef('addVisibilityConstraintStatement', $mockQuerySettings, $tableName, $sql);
		$this->assertSame($expectedSql, $sql['additionalWhereClause']);
		unset($GLOBALS['TCA'][$tableName]);
	}

	/**
	 * @test
	 * @expectedException \TYPO3\CMS\Extbase\Persistence\Generic\Exception\InconsistentQuerySettingsException
	 */
	public function visibilityConstraintStatementGenerationThrowsExceptionIfTheQuerySettingsAreInconsistent() {
		$tableName = 'tx_foo_table';
		$GLOBALS['TCA'][$tableName]['ctrl'] = array(
			'enablecolumns' => array(
				'disabled' => 'disabled_column'
			),
			'delete' => 'deleted_column'
		);
		$mockQuerySettings = $this->getMock('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Typo3QuerySettings', array('getIgnoreEnableFields', 'getEnableFieldsToBeIgnored', 'getIncludeDeleted'), array(), '', FALSE);
		$mockQuerySettings->expects($this->once())->method('getIgnoreEnableFields')->will($this->returnValue(FALSE));
		$mockQuerySettings->expects($this->once())->method('getEnableFieldsToBeIgnored')->will($this->returnValue(array()));
		$mockQuerySettings->expects($this->once())->method('getIncludeDeleted')->will($this->returnValue(TRUE));
		$sql = array();
		$mockTypo3DbBackend = $this->getAccessibleMock('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Storage\\Typo3DbBackend', array('getTypo3Mode'), array(), '', FALSE);
		$mockTypo3DbBackend->expects($this->any())->method('getTypo3Mode')->will($this->returnValue('FE'));
		$mockTypo3DbBackend->_callRef('addVisibilityConstraintStatement', $mockQuerySettings, $tableName, $sql);
		unset($GLOBALS['TCA'][$tableName]);
	}

	/**
	 * @test
	 */
	public function uidOfAlreadyPersistedValueObjectIsDeterminedCorrectly() {
		$mockValueObject = $this->getMockForAbstractClass('TYPO3\\CMS\\Extbase\\DomainObject\\AbstractValueObject', array('_getProperties'), '', FALSE);
		$mockValueObject->expects($this->any())->method('_getProperties')->will($this->returnValue(array('propertyName' => 'propertyValue')));
		$mockColumnMap = $this->getMock('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Mapper\\DataMap', array('isPersistableProperty', 'getColumnName'), array(), '', FALSE);
		$mockColumnMap->expects($this->any())->method('getColumnName')->will($this->returnValue('column_name'));
		$tableName = 'tx_foo_table';
		$mockDataMap = $this->getMock('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Mapper\\DataMap', array('isPersistableProperty', 'getColumnMap', 'getTableName'), array(), '', FALSE);
		$mockDataMap->expects($this->any())->method('isPersistableProperty')->will($this->returnValue(TRUE));
		$mockDataMap->expects($this->any())->method('getColumnMap')->will($this->returnValue($mockColumnMap));
		$mockDataMap->expects($this->any())->method('getTableName')->will($this->returnValue($tableName));
		$mockDataMapper = $this->getMock('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Mapper\\DataMapper', array('getDataMap'), array(), '', FALSE);
		$mockDataMapper->expects($this->any())->method('getDataMap')->will($this->returnValue($mockDataMap));
		$expectedStatement = 'SELECT * FROM tx_foo_table WHERE column_name=?';
		$expectedParameters = array('plainPropertyValue');
		$expectedUid = 52;
		$mockDataBaseHandle = $this->getMock('t3lib_db', array('sql_query', 'sql_fetch_assoc'), array(), '', FALSE);
		$mockDataBaseHandle->expects($this->once())->method('sql_query')->will($this->returnValue('resource'));
		$mockDataBaseHandle->expects($this->any())->method('sql_fetch_assoc')->with('resource')->will($this->returnValue(array('uid' => $expectedUid)));
		$mockTypo3DbBackend = $this->getAccessibleMock('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Storage\\Typo3DbBackend', array('getPlainValue', 'checkSqlErrors', 'replacePlaceholders', 'addVisibilityConstraintStatement'), array(), '', FALSE);
		$mockTypo3DbBackend->expects($this->once())->method('getPlainValue')->will($this->returnValue('plainPropertyValue'));
		$mockTypo3DbBackend->expects($this->once())->method('addVisibilityConstraintStatement')->with($this->isInstanceOf('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\QuerySettingsInterface'), $tableName, $this->isType('array'));
		$mockTypo3DbBackend->expects($this->once())->method('replacePlaceholders')->with($expectedStatement, $expectedParameters)->will($this->returnValue('plainPropertyValue'));
		$mockTypo3DbBackend->_set('dataMapper', $mockDataMapper);
		$mockTypo3DbBackend->_set('databaseHandle', $mockDataBaseHandle);
		$result = $mockTypo3DbBackend->_callRef('getUidOfAlreadyPersistedValueObject', $mockValueObject);
		$this->assertSame($expectedUid, $result);
	}

	/**
	 * @test
	 */
	public function doLanguageAndWorkspaceOverlayChangesUidIfInPreview() {
		$comparisonRow = array(
			'uid' => '43',
			'pid' => '42',
			'_ORIG_pid' => '-1',
			'_ORIG_uid' => '43'
		);
		$row = array(
			'uid' => '42',
			'pid' => '42'
		);
		$workspaceVersion = array(
			'uid' => '43',
			'pid' => '-1'
		);
		$languageUid = 2;
		$workspaceUid = 2;
		$sourceMock = new \TYPO3\CMS\Extbase\Persistence\Generic\Qom\Selector('tx_foo', 'Tx_Foo');
		/** @var $pageSelectMock \TYPO3\CMS\Frontend\Page\PageRepository|\PHPUnit_Framework_MockObject_MockObject */
		$pageSelectMock = $this->getMock('TYPO3\\CMS\\Frontend\\Page\\PageRepository', array('movePlhOL', 'getWorkspaceVersionOfRecord'));
		$pageSelectMock->versioningPreview = TRUE;
		$pageSelectMock->expects($this->once())->method('getWorkspaceVersionOfRecord')->with($workspaceUid, 'tx_foo', '42')->will($this->returnValue($workspaceVersion));
		$mockTypo3DbBackend = $this->getAccessibleMock('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Storage\\Typo3DbBackend', array('dummy'), array(), '', FALSE);
		$mockTypo3DbBackend->_set('pageSelectObject', $pageSelectMock);
		$this->assertSame(array($comparisonRow), $mockTypo3DbBackend->_call('doLanguageAndWorkspaceOverlay', $sourceMock, array($row), $languageUid, $workspaceUid));
	}

}


?>
