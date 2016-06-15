<?php
namespace TYPO3\CMS\Dbal\Tests\Unit\Database;

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

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Test case
 */
class DatabaseConnectionTest extends AbstractTestCase
{
    /**
     * @var \TYPO3\CMS\Dbal\Database\DatabaseConnection|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface
     */
    protected $subject;

    /**
     * Set up
     */
    protected function setUp()
    {
        $GLOBALS['TYPO3_LOADED_EXT'] = array();

        /** @var \TYPO3\CMS\Dbal\Database\DatabaseConnection|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface $subject */
        $subject = $this->getAccessibleMock(\TYPO3\CMS\Dbal\Database\DatabaseConnection::class, array('getFieldInfoCache'), array(), '', false);

        // Disable caching
        $mockCacheFrontend = $this->createMock(\TYPO3\CMS\Core\Cache\Frontend\PhpFrontend::class);
        $subject->expects($this->any())->method('getFieldInfoCache')->will($this->returnValue($mockCacheFrontend));

        // Inject SqlParser - Its logic is tested with the tests, too.
        $sqlParser = $this->getAccessibleMock(\TYPO3\CMS\Dbal\Database\SqlParser::class, array('dummy'), array(), '', false);
        $sqlParser->_set('databaseConnection', $subject);
        $subject->SQLparser = $sqlParser;

        // Mock away schema migration service from install tool
        $installerSqlMock = $this->getMockBuilder(\TYPO3\CMS\Install\Service\SqlSchemaMigrationService::class)
            ->setMethods(array('getFieldDefinitions_fileContent'))
            ->disableOriginalConstructor()
            ->getMock();
        $installerSqlMock->expects($this->any())->method('getFieldDefinitions_fileContent')->will($this->returnValue(array()));
        $subject->_set('installerSql', $installerSqlMock);

        // Inject DBMS specifics
        $subject->_set('dbmsSpecifics', GeneralUtility::makeInstance(\TYPO3\CMS\Dbal\Database\Specifics\NullSpecifics::class));

        $subject->initialize();
        $subject->lastHandlerKey = '_DEFAULT';

        $this->subject = $subject;
    }

    /**
     * Creates a fake extension with a given table definition.
     *
     * @param string $tableDefinition SQL script to create the extension's tables
     * @throws \RuntimeException
     * @return void
     */
    protected function createFakeExtension($tableDefinition)
    {
        // Prepare a fake extension configuration
        $ext_tables = GeneralUtility::tempnam('ext_tables');
        if (!GeneralUtility::writeFile($ext_tables, $tableDefinition)) {
            throw new \RuntimeException('Can\'t write temporary ext_tables file.');
        }
        $this->testFilesToDelete[] = $ext_tables;
        $GLOBALS['TYPO3_LOADED_EXT'] = array(
            'test_dbal' => array(
                'ext_tables.sql' => $ext_tables
            )
        );
        // Append our test table to the list of existing tables
        $this->subject->initialize();
    }

    /**
     * @test
     */
    public function tableWithMappingIsDetected()
    {
        $dbalConfiguration = array(
            'mapping' => array(
                'cf_cache_hash' => array(),
            ),
        );

        /** @var \TYPO3\CMS\Dbal\Database\DatabaseConnection|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface $subject */
        $subject = $this->getAccessibleMock(\TYPO3\CMS\Dbal\Database\DatabaseConnection::class, array('getFieldInfoCache'), array(), '', false);

        $mockCacheFrontend = $this->createMock(\TYPO3\CMS\Core\Cache\Frontend\PhpFrontend::class);
        $subject->expects($this->any())->method('getFieldInfoCache')->will($this->returnValue($mockCacheFrontend));

        $sqlParser = $this->getAccessibleMock(\TYPO3\CMS\Dbal\Database\SqlParser::class, array('dummy'), array(), '', false);
        $sqlParser->_set('databaseConnection', $subject);
        $subject->SQLparser = $sqlParser;

        $installerSqlMock = $this->createMock(\TYPO3\CMS\Install\Service\SqlSchemaMigrationService::class);
        $subject->_set('installerSql', $installerSqlMock);
        $schemaMigrationResult = array(
            'cf_cache_pages' => array(),
        );
        $installerSqlMock->expects($this->once())->method('getFieldDefinitions_fileContent')->will($this->returnValue($schemaMigrationResult));

        $subject->conf = $dbalConfiguration;
        $subject->initialize();
        $subject->lastHandlerKey = '_DEFAULT';

        $this->assertFalse($subject->_call('map_needMapping', 'cf_cache_pages'));
        $cfCacheHashNeedsMapping = $subject->_call('map_needMapping', 'cf_cache_hash');
        $this->assertEquals('cf_cache_hash', $cfCacheHashNeedsMapping[0]['table']);
    }

    /**
     * @test
     * @see https://forge.typo3.org/issues/67067
     */
    public function adminGetTablesReturnsArrayWithNameKey()
    {
        $handlerMock = $this->getMockBuilder('\ADODB_mock')
            ->setMethods(array('MetaTables'))
            ->disableOriginalConstructor()
            ->getMock();
        $handlerMock->expects($this->any())->method('MetaTables')->will($this->returnValue(array('cf_cache_hash')));
        $this->subject->handlerCfg['_DEFAULT']['type'] = 'adodb';
        $this->subject->handlerInstance['_DEFAULT'] = $handlerMock;

        $actual = $this->subject->admin_get_tables();
        $expected = array('cf_cache_hash' => array('Name' => 'cf_cache_hash'));
        $this->assertSame($expected, $actual);
    }

    /**
     * @test
     * @see http://forge.typo3.org/issues/21502
     */
    public function concatCanBeParsedAfterLikeOperator()
    {
        $result = $this->subject->SELECTquery('*', 'sys_refindex, tx_dam_file_tracking', 'sys_refindex.tablename = \'tx_dam_file_tracking\'' . ' AND sys_refindex.ref_string LIKE CONCAT(tx_dam_file_tracking.file_path, tx_dam_file_tracking.file_name)');
        $expected = 'SELECT * FROM sys_refindex, tx_dam_file_tracking WHERE sys_refindex.tablename = \'tx_dam_file_tracking\'';
        $expected .= ' AND sys_refindex.ref_string LIKE CONCAT(tx_dam_file_tracking.file_path, tx_dam_file_tracking.file_name)';
        $this->assertEquals($expected, $this->cleanSql($result));
    }

    /**
     * @test
     * @see http://forge.typo3.org/issues/20346
     */
    public function floatNumberCanBeStoredInDatabase()
    {
        $this->createFakeExtension('
			CREATE TABLE tx_test_dbal (
				foo double default \'0\',
				foobar int default \'0\'
			);
		');
        $data = array(
            'foo' => 99.12,
            'foobar' => -120
        );
        $result = $this->subject->INSERTquery('tx_test_dbal', $data);
        $expected = 'INSERT INTO tx_test_dbal ( foo, foobar ) VALUES ( \'99.12\', \'-120\' )';
        $this->assertEquals($expected, $this->cleanSql($result));
    }

    /**
     * @test
     * @see http://forge.typo3.org/issues/20427
     */
    public function positive64BitIntegerIsSupported()
    {
        if (!is_int(9223372036854775806)) {
            $this->markTestSkipped('Test skipped because running on 32 bit system.');
        }
        $this->createFakeExtension('
			CREATE TABLE tx_test_dbal (
				foo int default \'0\',
				foobar bigint default \'0\'
			);
		');
        $data = array(
            'foo' => 9223372036854775807,
            'foobar' => 9223372036854775807
        );
        $result = $this->subject->INSERTquery('tx_test_dbal', $data);
        $expected = 'INSERT INTO tx_test_dbal ( foo, foobar ) VALUES ( \'9223372036854775807\', \'9223372036854775807\' )';
        $this->assertEquals($expected, $this->cleanSql($result));
    }

    /**
     * @test
     */
    public function sqlForInsertWithMultipleRowsIsValid()
    {
        $fields = array('uid', 'pid', 'title', 'body');
        $rows = array(
            array('1', '2', 'Title #1', 'Content #1'),
            array('3', '4', 'Title #2', 'Content #2'),
            array('5', '6', 'Title #3', 'Content #3')
        );
        $result = $this->subject->INSERTmultipleRows('tt_content', $fields, $rows);
        $expected = 'INSERT INTO tt_content (uid, pid, title, body) VALUES ';
        $expected .= '(\'1\', \'2\', \'Title #1\', \'Content #1\'), ';
        $expected .= '(\'3\', \'4\', \'Title #2\', \'Content #2\'), ';
        $expected .= '(\'5\', \'6\', \'Title #3\', \'Content #3\')';
        $this->assertEquals($expected, $this->cleanSql($result));
    }

    /**
     * @test
     */
    public function sqlForSelectMmQuery()
    {
        $result = $this->subject->SELECT_mm_query('*', 'sys_category', 'sys_category_record_mm', 'tt_content', 'AND sys_category.uid = 1', '', 'sys_category.title DESC');
        $expected = 'SELECT * FROM sys_category,sys_category_record_mm,tt_content WHERE sys_category.uid=sys_category_record_mm.uid_local AND tt_content.uid=sys_category_record_mm.uid_foreign AND sys_category.uid = 1 ORDER BY sys_category.title DESC';
        $this->assertEquals($expected, $result);
    }

    /**
     * @test
     * @see http://forge.typo3.org/issues/16708
     */
    public function minFunctionAndInOperatorCanBeParsed()
    {
        $result = $this->subject->SELECTquery('*', 'pages', 'MIN(uid) IN (1,2,3,4)');
        $expected = 'SELECT * FROM pages WHERE MIN(uid) IN (1,2,3,4)';
        $this->assertEquals($expected, $this->cleanSql($result));
    }

    /**
     * @test
     * @see http://forge.typo3.org/issues/16708
     */
    public function maxFunctionAndInOperatorCanBeParsed()
    {
        $result = $this->subject->SELECTquery('*', 'pages', 'MAX(uid) IN (1,2,3,4)');
        $expected = 'SELECT * FROM pages WHERE MAX(uid) IN (1,2,3,4)';
        $this->assertEquals($expected, $this->cleanSql($result));
    }

    /**
     * @test
     * @see https://forge.typo3.org/issues/71979
     */
    public function canCompileCastOperatorWithOrComparator()
    {
        $result = $this->subject->SELECTquery('uid', 'sys_category', 'FIND_IN_SET(\'0\',parent) != 0 OR CAST(parent AS CHAR) = \'\'');
        $expected = 'SELECT uid FROM sys_category WHERE FIND_IN_SET(\'0\',parent) != 0 OR CAST(parent AS CHAR) = \'\'';
        $this->assertEquals($expected, $this->cleanSql($result));
    }

    /**
     * @test
     * @see http://forge.typo3.org/issues/21514
     */
    public function likeBinaryOperatorIsKept()
    {
        $result = $this->cleanSql($this->subject->SELECTquery('*', 'tt_content', 'bodytext LIKE BINARY \'test\''));
        $expected = 'SELECT * FROM tt_content WHERE bodytext LIKE BINARY \'test\'';
        $this->assertEquals($expected, $this->cleanSql($result));
    }

    /**
     * @test
     * @see http://forge.typo3.org/issues/21514
     */
    public function notLikeBinaryOperatorIsKept()
    {
        $result = $this->cleanSql($this->subject->SELECTquery('*', 'tt_content', 'bodytext NOT LIKE BINARY \'test\''));
        $expected = 'SELECT * FROM tt_content WHERE bodytext NOT LIKE BINARY \'test\'';
        $this->assertEquals($expected, $this->cleanSql($result));
    }

    ///////////////////////////////////////
    // Tests concerning prepared queries
    ///////////////////////////////////////
    /**
     * @test
     * @see http://forge.typo3.org/issues/23374
     */
    public function similarNamedParametersAreProperlyReplaced()
    {
        $sql = 'SELECT * FROM cache WHERE tag = :tag1 OR tag = :tag10 OR tag = :tag100';
        $parameterValues = array(
            ':tag1' => 'tag-one',
            ':tag10' => 'tag-two',
            ':tag100' => 'tag-three'
        );
        $className = self::buildAccessibleProxy(\TYPO3\CMS\Core\Database\PreparedStatement::class);
        $query = $sql;
        $precompiledQueryParts = array();
        $statement = new $className($sql, 'cache');
        $statement->bindValues($parameterValues);
        $parameters = $statement->_get('parameters');
        $statement->_callRef('convertNamedPlaceholdersToQuestionMarks', $query, $parameters, $precompiledQueryParts);
        $expectedQuery = 'SELECT * FROM cache WHERE tag = ? OR tag = ? OR tag = ?';
        $expectedParameterValues = array(
            0 => array(
                'type' => \TYPO3\CMS\Core\Database\PreparedStatement::PARAM_STR,
                'value' => 'tag-one',
            ),
            1 => array(
                'type' => \TYPO3\CMS\Core\Database\PreparedStatement::PARAM_STR,
                'value' => 'tag-two',
            ),
            2 => array(
                'type' => \TYPO3\CMS\Core\Database\PreparedStatement::PARAM_STR,
                'value' => 'tag-three',
            ),
        );
        $this->assertEquals($expectedQuery, $query);
        $this->assertEquals($expectedParameterValues, $parameters);
    }

    ///////////////////////////////////////
    // Tests concerning indexes
    ///////////////////////////////////////
    /**
     * @test
     * @param string $indexSQL
     * @param string $expected
     * @dataProvider equivalentIndexDefinitionDataProvider
     */
    public function equivalentIndexDefinitionRemovesLengthInformation($indexSQL, $expected)
    {
        $result = $this->subject->getEquivalentIndexDefinition($indexSQL);
        $this->assertSame($expected, $result);
    }

    /**
     * @return array
     */
    public function equivalentIndexDefinitionDataProvider()
    {
        return array(
            array('KEY (foo,bar(199))', 'KEY (foo,bar)'),
            array('KEY (foo(199), bar)', 'KEY (foo, bar)'),
            array('KEY (foo(199),bar(199))', 'KEY (foo,bar)'),
        );
    }
}
