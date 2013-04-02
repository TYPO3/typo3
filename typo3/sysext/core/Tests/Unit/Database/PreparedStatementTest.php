<?php
namespace TYPO3\CMS\Core\Tests\Unit\Database;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2013 Helmut Hummel <helmut@typo3.org>
 *  All rights reserved
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

/**
 * Testcase
 *
 * @author Helmut Hummel <helmut@typo3.org>
 */
class PreparedStatementTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var \PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Database\DatabaseConnection
	 */
	private $databaseStub;

	/**
	 * Create a new database mock object for every test
	 * and backup the original global database object.
	 *
	 * @return void
	 */
	protected function setUp() {
		$this->databaseStub = $this->setUpAndReturnDatabaseStub();
	}

	//////////////////////
	// Utility functions
	//////////////////////
	/**
	 * Set up the stub to be able to get the result of the prepared statement.
	 *
	 * @return \PHPUnit_Framework_MockObject_MockObject
	 */
	private function setUpAndReturnDatabaseStub() {
		$GLOBALS['TYPO3_DB']->connectDB();
		$databaseLink = $GLOBALS['TYPO3_DB']->getDatabaseHandle();
		$GLOBALS['TYPO3_DB'] = $this->getAccessibleMock(
			'TYPO3\\CMS\\Core\\Database\\DatabaseConnection',
			array('exec_PREPAREDquery'),
			array(),
			'',
			FALSE,
			FALSE
		);
		$GLOBALS['TYPO3_DB']->_set('isConnected', TRUE);
		$GLOBALS['TYPO3_DB']->setDatabaseHandle($databaseLink);
		return $GLOBALS['TYPO3_DB'];
	}

	/**
	 * Create a object fo the subject to be tested.
	 *
	 * @param string $query
	 * @return \TYPO3\CMS\Core\Database\PreparedStatement
	 */
	private function createPreparedStatement($query) {
		return new \TYPO3\CMS\Core\Database\PreparedStatement($query, 'pages');
	}

	////////////////////////////////////
	// Tests for the utility functions
	////////////////////////////////////

	/**
	 * @test
	 * @return void
	 */
	public function setUpAndReturnDatabaseStubReturnsMockObjectOfDatabaseConnection() {
		$this->assertTrue($this->setUpAndReturnDatabaseStub() instanceof \TYPO3\CMS\Core\Database\DatabaseConnection);
	}

	/**
	 * @test
	 * @return void
	 */
	public function createPreparedStatementReturnsInstanceOfPreparedStatementClass() {
		$this->assertTrue($this->createPreparedStatement('dummy') instanceof \TYPO3\CMS\Core\Database\PreparedStatement);
	}

	///////////////////////////////////////
	// Tests for \TYPO3\CMS\Core\Database\PreparedStatement
	///////////////////////////////////////
	/**
	 * Data Provider for two tests, providing sample queries, parameters and expected result queries.
	 *
	 * @see parametersAreReplacedInQueryByCallingExecute
	 * @see parametersAreReplacedInQueryWhenBoundWithBindValues
	 * @return array
	 */
	public function parametersAndQueriesDataProvider() {
		return array(
			'one named integer parameter' => array('SELECT * FROM pages WHERE pid=:pid', array(':pid' => 1), 'SELECT * FROM pages WHERE pid=1'),
			'one unnamed integer parameter' => array('SELECT * FROM pages WHERE pid=?', array(1), 'SELECT * FROM pages WHERE pid=1'),
			'one named integer parameter is replaced multiple times' => array('SELECT * FROM pages WHERE pid=:pid OR uid=:pid', array(':pid' => 1), 'SELECT * FROM pages WHERE pid=1 OR uid=1'),
			'two named integer parameters are replaced' => array('SELECT * FROM pages WHERE pid=:pid OR uid=:uid', array(':pid' => 1, ':uid' => 10), 'SELECT * FROM pages WHERE pid=1 OR uid=10'),
			'two unnamed integer parameters are replaced' => array('SELECT * FROM pages WHERE pid=? OR uid=?', array(1, 1), 'SELECT * FROM pages WHERE pid=1 OR uid=1'),
			'php bool TRUE parameter is replaced with 1' => array('SELECT * FROM pages WHERE deleted=?', array(TRUE), 'SELECT * FROM pages WHERE deleted=1'),
			'php bool FALSE parameter is replaced with 0' => array('SELECT * FROM pages WHERE deleted=?', array(FALSE), 'SELECT * FROM pages WHERE deleted=0'),
			'php null parameter is replaced with NULL' => array('SELECT * FROM pages WHERE deleted=?', array(NULL), 'SELECT * FROM pages WHERE deleted=NULL'),
			'string parameter is wrapped in quotes' => array('SELECT * FROM pages WHERE title=?', array('Foo bar'), 'SELECT * FROM pages WHERE title=\'Foo bar\''),
			'number as string parameter is wrapped in quotes' => array('SELECT * FROM pages WHERE title=?', array('12'), 'SELECT * FROM pages WHERE title=\'12\''),
			'string single quotes in parameter are properly escaped' => array('SELECT * FROM pages WHERE title=?', array('\'Foo\''), 'SELECT * FROM pages WHERE title=\'\\\'Foo\\\'\''),
			'question mark as values with unnamed parameters are properly escaped' => array('SELECT * FROM foo WHERE title=? AND name=?', array('?', 'fancy title'), 'SELECT * FROM foo WHERE title=\'?\' AND name=\'fancy title\''),
			'parameter name as value is properly escaped' => array('SELECT * FROM foo WHERE title=:name AND name=:title', array(':name' => ':title', ':title' => 'fancy title'), 'SELECT * FROM foo WHERE title=\':title\' AND name=\'fancy title\''),
			'question mark as value of a parameter with a name is properly escaped' => array('SELECT * FROM foo WHERE title=:name AND name=?', array(':name' => '?', 'cool name'), 'SELECT * FROM foo WHERE title=\'?\' AND name=\'cool name\''),
			'regular expression back references as values are left untouched' => array('SELECT * FROM foo WHERE title=:name AND name=?', array(':name' => '\\1', '${1}'), 'SELECT * FROM foo WHERE title=\'\\\\1\' AND name=\'${1}\''),
			'unsubstituted question marks do not contain the token wrap' => array('SELECT * FROM foo WHERE title=:name AND question LIKE "%what?" AND name=:title', array(':name' => 'Title', ':title' => 'Name'), 'SELECT * FROM foo WHERE title=\'Title\' AND question LIKE "%what?" AND name=\'Name\'')
		);
	}

	/**
	 * Checking if calling execute() with parameters, they are
	 * properly relpaced in the query.
	 *
	 * @test
	 * @dataProvider parametersAndQueriesDataProvider
	 * @param string $query				Query with unreplaced markers
	 * @param array  $parameters		Array of parameters to be replaced in the query
	 * @param string $expectedResult	Query with all markers replaced
	 * @return void
	 */
	public function parametersAreReplacedInQueryByCallingExecute($query, $parameters, $expectedResult) {
		$statement = $this->createPreparedStatement($query);
		$this->databaseStub->expects($this->any())->method('exec_PREPAREDquery')->with($this->equalTo($expectedResult));
		$statement->execute($parameters);
	}

	/**
	 * Checking if parameters bound to the statement by bindValues()
	 * are properly replaced in the query.
	 *
	 * @test
	 * @dataProvider parametersAndQueriesDataProvider
	 * @param string $query				Query with unreplaced markers
	 * @param array  $parameters		Array of parameters to be replaced in the query
	 * @param string $expectedResult	Query with all markers replaced
	 * @return void
	 */
	public function parametersAreReplacedInQueryWhenBoundWithBindValues($query, $parameters, $expectedResult) {
		$statement = $this->createPreparedStatement($query);
		$this->databaseStub->expects($this->any())->method('exec_PREPAREDquery')->with($this->equalTo($expectedResult));
		$statement->bindValues($parameters);
		$statement->execute();
	}

	/**
	 * Data Provider with invalid parameters.
	 *
	 * @see invalidParameterTypesPassedToBindValueThrowsException
	 * @return array
	 */
	public function invalidParameterTypesPassedToBindValueThrowsExceptionDataProvider() {
		return array(
			'integer passed with param type NULL' => array(1, \TYPO3\CMS\Core\Database\PreparedStatement::PARAM_NULL),
			'string passed with param type NULL' => array('1', \TYPO3\CMS\Core\Database\PreparedStatement::PARAM_NULL),
			'bool passed with param type NULL' => array(TRUE, \TYPO3\CMS\Core\Database\PreparedStatement::PARAM_NULL),
			'NULL passed with param type INT' => array(NULL, \TYPO3\CMS\Core\Database\PreparedStatement::PARAM_INT),
			'string passed with param type INT' => array('1', \TYPO3\CMS\Core\Database\PreparedStatement::PARAM_INT),
			'bool passed with param type INT' => array(TRUE, \TYPO3\CMS\Core\Database\PreparedStatement::PARAM_INT),
			'NULL passed with param type BOOL' => array(NULL, \TYPO3\CMS\Core\Database\PreparedStatement::PARAM_BOOL),
			'string passed with param type BOOL' => array('1', \TYPO3\CMS\Core\Database\PreparedStatement::PARAM_BOOL),
			'integer passed with param type BOOL' => array(1, \TYPO3\CMS\Core\Database\PreparedStatement::PARAM_BOOL)
		);
	}

	/**
	 * Checking if an exception is thrown if invalid parameters are
	 * provided vor bindValue().
	 *
	 * @test
	 * @expectedException \InvalidArgumentException
	 * @dataProvider invalidParameterTypesPassedToBindValueThrowsExceptionDataProvider
	 * @param mixed   $parameter	Parameter to be replaced in the query
	 * @param integer $type			Type of the parameter value
	 * @return void
	 */
	public function invalidParameterTypesPassedToBindValueThrowsException($parameter, $type) {
		$statement = $this->createPreparedStatement('');
		$statement->bindValue(1, $parameter, $type);
	}

	/**
	 * Checking if formerly bound values are replaced by the values passed to execute().
	 *
	 * @test
	 * @return void
	 */
	public function parametersPassedToExecuteOverrulesFormerlyBoundValues() {
		$query = 'SELECT * FROM pages WHERE pid=? OR uid=?';
		$expectedResult = 'SELECT * FROM pages WHERE pid=30 OR uid=40';
		$this->databaseStub->expects($this->any())->method('exec_PREPAREDquery')->with($this->equalTo($expectedResult));
		$statement = $this->createPreparedStatement($query);
		$statement->bindValues(array(10, 20));
		$statement->execute(array(30, 40));
	}

	/**
	 * Data Provieder for invalid marker names.
	 *
	 * @see passingInvalidMarkersThrowsExeption
	 * @return array
	 */
	public function passingInvalidMarkersThrowsExeptionDataProvider() {
		return array(
			'using other prefix than colon' => array('SELECT * FROM pages WHERE pid=#pid', array('#pid' => 1)),
			'using non alphanumerical character' => array('SELECT * FROM pages WHERE title=:stra≠e', array(':stra≠e' => 1)),
			'no colon used' => array('SELECT * FROM pages WHERE pid=pid', array('pid' => 1)),
			'colon at the end' => array('SELECT * FROM pages WHERE pid=pid:', array('pid:' => 1)),
			'colon without alphanumerical character' => array('SELECT * FROM pages WHERE pid=:', array(':' => 1))
		);
	}

	/**
	 * Checks if an exception is thrown, if parameter have invalid marker named.
	 *
	 * @test
	 * @expectedException \InvalidArgumentException
	 * @dataProvider passingInvalidMarkersThrowsExeptionDataProvider
	 * @param string $query				Query with unreplaced markers
	 * @param array  $parameters		Array of parameters to be replaced in the query
	 * @return void
	 */
	public function passingInvalidMarkersThrowsExeption($query, $parameters) {
		$statement = $this->createPreparedStatement($query);
		$statement->execute($parameters);
	}

}

?>