<?php
namespace TYPO3\CMS\Dbal\Tests\Unit\Database;

/***************************************************************
 * Copyright notice
 *
 * (c) 2014 Christian Kuhn <lolli@schwarzbu.ch>
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

require_once __DIR__ . '/../../../../adodb/adodb/adodb.inc.php';
require_once __DIR__ . '/../../../../adodb/adodb/drivers/adodb-mssql.inc.php';
require_once __DIR__ . '/../../../../adodb/adodb/drivers/adodb-oci8.inc.php';
require_once __DIR__ . '/../../../../adodb/adodb/drivers/adodb-postgres7.inc.php';

/**
 * Base test case for dbal database tests.
 */
abstract class AbstractTestCase extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * Prepare a DatabaseConnection subject.
	 * Used by driver specific test cases.
	 *
	 * @param string $driver Driver to use like "mssql", "oci8" and "postgres7"
	 * @param array $configuration Dbal configuration array
	 * @return \TYPO3\CMS\Dbal\Database\DatabaseConnection|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface
	 */
	protected function prepareSubject($driver, array $configuration) {
		/** @var \TYPO3\CMS\Dbal\Database\DatabaseConnection|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface $subject */
		$subject = $this->getAccessibleMock('TYPO3\\CMS\\Dbal\\Database\\DatabaseConnection', array('getFieldInfoCache'), array(), '', FALSE);

		$subject->conf = $configuration;

		// Disable caching
		$mockCacheFrontend = $this->getMock('TYPO3\\CMS\\Core\\Cache\\Frontend\\PhpFrontend', array(), array(), '', FALSE);
		$subject->expects($this->any())->method('getFieldInfoCache')->will($this->returnValue($mockCacheFrontend));

		// Inject SqlParser - Its logic is tested with the tests, too.
		$sqlParser = $this->getAccessibleMock('TYPO3\\CMS\\Dbal\\Database\\SqlParser', array('dummy'), array(), '', FALSE);
		$sqlParser->_set('databaseConnection', $subject);
		$subject->SQLparser = $sqlParser;

		// Mock away schema migration service from install tool
		$installerSqlMock = $this->getMock('TYPO3\\CMS\\Install\\Service\\SqlSchemaMigrationService', array('getFieldDefinitions_fileContent'), array(), '', FALSE);
		$installerSqlMock->expects($this->any())->method('getFieldDefinitions_fileContent')->will($this->returnValue(array()));
		$subject->_set('installerSql', $installerSqlMock);

		$subject->initialize();

		// Fake a working connection
		$handlerKey = '_DEFAULT';
		$subject->lastHandlerKey = $handlerKey;
		$adodbDriverClass = '\ADODB_' . $driver;
		$subject->handlerInstance[$handlerKey] = new $adodbDriverClass();
		$subject->handlerInstance[$handlerKey]->DataDictionary = NewDataDictionary($subject->handlerInstance[$handlerKey]);
		$subject->handlerInstance[$handlerKey]->_connectionID = rand(1, 1000);

		return $subject;
	}


	/**
	 * Clean a parsed SQL query for easier comparison with expected SQL.
	 *
	 * @param mixed $sql
	 * @return mixed (string or array)
	 */
	protected function cleanSql($sql) {
		if (!is_string($sql)) {
			return $sql;
		}
		$sql = str_replace("\n", ' ', $sql);
		$sql = preg_replace('/\\s+/', ' ', $sql);
		return trim($sql);
	}
}