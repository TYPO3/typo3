<?php
namespace TYPO3\CMS\Extbase\Tests\Unit\Persistence\Generic\Storage;

/**
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
class Typo3DbQueryParserTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @test
	 */
	public function addSysLanguageStatementWorksForDefaultLanguage() {
		$table = $this->getUniqueId('tx_coretest_table');
		$GLOBALS['TCA'][$table]['ctrl'] = array(
			'languageField' => 'sys_language_uid'
		);
		/** @var \TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings|\PHPUnit_Framework_MockObject_MockObject $querySettings */
		$querySettings = $this->getMock('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Typo3QuerySettings');
		$sql = array();
		$mockTypo3DbQueryParser = $this->getAccessibleMock('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Storage\\Typo3DbQueryParser', array('dummy'), array(), '', FALSE);
		$mockTypo3DbQueryParser->_callRef('addSysLanguageStatement', $table, $sql, $querySettings);
		$expectedSql = array('additionalWhereClause' => array('(' . $table . '.sys_language_uid IN (0,-1))'));
		$this->assertSame($expectedSql, $sql);
	}

	/**
	 * @test
	 */
	public function addSysLanguageStatementWorksForNonDefaultLanguage() {
		$table = $this->getUniqueId('tx_coretest_table');
		$GLOBALS['TCA'][$table]['ctrl'] = array(
			'languageField' => 'sys_language_uid'
		);
		$sql = array();
		/** @var \TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings|\PHPUnit_Framework_MockObject_MockObject $querySettings */
		$querySettings = $this->getMock('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Typo3QuerySettings', array('dummy'));
		$querySettings->setLanguageUid('1');
		$mockTypo3DbQueryParser = $this->getAccessibleMock('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Storage\\Typo3DbQueryParser', array('dummy'), array(), '', FALSE);
		$mockTypo3DbQueryParser->_callRef('addSysLanguageStatement', $table, $sql, $querySettings);
		$result = array('additionalWhereClause' => array('(' . $table . '.sys_language_uid IN (1,-1))'));
		$this->assertSame($result, $sql);
	}

	/**
	 * @test
	 */
	public function addSysLanguageStatementWorksInBackendContextWithNoGlobalTypoScriptFrontendControllerAvailable() {
		$table = $this->getUniqueId('tx_coretest_table');
		$GLOBALS['TCA'][$table]['ctrl'] = array(
			'languageField' => 'sys_language_uid'
		);
		$sql = array();
		$querySettings = new \TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings();
		$mockTypo3DbQueryParser = $this->getAccessibleMock('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Storage\\Typo3DbQueryParser', array('dummy'), array(), '', FALSE);
		$mockTypo3DbQueryParser->_callRef('addSysLanguageStatement', $table, $sql, $querySettings);
		$expectedSql = array('additionalWhereClause' => array('(' . $table . '.sys_language_uid IN (0,-1))'));
		$this->assertSame($expectedSql, $sql);
	}

	/**
	 * @test
	 */
	public function addSysLanguageStatementWorksForDefaultLanguageWithoutDeleteStatementReturned() {
		$table = $this->getUniqueId('tx_coretest_table');
		$GLOBALS['TCA'][$table]['ctrl'] = array(
			'languageField' => 'sys_language_uid',
			'delete' => 'deleted'
		);
		$sql = array();
		$querySettings = new \TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings();
		$querySettings->setLanguageUid(0);
		$mockTypo3DbQueryParser = $this->getAccessibleMock('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Storage\\Typo3DbQueryParser', array('dummy'), array(), '', FALSE);
		$mockTypo3DbQueryParser->_callRef('addSysLanguageStatement', $table, $sql, $querySettings);
		$expectedSql = array('additionalWhereClause' => array('(' . $table . '.sys_language_uid IN (0,-1))'));
		$this->assertSame($expectedSql, $sql);
	}

	/**
	 * @test
	 */
	public function addSysLanguageStatementWorksForForeignLanguageWithoutSubselection() {
		$table = $this->getUniqueId('tx_coretest_table');
		$GLOBALS['TCA'][$table]['ctrl'] = array(
			'languageField' => 'sys_language_uid'
		);
		$sql = array();
		$querySettings = new \TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings();
		$querySettings->setLanguageUid(2);
		$mockTypo3DbQueryParser = $this->getAccessibleMock('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Storage\\Typo3DbQueryParser', array('dummy'), array(), '', FALSE);
		$mockTypo3DbQueryParser->_callRef('addSysLanguageStatement', $table, $sql, $querySettings);
		$expectedSql = array('additionalWhereClause' => array('(' . $table . '.sys_language_uid IN (2,-1))'));
		$this->assertSame($expectedSql, $sql);
	}

	/**
	 * @test
	 */
	public function addSysLanguageStatementWorksForForeignLanguageWithSubselectionWithoutDeleteStatementReturned() {
		$table = $this->getUniqueId('tx_coretest_table');
		$GLOBALS['TCA'][$table]['ctrl'] = array(
			'languageField' => 'sys_language_uid',
			'transOrigPointerField' => 'l10n_parent'
		);
		$sql = array();
		$querySettings = new \TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings();
		$querySettings->setLanguageUid(2);
		$mockTypo3DbQueryParser = $this->getAccessibleMock('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Storage\\Typo3DbQueryParser', array('dummy'), array(), '', FALSE);
		$mockTypo3DbQueryParser->_callRef('addSysLanguageStatement', $table, $sql, $querySettings);
		$expectedSql = array('additionalWhereClause' => array('(' . $table . '.sys_language_uid IN (2,-1) OR (' . $table . '.sys_language_uid=0 AND ' . $table . '.uid NOT IN (SELECT ' . $table . '.l10n_parent FROM ' . $table . ' WHERE ' . $table . '.l10n_parent>0 AND ' . $table . '.sys_language_uid=2)))'));
		$this->assertSame($expectedSql, $sql);
	}

	/**
	 * @test
	 */
	public function addSysLanguageStatementWorksForForeignLanguageWithSubselectionTakesDeleteStatementIntoAccountIfNecessary() {
		$table = $this->getUniqueId('tx_coretest_table');
		$GLOBALS['TCA'][$table]['ctrl'] = array(
			'languageField' => 'sys_language_uid',
			'transOrigPointerField' => 'l10n_parent',
			'delete' => 'deleted'
		);
		$sql = array();
		$querySettings = new \TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings();
		$querySettings->setLanguageUid(2);
		$mockTypo3DbQueryParser = $this->getAccessibleMock('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Storage\\Typo3DbQueryParser', array('dummy'), array(), '', FALSE);
		$mockTypo3DbQueryParser->_callRef('addSysLanguageStatement', $table, $sql, $querySettings);
		$expectedSql = array('additionalWhereClause' => array(
			'(' . $table . '.sys_language_uid IN (2,-1)' .
				' OR (' . $table . '.sys_language_uid=0 AND ' . $table . '.uid NOT IN (' .
				'SELECT ' . $table . '.l10n_parent FROM ' . $table .
				' WHERE ' . $table . '.l10n_parent>0 AND ' .
				$table . '.sys_language_uid=2 AND ' .
				$table . '.deleted=0)))')
		);
		$this->assertSame($expectedSql, $sql);
	}

	/**
	 * @test
	 */
	public function addSysLanguageStatementWorksInBackendContextWithSubselectionTakesDeleteStatementIntoAccountIfNecessary() {
		$table = $this->getUniqueId('tx_coretest_table');
		$table = 'tt_content';
		$GLOBALS['TCA'][$table]['ctrl'] = array(
			'languageField' => 'sys_language_uid',
			'transOrigPointerField' => 'l10n_parent',
			'delete' => 'deleted'
		);
		$sql = array();
		$querySettings = new \TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings();
		$querySettings->setLanguageUid(2);
		$mockTypo3DbQueryParser = $this->getAccessibleMock('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Storage\\Typo3DbQueryParser', array('dummy'), array(), '', FALSE);
		$mockTypo3DbQueryParser->_callRef('addSysLanguageStatement', $table, $sql, $querySettings);
		$expectedSql = array('additionalWhereClause' => array(
			'(' . $table . '.sys_language_uid IN (2,-1)' .
				' OR (' . $table . '.sys_language_uid=0 AND ' . $table . '.uid NOT IN (' .
				'SELECT ' . $table . '.l10n_parent FROM ' . $table .
				' WHERE ' . $table . '.l10n_parent>0 AND ' .
				$table . '.sys_language_uid=2 AND ' .
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
		$mockTypo3DbQueryParser = $this->getAccessibleMock('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Storage\\Typo3DbQueryParser', array('dummy'), array(), '', FALSE);
		$mockTypo3DbQueryParser->_set('dataMapper', $mockDataMapper);
		$mockTypo3DbQueryParser->_callRef('parseOrderings', $orderings, $mockSource, $sql);
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
		$mockTypo3DbQueryParser = $this->getAccessibleMock('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Storage\\Typo3DbQueryParser', array('dummy'), array(), '', FALSE);
		$mockTypo3DbQueryParser->_set('dataMapper', $mockDataMapper);
		$mockTypo3DbQueryParser->_callRef('parseOrderings', $orderings, $mockSource, $sql);
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
		$mockTypo3DbQueryParser = $this->getAccessibleMock('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Storage\\Typo3DbQueryParser', array('dummy'), array(), '', FALSE);
		$mockTypo3DbQueryParser->_set('dataMapper', $mockDataMapper);
		$mockTypo3DbQueryParser->_callRef('parseOrderings', $orderings, $mockSource, $sql);
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

		/** @var $mockEnvironmentService \TYPO3\CMS\Extbase\Service\EnvironmentService | \PHPUnit_Framework_MockObject_MockObject */
		$mockEnvironmentService = $this->getMock('TYPO3\\CMS\\Extbase\\Service\\EnvironmentService', array('isEnvironmentInFrontendMode'));
		$mockEnvironmentService->expects($this->any())->method('isEnvironmentInFrontendMode')->will($this->returnValue($mode == 'FE'));

		$mockTypo3DbQueryParser = $this->getAccessibleMock('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Storage\\Typo3DbQueryParser', array('dummy'), array(), '', FALSE);
		$mockTypo3DbQueryParser->_set('environmentService', $mockEnvironmentService);
		$mockTypo3DbQueryParser->_callRef('addVisibilityConstraintStatement', $mockQuerySettings, $tableName, $sql);
		$this->assertSame($expectedSql, $sql['additionalWhereClause']);
		unset($GLOBALS['TCA'][$tableName]);
	}

	public function providerForRespectEnableFields() {
		return array(
			'in be: respectEnableFields=false' => array('BE', FALSE, NULL),
			'in be: respectEnableFields=true' => array('BE', TRUE, array('tx_foo_table.disabled_column=0 AND (tx_foo_table.starttime_column<=123456789) AND tx_foo_table.deleted_column=0')),
			'in FE: respectEnableFields=false' => array('FE', FALSE, NULL),
			'in FE: respectEnableFields=true' => array('FE', TRUE, array('tx_foo_table.deleted_column=0 AND tx_foo_table.disabled_column=0 AND tx_foo_table.starttime_column<=123456789'))
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
		/** @var \TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings $mockQuerySettings */
		$mockQuerySettings = $this->getMock('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Typo3QuerySettings', array('dummy'), array(), '', FALSE);
		$mockQuerySettings->setIgnoreEnableFields(!$respectEnableFields);
		$mockQuerySettings->setIncludeDeleted(!$respectEnableFields);
		$sql = array();

		/** @var $mockEnvironmentService \TYPO3\CMS\Extbase\Service\EnvironmentService | \PHPUnit_Framework_MockObject_MockObject */
		$mockEnvironmentService = $this->getMock('TYPO3\\CMS\\Extbase\\Service\\EnvironmentService', array('isEnvironmentInFrontendMode'));
		$mockEnvironmentService->expects($this->any())->method('isEnvironmentInFrontendMode')->will($this->returnValue($mode == 'FE'));

		$mockTypo3DbQueryParser = $this->getAccessibleMock('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Storage\\Typo3DbQueryParser', array('dummy'), array(), '', FALSE);
		$mockTypo3DbQueryParser->_set('environmentService', $mockEnvironmentService);
		$mockTypo3DbQueryParser->_callRef('addVisibilityConstraintStatement', $mockQuerySettings, $tableName, $sql);
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

		/** @var $mockEnvironmentService \TYPO3\CMS\Extbase\Service\EnvironmentService | \PHPUnit_Framework_MockObject_MockObject */
		$mockEnvironmentService = $this->getMock('TYPO3\\CMS\\Extbase\\Service\\EnvironmentService', array('isEnvironmentInFrontendMode'));
		$mockEnvironmentService->expects($this->any())->method('isEnvironmentInFrontendMode')->will($this->returnValue(TRUE));

		$mockTypo3DbQueryParser = $this->getAccessibleMock('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Storage\\Typo3DbQueryParser', array('dummy'), array(), '', FALSE);
		$mockTypo3DbQueryParser->_set('environmentService', $mockEnvironmentService);
		$mockTypo3DbQueryParser->_callRef('addVisibilityConstraintStatement', $mockQuerySettings, $tableName, $sql);
		unset($GLOBALS['TCA'][$tableName]);
	}
	/**
	 * DataProvider for addPageIdStatement Tests
	 */
	public function providerForAddPageIdStatementData() {
		$table = $this->getUniqueId('tx_coretest_table');
		return array(
			'set Pid to zero if rootLevel = 1' => array(
				'1',
				$table,
				array('additionalWhereClause' => array($table . '.pid = 0'))
			),
			'set Pid to given Pids if rootLevel = 0' => array(
				'0',
				$table,
				array('additionalWhereClause' => array($table . '.pid IN (42, 27)'))
			),
			'set no statement if rootLevel = -1' => array(
				'-1',
				$table,
				array()
			)
		);
	}

	/**
	 * @test
	 * @dataProvider providerForAddPageIdStatementData
	 */
	public function addPageIdStatementSetsPidToZeroIfTableDeclaresRootlevel($rootLevel, $table, $expectedSql) {

		$GLOBALS['TCA'][$table]['ctrl'] = array(
			'rootLevel' => $rootLevel
		);
		$sql = array();
		$storagePageIds = array(42,27);
		$mockTypo3DbQueryParser = $this->getAccessibleMock('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Storage\\Typo3DbQueryParser', array('dummy'), array(), '', FALSE);
		$mockFrontendVariableCache = $this->getMock('TYPO3\\CMS\\Core\\Cache\\Frontend\\VariableFrontend', array(), array(), '', FALSE);
		$mockTypo3DbQueryParser->_set('tableColumnCache', $mockFrontendVariableCache);
		$mockFrontendVariableCache->expects($this->once())->method('get')->will($this->returnValue(array('pid' => '42')));
		$mockTypo3DbQueryParser->_callRef('addPageIdStatement', $table, $sql, $storagePageIds);

		$this->assertSame($expectedSql, $sql);
	}

	/**
	 * @test
	 * @expectedException \TYPO3\CMS\Extbase\Persistence\Generic\Exception\UnexpectedTypeException
	 */
	public function getPlainValueThrowsExceptionIfInputIsArray() {
		$mockTypo3DbQueryParser = $this->getAccessibleMock(
			'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Storage\\Typo3DbQueryParser',
			array('dummy'),
			array(),
			'',
			FALSE
		);
		$mockTypo3DbQueryParser->_call('getPlainValue', array());
	}

	/**
	 * @test
	 */
	public function getPlainValueReturnsTimestampIfDateTimeObjectIsGiven() {
		$mockTypo3DbQueryParser = $this->getAccessibleMock(
			'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Storage\\Typo3DbQueryParser',
			array('dummy'),
			array(),
			'',
			FALSE
		);
		$input = new \DateTime('@1365866253');
		$this->assertSame('1365866253', $mockTypo3DbQueryParser->_call('getPlainValue', $input));
	}

	/**
	 * @test
	 */
	public function getPlainValueReturnsIntegerOneIfValueIsBooleanTrue() {
		$mockTypo3DbQueryParser = $this->getAccessibleMock(
			'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Storage\\Typo3DbQueryParser',
			array('dummy'),
			array(),
			'',
			FALSE
		);
		$this->assertSame(1, $mockTypo3DbQueryParser->_call('getPlainValue', TRUE));
	}

	/**
	 * @test
	 */
	public function getPlainValueReturnsIntegerZeroIfValueIsBooleanFalse() {
		$mockTypo3DbQueryParser = $this->getAccessibleMock(
			'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Storage\\Typo3DbQueryParser',
			array('dummy'),
			array(),
			'',
			FALSE
		);
		$this->assertSame(0, $mockTypo3DbQueryParser->_call('getPlainValue', FALSE));
	}

	/**
	 * @test
	 * @expectedException \TYPO3\CMS\Extbase\Persistence\Generic\Exception\UnexpectedTypeException
	 */
	public function getPlainValueCallsGetRealInstanceOnInputIfInputIsInstanceOfLazyLoadingProxy() {
		$mockTypo3DbQueryParser = $this->getAccessibleMock(
			'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Storage\\Typo3DbQueryParser',
			array('dummy'),
			array(),
			'',
			FALSE
		);
		$input = $this->getMock(
			'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\LazyLoadingProxy',
			array(),
			array(),
			'',
			FALSE
		);
		$input
			->expects($this->once())
			->method('_loadRealInstance');
		$mockTypo3DbQueryParser->_call('getPlainValue', $input);
	}

	/**
	 * @test
	 */
	public function getPlainValueCallsGetUidOnDomainObjectInterfaceInput() {
		$mockTypo3DbQueryParser = $this->getAccessibleMock(
			'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Storage\\Typo3DbQueryParser',
			array('dummy'),
			array(),
			'',
			FALSE
		);
		$input = $this->getMock(
			'TYPO3\\CMS\\Extbase\\DomainObject\\DomainObjectInterface',
			array(),
			array(),
			'',
			FALSE
		);
		$input
			->expects($this->once())
			->method('getUid')
			->will($this->returnValue(23));
		$this->assertSame(23, $mockTypo3DbQueryParser->_call('getPlainValue', $input));
	}

	/**
	 * @test
	 */
	public function getPlainValueReturnsSimpleType() {
		$mockTypo3DbQueryParser = $this->getAccessibleMock(
			'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Storage\\Typo3DbQueryParser',
			array('dummy'),
			array(),
			'',
			FALSE
		);
		$value = $this->getUniqueId('foo_');
		$this->assertSame($value, $mockTypo3DbQueryParser->_call('getPlainValue', $value));
	}

}
