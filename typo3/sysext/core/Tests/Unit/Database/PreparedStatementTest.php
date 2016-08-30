<?php
namespace TYPO3\CMS\Core\Tests\Unit\Database;

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

/**
 * Test case
 */
class PreparedStatementTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Database\DatabaseConnection
     */
    protected $databaseStub;

    /**
     * Create a new database mock object for every test
     * and backup the original global database object.
     *
     * @return void
     */
    protected function setUp()
    {
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
    private function setUpAndReturnDatabaseStub()
    {
        $GLOBALS['TYPO3_DB'] = $this->getAccessibleMock(
            \TYPO3\CMS\Core\Database\DatabaseConnection::class,
            ['prepare_PREPAREDquery'],
            [],
            '',
            false,
            false
        );

        return $GLOBALS['TYPO3_DB'];
    }

    /**
     * Create an object fo the subject to be tested.
     *
     * @param string $query
     * @return \TYPO3\CMS\Core\Database\PreparedStatement
     */
    private function createPreparedStatement($query)
    {
        return new \TYPO3\CMS\Core\Database\PreparedStatement($query, 'pages');
    }

    ////////////////////////////////////
    // Tests for the utility functions
    ////////////////////////////////////

    /**
     * @test
     * @return void
     */
    public function setUpAndReturnDatabaseStubReturnsMockObjectOfDatabaseConnection()
    {
        $this->assertTrue($this->setUpAndReturnDatabaseStub() instanceof \TYPO3\CMS\Core\Database\DatabaseConnection);
    }

    /**
     * @test
     * @return void
     */
    public function createPreparedStatementReturnsInstanceOfPreparedStatementClass()
    {
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
    public function parametersAndQueriesDataProvider()
    {
        return [
            'one named integer parameter' => ['SELECT * FROM pages WHERE pid=:pid', [':pid' => 1], 'SELECT * FROM pages WHERE pid=?'],
            'one unnamed integer parameter' => ['SELECT * FROM pages WHERE pid=?', [1], 'SELECT * FROM pages WHERE pid=?'],
            'one named integer parameter is replaced multiple times' => ['SELECT * FROM pages WHERE pid=:pid OR uid=:pid', [':pid' => 1], 'SELECT * FROM pages WHERE pid=? OR uid=?'],
            'two named integer parameters are replaced' => ['SELECT * FROM pages WHERE pid=:pid OR uid=:uid', [':pid' => 1, ':uid' => 10], 'SELECT * FROM pages WHERE pid=? OR uid=?'],
            'two unnamed integer parameters are replaced' => ['SELECT * FROM pages WHERE pid=? OR uid=?', [1, 1], 'SELECT * FROM pages WHERE pid=? OR uid=?'],
        ];
    }

    /**
     * Checking if calling execute() with parameters, they are
     * properly replaced in the query.
     *
     * @test
     * @dataProvider parametersAndQueriesDataProvider
     * @param string $query Query with unreplaced markers
     * @param array  $parameters Array of parameters to be replaced in the query
     * @param string $expectedResult Query with all markers replaced
     * @return void
     */
    public function parametersAreReplacedByQuestionMarkInQueryByCallingExecute($query, $parameters, $expectedResult)
    {
        $statement = $this->createPreparedStatement($query);
        $this->databaseStub->expects($this->any())->method('prepare_PREPAREDquery')->with($this->equalTo($expectedResult));
        $statement->execute($parameters);
    }

    /**
     * Checking if parameters bound to the statement by bindValues()
     * are properly replaced in the query.
     *
     * @test
     * @dataProvider parametersAndQueriesDataProvider
     * @param string $query Query with unreplaced markers
     * @param array  $parameters Array of parameters to be replaced in the query
     * @param string $expectedResult Query with all markers replaced
     * @return void
     */
    public function parametersAreReplacedInQueryWhenBoundWithBindValues($query, $parameters, $expectedResult)
    {
        $statement = $this->createPreparedStatement($query);
        $this->databaseStub->expects($this->any())->method('prepare_PREPAREDquery')->with($this->equalTo($expectedResult));
        $statement->bindValues($parameters);
        $statement->execute();
    }

    /**
     * Data Provider with invalid parameters.
     *
     * @see invalidParameterTypesPassedToBindValueThrowsException
     * @return array
     */
    public function invalidParameterTypesPassedToBindValueThrowsExceptionDataProvider()
    {
        return [
            'integer passed with param type NULL' => [1, \TYPO3\CMS\Core\Database\PreparedStatement::PARAM_NULL],
            'string passed with param type NULL' => ['1', \TYPO3\CMS\Core\Database\PreparedStatement::PARAM_NULL],
            'bool passed with param type NULL' => [true, \TYPO3\CMS\Core\Database\PreparedStatement::PARAM_NULL],
            'NULL passed with param type INT' => [null, \TYPO3\CMS\Core\Database\PreparedStatement::PARAM_INT],
            'string passed with param type INT' => ['1', \TYPO3\CMS\Core\Database\PreparedStatement::PARAM_INT],
            'bool passed with param type INT' => [true, \TYPO3\CMS\Core\Database\PreparedStatement::PARAM_INT],
            'NULL passed with param type BOOL' => [null, \TYPO3\CMS\Core\Database\PreparedStatement::PARAM_BOOL],
            'string passed with param type BOOL' => ['1', \TYPO3\CMS\Core\Database\PreparedStatement::PARAM_BOOL],
            'integer passed with param type BOOL' => [1, \TYPO3\CMS\Core\Database\PreparedStatement::PARAM_BOOL]
        ];
    }

    /**
     * Checking if an exception is thrown if invalid parameters are
     * provided vor bindValue().
     *
     * @test
     * @expectedException \InvalidArgumentException
     * @dataProvider invalidParameterTypesPassedToBindValueThrowsExceptionDataProvider
     * @param mixed $parameter Parameter to be replaced in the query
     * @param int $type Type of the parameter value
     * @return void
     */
    public function invalidParameterTypesPassedToBindValueThrowsException($parameter, $type)
    {
        $statement = $this->createPreparedStatement('');
        $statement->bindValue(1, $parameter, $type);
    }

    /**
     * Data Provider for invalid marker names.
     *
     * @see passingInvalidMarkersThrowsExeption
     * @return array
     */
    public function passingInvalidMarkersThrowsExceptionDataProvider()
    {
        return [
            'using other prefix than colon' => ['SELECT * FROM pages WHERE pid=#pid', ['#pid' => 1]],
            'using non alphanumerical character' => ['SELECT * FROM pages WHERE title=:stra≠e', [':stra≠e' => 1]],
            'no colon used' => ['SELECT * FROM pages WHERE pid=pid', ['pid' => 1]],
            'colon at the end' => ['SELECT * FROM pages WHERE pid=pid:', ['pid:' => 1]],
            'colon without alphanumerical character' => ['SELECT * FROM pages WHERE pid=:', [':' => 1]]
        ];
    }

    /**
     * Checks if an exception is thrown, if parameter have invalid marker named.
     *
     * @test
     * @expectedException \InvalidArgumentException
     * @dataProvider passingInvalidMarkersThrowsExceptionDataProvider
     * @param string $query Query with unreplaced markers
     * @param array  $parameters Array of parameters to be replaced in the query
     * @return void
     */
    public function passingInvalidMarkersThrowsException($query, $parameters)
    {
        $statement = $this->createPreparedStatement($query);
        $statement->bindValues($parameters);
    }
}
