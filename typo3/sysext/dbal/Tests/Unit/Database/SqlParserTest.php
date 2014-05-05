<?php
namespace TYPO3\CMS\Dbal\Tests\Unit\Database;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2009-2014 Xavier Perseguers <xavier@typo3.org>
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
 * Test case
 */
class SqlParserTest extends AbstractTestCase {

	/**
	 * @var \TYPO3\CMS\Dbal\Database\SqlParser|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface
	 */
	protected $subject;

	/**
	 * Prepares the environment before running a test.
	 */
	public function setUp() {
		$subject = $this->getAccessibleMock('TYPO3\\CMS\\Dbal\\Database\\SqlParser', array('dummy'), array(), '', FALSE);

		$mockDatabaseConnection = $this->getMock('TYPO3\\CMS\\Dbal\\Database\\DatabaseConnection', array(), array(), '', FALSE);
		$mockDatabaseConnection->lastHandlerKey = '_DEFAULT';
		$subject->_set('databaseConnection', $mockDatabaseConnection);

		$this->subject = $subject;
	}

	/**
	 * @test
	 */
	public function canExtractPartsOfAQuery() {
		$parseString = 'SELECT   *' . LF . 'FROM pages WHERE pid IN (1,2,3,4)';
		$regex = '^SELECT[[:space:]]+(.*)[[:space:]]+';
		$trimAll = TRUE;
		$fields = $this->subject->_callRef('nextPart', $parseString, $regex, $trimAll);
		$this->assertEquals('*', $fields);
		$this->assertEquals('FROM pages WHERE pid IN (1,2,3,4)', $parseString);
		$regex = '^FROM ([^)]+) WHERE';
		$table = $this->subject->_callRef('nextPart', $parseString, $regex);
		$this->assertEquals('pages', $table);
		$this->assertEquals('pages WHERE pid IN (1,2,3,4)', $parseString);
	}

	/**
	 * @test
	 */
	public function canGetIntegerValue() {
		$parseString = '1024';
		$result = $this->subject->_callRef('getValue', $parseString);
		$expected = array(1024);
		$this->assertEquals($expected, $result);
	}

	/**
	 * @test
	 * @see http://forge.typo3.org/issues/21887
	 */
	public function canGetStringValue() {
		$parseString = '"some owner\\\'s string"';
		$result = $this->subject->_callRef('getValue', $parseString);
		$expected = array('some owner\'s string', '"');
		$this->assertEquals($expected, $result);
	}

	/**
	 * @test
	 * @see http://forge.typo3.org/issues/21887
	 */
	public function canGetStringValueWithSingleQuote() {
		$parseString = '\'some owner\\\'s string\'';
		$result = $this->subject->_callRef('getValue', $parseString);
		$expected = array('some owner\'s string', '\'');
		$this->assertEquals($expected, $result);
	}

	/**
	 * @test
	 * @see http://forge.typo3.org/issues/21887
	 */
	public function canGetStringValueWithDoubleQuote() {
		$parseString = '"the \\"owner\\" is here"';
		$result = $this->subject->_callRef('getValue', $parseString);
		$expected = array('the "owner" is here', '"');
		$this->assertEquals($expected, $result);
	}

	/**
	 * @test
	 */
	public function canGetListOfValues() {
		$parseString = '( 1,   2, 3  ,4)';
		$operator = 'IN';
		$result = $this->subject->_callRef('getValue', $parseString, $operator);
		$expected = array(
			array(1),
			array(2),
			array(3),
			array(4)
		);
		$this->assertEquals($expected, $result);
	}

	/**
	 * @test
	 */
	public function parseWhereClauseReturnsArray() {
		$parseString = 'uid IN (1,2) AND (starttime < ' . time() . ' OR cruser_id + 10 < 20)';
		$result = $this->subject->parseWhereClause($parseString);
		$this->assertInternalType('array', $result);
		$this->assertEmpty($parseString);
	}

	/**
	 * @test
	 */
	public function canSelectAllFieldsFromPages() {
		$sql = 'SELECT * FROM pages';
		$expected = $sql;
		$result = $this->subject->debug_testSQL($sql);
		$this->assertEquals($expected, $this->cleanSql($result));
	}

	/**
	 * @test
	 */
	public function canParseTruncateTable() {
		$sql = 'TRUNCATE TABLE be_users';
		$expected = $sql;
		$result = $this->subject->debug_testSQL($sql);
		$this->assertEquals($expected, $this->cleanSql($result));
	}

	/**
	 * @test
	 * @see http://forge.typo3.org/issues/22049
	 */
	public function canParseAndCompileBetweenOperator() {
		$parseString = '((scheduled BETWEEN 1265068628 AND 1265068828 ) OR scheduled <= 1265068728) AND NOT exec_time AND NOT process_id AND page_id=1 AND parameters_hash = \'854e9a2a77\'';
		$result = $this->subject->parseWhereClause($parseString);
		$this->assertInternalType('array', $result);
		$this->assertEmpty($parseString);

		$result = $this->subject->compileWhereClause($result);
		$expected = '((scheduled BETWEEN 1265068628 AND 1265068828) OR scheduled <= 1265068728) AND NOT exec_time AND NOT process_id AND page_id = 1 AND parameters_hash = \'854e9a2a77\'';
		$this->assertEquals($expected, $this->cleanSql($result));
	}

	/**
	 * @test
	 */
	public function canParseInsertWithoutSpaceAfterValues() {
		$parseString = 'INSERT INTO static_country_zones VALUES(\'483\', \'0\', \'NL\', \'NLD\', \'528\', \'DR\', \'Drenthe\', \'\');';
		$components = $this->subject->_callRef('parseINSERT', $parseString);
		$this->assertInternalType('array', $components);

		$result = $this->subject->_callRef('compileINSERT', $components);
		$expected = 'INSERT INTO static_country_zones VALUES (\'483\', \'0\', \'NL\', \'NLD\', \'528\', \'DR\', \'Drenthe\', \'\')';
		$this->assertEquals($expected, $this->cleanSql($result));
	}

	/**
	 * @test
	 */
	public function canParseInsertWithSpaceAfterValues() {
		$parseString = 'INSERT INTO static_country_zones VALUES (\'483\', \'0\', \'NL\', \'NLD\', \'528\', \'DR\', \'Drenthe\', \'\');';
		$components = $this->subject->_callRef('parseINSERT', $parseString);
		$this->assertInternalType('array', $components);

		$result = $this->subject->_callRef('compileINSERT', $components);
		$expected = 'INSERT INTO static_country_zones VALUES (\'483\', \'0\', \'NL\', \'NLD\', \'528\', \'DR\', \'Drenthe\', \'\')';
		$this->assertEquals($expected, $this->cleanSql($result));
	}

	/**
	 * @test
	 */
	public function canParseInsertWithFields() {
		$parseString = 'INSERT INTO static_territories (uid, pid, tr_iso_nr, tr_parent_iso_nr, tr_name_en) ';
		$parseString .= 'VALUES (\'1\', \'0\', \'2\', \'0\', \'Africa\');';
		$components = $this->subject->_callRef('parseINSERT', $parseString);
		$this->assertInternalType('array', $components);

		$result = $this->subject->_callRef('compileINSERT', $components);
		$expected = 'INSERT INTO static_territories (uid, pid, tr_iso_nr, tr_parent_iso_nr, tr_name_en) ';
		$expected .= 'VALUES (\'1\', \'0\', \'2\', \'0\', \'Africa\')';
		$this->assertEquals($expected, $this->cleanSql($result));
	}

	/**
	 * @test
	 */
	public function canParseExtendedInsert() {
		$parseString = 'INSERT INTO static_territories VALUES (\'1\', \'0\', \'2\', \'0\', \'Africa\'),(\'2\', \'0\', \'9\', \'0\', \'Oceania\'),' . '(\'3\', \'0\', \'19\', \'0\', \'Americas\'),(\'4\', \'0\', \'142\', \'0\', \'Asia\');';
		$components = $this->subject->_callRef('parseINSERT', $parseString);
		$this->assertInternalType('array', $components);

		$result = $this->subject->_callRef('compileINSERT', $components);
		$expected = 'INSERT INTO static_territories VALUES (\'1\', \'0\', \'2\', \'0\', \'Africa\'), (\'2\', \'0\', \'9\', \'0\', \'Oceania\'), ' . '(\'3\', \'0\', \'19\', \'0\', \'Americas\'), (\'4\', \'0\', \'142\', \'0\', \'Asia\')';
		$this->assertEquals($expected, $this->cleanSql($result));
	}

	/**
	 * @test
	 */
	public function canParseExtendedInsertWithFields() {
		$parseString = 'INSERT INTO static_territories (uid, pid, tr_iso_nr, tr_parent_iso_nr, tr_name_en) ';
		$parseString .= 'VALUES (\'1\', \'0\', \'2\', \'0\', \'Africa\'),(\'2\', \'0\', \'9\', \'0\', \'Oceania\');';
		$components = $this->subject->_callRef('parseINSERT', $parseString);
		$this->assertInternalType('array', $components);

		$result = $this->subject->_callRef('compileINSERT', $components);
		$expected = 'INSERT INTO static_territories (uid, pid, tr_iso_nr, tr_parent_iso_nr, tr_name_en) ';
		$expected .= 'VALUES (\'1\', \'0\', \'2\', \'0\', \'Africa\'), (\'2\', \'0\', \'9\', \'0\', \'Oceania\')';
		$this->assertEquals($expected, $this->cleanSql($result));
	}

	/**
	 * @test
	 * @see http://forge.typo3.org/issues/17552
	 */
	public function canParseIfNullOperator() {
		$parseString = 'IFNULL(tt_news_cat_mm.uid_foreign,0) IN (21,22)';
		$result = $this->subject->parseWhereClause($parseString);
		$this->assertInternalType('array', $result);
		$this->assertEmpty($parseString);
	}

	/**
	 * @test
	 * @see http://forge.typo3.org/issues/17552
	 */
	public function canParseIfNullOperatorWithAdditionalClauses() {
		$parseString = '1=1 AND IFNULL(tt_news_cat_mm.uid_foreign,0) IN (21,22) AND tt_news.sys_language_uid IN (0,-1) ';
		$parseString .= 'AND tt_news.pid > 0 AND tt_news.pid IN (61) AND tt_news.deleted=0 AND tt_news.t3ver_state<=0 ';
		$parseString .= 'AND tt_news.hidden=0 AND tt_news.starttime<=1266065460 AND (tt_news.endtime=0 OR tt_news.endtime>1266065460) ';
		$parseString .= 'AND (tt_news.fe_group=\'\' OR tt_news.fe_group IS NULL OR tt_news.fe_group=\'0\' ';
		$parseString .= 'OR (tt_news.fe_group LIKE \'%,0,%\' OR tt_news.fe_group LIKE \'0,%\' OR tt_news.fe_group LIKE \'%,0\' ';
		$parseString .= 'OR tt_news.fe_group=\'0\') OR (tt_news.fe_group LIKE \'%,-1,%\' OR tt_news.fe_group LIKE \'-1,%\' ';
		$parseString .= 'OR tt_news.fe_group LIKE \'%,-1\' OR tt_news.fe_group=\'-1\'))';

		$result = $this->subject->parseWhereClause($parseString);
		$this->assertInternalType('array', $result);
		$this->assertEmpty($parseString);
	}

	/**
	 * @test
	 * @see http://forge.typo3.org/issues/17552
	 */
	public function canCompileIfNullOperator() {
		$parseString = 'SELECT * FROM tx_irfaq_q_cat_mm WHERE IFNULL(tx_irfaq_q_cat_mm.uid_foreign,0) = 1';
		$components = $this->subject->_callRef('parseSELECT', $parseString);
		$this->assertInternalType('array', $components);

		$result = $this->subject->_callRef('compileSELECT', $components);
		$expected = 'SELECT * FROM tx_irfaq_q_cat_mm WHERE IFNULL(tx_irfaq_q_cat_mm.uid_foreign, 0) = 1';
		$this->assertEquals($expected, $this->cleanSql($result));
	}

	/**
	 * @test
	 * @see http://forge.typo3.org/issues/22695
	 */
	public function canParseAlterEngineStatement() {
		$parseString = 'ALTER TABLE tx_realurl_pathcache ENGINE=InnoDB';
		$components = $this->subject->_callRef('parseALTERTABLE', $parseString);
		$this->assertInternalType('array', $components);

		$result = $this->subject->_callRef('compileALTERTABLE', $components);
		$expected = 'ALTER TABLE tx_realurl_pathcache ENGINE = InnoDB';
		$this->assertEquals($expected, $this->cleanSql($result));
	}

	/**
	 * @test
	 * @see http://forge.typo3.org/issues/22731
	 */
	public function canParseAlterCharacterSetStatement() {
		$parseString = 'ALTER TABLE `index_phash` DEFAULT CHARACTER SET utf8';
		$components = $this->subject->_callRef('parseALTERTABLE', $parseString);
		$this->assertInternalType('array', $components);

		$result = $this->subject->_callRef('compileALTERTABLE', $components);
		$expected = 'ALTER TABLE index_phash DEFAULT CHARACTER SET utf8';
		$this->assertEquals($expected, $this->cleanSql($result));
	}

	/**
	 * @test
	 * @see http://forge.typo3.org/issues/23087
	 */
	public function canParseFindInSetStatement() {
		$parseString = 'SELECT * FROM fe_users WHERE FIND_IN_SET(10, usergroup)';
		$components = $this->subject->_callRef('parseSELECT', $parseString);
		$this->assertInternalType('array', $components);

		$result = $this->subject->_callRef('compileSELECT', $components);
		$expected = 'SELECT * FROM fe_users WHERE FIND_IN_SET(10, usergroup)';
		$this->assertEquals($expected, $this->cleanSql($result));
	}

	/**
	 * @test
	 * @see http://forge.typo3.org/issues/27858
	 */
	public function canParseSingleQuote() {
		$parseString = 'SELECT * FROM pages WHERE title=\'1\\\'\' AND deleted=0';
		$result = $this->subject->_callRef('parseSELECT', $parseString);
		$this->assertInternalType('array', $result);
		$this->assertEmpty($result['parseString']);
	}

	///////////////////////////////////////
	// Tests concerning JOINs
	///////////////////////////////////////
	/**
	 * @test
	 */
	public function parseFromTablesWithInnerJoinReturnsArray() {
		$parseString = 'be_users INNER JOIN pages ON pages.cruser_id = be_users.uid';

		$result = $this->subject->parseFromTables($parseString);
		$this->assertInternalType('array', $result);
		$this->assertEmpty($parseString);
	}

	/**
	 * @test
	 */
	public function parseFromTablesWithLeftOuterJoinReturnsArray() {
		$parseString = 'be_users LEFT OUTER JOIN pages ON be_users.uid = pages.cruser_id';

		$result = $this->subject->parseFromTables($parseString);
		$this->assertInternalType('array', $result);
		$this->assertEmpty($parseString);
	}

	/**
	 * @test
	 * @see http://forge.typo3.org/issues/21555
	 */
	public function parseFromTablesWithRightOuterJoinReturnsArray() {
		$parseString = 'tx_powermail_fieldsets RIGHT JOIN tt_content ON tx_powermail_fieldsets.tt_content = tt_content.uid';

		$result = $this->subject->parseFromTables($parseString);
		$this->assertInternalType('array', $result);
		$this->assertEmpty($parseString);
	}

	/**
	 * @test
	 */
	public function parseFromTablesWithMultipleJoinsReturnsArray() {
		$parseString = 'be_users LEFT OUTER JOIN pages ON be_users.uid = pages.cruser_id INNER JOIN cache_pages cp ON cp.page_id = pages.uid';
		$result = $this->subject->parseFromTables($parseString);
		$this->assertInternalType('array', $result);
		$this->assertEmpty($parseString);
	}

	/**
	 * @test
	 * @see http://forge.typo3.org/issues/21555
	 */
	public function parseFromTablesWithMultipleJoinsAndParenthesesReturnsArray() {
		$parseString = 'tx_powermail_fieldsets RIGHT JOIN tt_content ON tx_powermail_fieldsets.tt_content = tt_content.uid LEFT JOIN tx_powermail_fields ON tx_powermail_fieldsets.uid = tx_powermail_fields.fieldset';
		$result = $this->subject->parseFromTables($parseString);
		$this->assertInternalType('array', $result);
		$this->assertEmpty($parseString);
	}

	/**
	 * @test
	 */
	public function canUseInnerJoinInSelect() {
		$sql = 'SELECT pages.uid, be_users.username FROM be_users INNER JOIN pages ON pages.cruser_id = be_users.uid';

		$result = $this->subject->debug_testSQL($sql);
		$expected = 'SELECT pages.uid, be_users.username FROM be_users INNER JOIN pages ON pages.cruser_id=be_users.uid';
		$this->assertEquals($expected, $this->cleanSql($result));
	}

	/**
	 * @test
	 */
	public function canUseMultipleInnerJoinsInSelect() {
		$sql = 'SELECT * FROM tt_news_cat INNER JOIN tt_news_cat_mm ON tt_news_cat.uid = tt_news_cat_mm.uid_foreign INNER JOIN tt_news ON tt_news.uid = tt_news_cat_mm.uid_local';

		$result = $this->subject->debug_testSQL($sql);
		$expected = 'SELECT * FROM tt_news_cat INNER JOIN tt_news_cat_mm ON tt_news_cat.uid=tt_news_cat_mm.uid_foreign INNER JOIN tt_news ON tt_news.uid=tt_news_cat_mm.uid_local';
		$this->assertEquals($expected, $this->cleanSql($result));
	}

	/**
	 * @test
	 * @see http://forge.typo3.org/issues/22501
	 */
	public function canParseMultipleJoinConditions() {
		$sql = 'SELECT * FROM T1 LEFT OUTER JOIN T2 ON T2.pid = T1.uid AND T2.size = 4 WHERE T1.cr_userid = 1';

		$result = $this->subject->debug_testSQL($sql);
		$expected = 'SELECT * FROM T1 LEFT OUTER JOIN T2 ON T2.pid=T1.uid AND T2.size=4 WHERE T1.cr_userid = 1';
		$this->assertEquals($expected, $this->cleanSql($result));
	}

	/**
	 * @test
	 * @see http://forge.typo3.org/issues/22501
	 */
	public function canParseMultipleJoinConditionsWithLessThanOperator() {
		$sql = 'SELECT * FROM T1 LEFT OUTER JOIN T2 ON T2.size < 4 OR T2.pid = T1.uid WHERE T1.cr_userid = 1';

		$result = $this->subject->debug_testSQL($sql);
		$expected = 'SELECT * FROM T1 LEFT OUTER JOIN T2 ON T2.size<4 OR T2.pid=T1.uid WHERE T1.cr_userid = 1';
		$this->assertEquals($expected, $this->cleanSql($result));
	}

	///////////////////////////////////////
	// Tests concerning DB management
	///////////////////////////////////////
	/**
	 * @test
	 * @see http://forge.typo3.org/issues/16689
	 */
	public function indexMayContainALengthRestrictionInCreateTable() {
		$parseString = '
			CREATE TABLE tx_realurl_uniqalias (
				uid int(11) NOT NULL auto_increment,
				tstamp int(11) DEFAULT \'0\' NOT NULL,
				tablename varchar(60) DEFAULT \'\' NOT NULL,
				field_alias varchar(255) DEFAULT \'\' NOT NULL,
				field_id varchar(60) DEFAULT \'\' NOT NULL,
				value_alias varchar(255) DEFAULT \'\' NOT NULL,
				value_id int(11) DEFAULT \'0\' NOT NULL,
				lang int(11) DEFAULT \'0\' NOT NULL,
				expire int(11) DEFAULT \'0\' NOT NULL,

				PRIMARY KEY (uid),
				KEY tablename (tablename),
				KEY bk_realurl01 (field_alias,field_id,value_id,lang,expire),
				KEY bk_realurl02 (tablename,field_alias,field_id,value_alias(220),expire)
			);
		';
		$result = $this->subject->_callRef('parseCREATETABLE', $parseString);
		$this->assertInternalType('array', $result);
	}

	/**
	 * @test
	 * @see http://forge.typo3.org/issues/21730
	 */
	public function indexMayContainALengthRestrictionInAlterTable() {
		$parseString = 'ALTER TABLE tx_realurl_uniqalias ADD KEY bk_realurl02 (tablename,field_alias,field_id,value_alias(220),expire)';
		$result = $this->subject->_callRef('parseALTERTABLE', $parseString);
		$this->assertInternalType('array', $result);
	}

	/**
	 * @test
	 * @see http://forge.typo3.org/issues/15366
	 */
	public function canParseUniqueIndexCreation() {
		$sql = 'ALTER TABLE static_territories ADD UNIQUE uid (uid)';
		$expected = $sql;
		$alterTables = $this->subject->_callRef('parseALTERTABLE', $sql);
		$queries = $this->subject->compileSQL($alterTables);
		$this->assertEquals($expected, $queries);
	}

	///////////////////////////////////////
	// Tests concerning subqueries
	///////////////////////////////////////
	/**
	 * @test
	 * @see http://forge.typo3.org/issues/21688
	 */
	public function inWhereClauseSupportsSubquery() {
		$parseString = 'process_id IN (SELECT process_id FROM tx_crawler_process WHERE active=0 AND deleted=0)';
		$result = $this->subject->parseWhereClause($parseString);
		$this->assertInternalType('array', $result);
		$this->assertEmpty($parseString);
	}

	/**
	 * @test
	 * @see http://forge.typo3.org/issues/21688
	 */
	public function inWhereClauseWithSubqueryIsProperlyCompiled() {
		$sql = 'SELECT * FROM tx_crawler_queue WHERE process_id IN (SELECT process_id FROM tx_crawler_process WHERE active=0 AND deleted=0)';

		$result = $this->subject->debug_testSQL($sql);
		$expected = 'SELECT * FROM tx_crawler_queue WHERE process_id IN (SELECT process_id FROM tx_crawler_process WHERE active = 0 AND deleted = 0)';
		$this->assertEquals($expected, $this->cleanSql($result));
	}

	/**
	 * @test
	 * @see http://forge.typo3.org/issues/21688
	 */
	public function whereClauseSupportsExistsKeyword() {
		$parseString = 'EXISTS (SELECT * FROM tx_crawler_queue WHERE tx_crawler_queue.process_id = tx_crawler_process.process_id AND tx_crawler_queue.exec_time = 0)';
		$result = $this->subject->parseWhereClause($parseString);
		$this->assertInternalType('array', $result);
		$this->assertEmpty($parseString);
	}

	/**
	 * @test
	 * @see http://forge.typo3.org/issues/21688
	 */
	public function existsClauseIsProperlyCompiled() {
		$sql = 'SELECT * FROM tx_crawler_process WHERE active = 0 AND NOT EXISTS (SELECT * FROM tx_crawler_queue WHERE tx_crawler_queue.process_id = tx_crawler_process.process_id AND tx_crawler_queue.exec_time = 0)';

		$result = $this->subject->debug_testSQL($sql);
		$expected = 'SELECT * FROM tx_crawler_process WHERE active = 0 AND NOT EXISTS (SELECT * FROM tx_crawler_queue WHERE tx_crawler_queue.process_id = tx_crawler_process.process_id AND tx_crawler_queue.exec_time = 0)';
		$this->assertEquals($expected, $this->cleanSql($result));
	}

	///////////////////////////////////////
	// Tests concerning advanced operators
	///////////////////////////////////////
	/**
	 * @test
	 * @see http://forge.typo3.org/issues/21903
	 */
	public function caseWithBooleanConditionIsSupportedInFields() {
		$parseString = 'CASE WHEN 1>0 THEN 2 ELSE 1 END AS foo, other_column';
		$result = $this->subject->parseFieldList($parseString);
		$this->assertInternalType('array', $result);
		$this->assertEmpty($parseString);
	}

	/**
	 * @test
	 * @see http://forge.typo3.org/issues/21903
	 */
	public function caseWithBooleanConditionIsProperlyCompiled() {
		$sql = 'SELECT CASE WHEN 1>0 THEN 2 ELSE 1 END AS foo, other_column FROM mytable';

		$result = $this->subject->debug_testSQL($sql);
		$expected = 'SELECT CASE WHEN 1 > 0 THEN 2 ELSE 1 END AS foo, other_column FROM mytable';
		$this->assertEquals($expected, $this->cleanSql($result));
	}

	/**
	 * @test
	 * @see http://forge.typo3.org/issues/21903
	 */
	public function caseWithMultipleWhenIsSupportedInFields() {
		$parseString = 'CASE column WHEN 1 THEN \'one\' WHEN 2 THEN \'two\' ELSE \'out of range\' END AS number';
		$result = $this->subject->parseFieldList($parseString);
		$this->assertInternalType('array', $result);
		$this->assertEmpty($parseString);
	}

	/**
	 * @test
	 * @see http://forge.typo3.org/issues/21903
	 */
	public function caseWithMultipleWhenIsProperlyCompiled() {
		$sql = 'SELECT CASE column WHEN 1 THEN \'one\' WHEN 2 THEN \'two\' ELSE \'out of range\' END AS number FROM mytable';

		$result = $this->subject->debug_testSQL($sql);
		$expected = 'SELECT CASE column WHEN 1 THEN \'one\' WHEN 2 THEN \'two\' ELSE \'out of range\' END AS number FROM mytable';
		$this->assertEquals($expected, $this->cleanSql($result));
	}

	/**
	 * @test
	 * @see http://forge.typo3.org/issues/21902
	 */
	public function locateIsSupported() {
		$sql = 'SELECT * FROM tx_templavoila_tmplobj WHERE LOCATE(\'(fce)\', datastructure)>0';

		$result = $this->subject->debug_testSQL($sql);
		$expected = 'SELECT * FROM tx_templavoila_tmplobj WHERE LOCATE(\'(fce)\', datastructure) > 0';
		$this->assertEquals($expected, $this->cleanSql($result));
	}

	/**
	 * @test
	 * @see http://forge.typo3.org/issues/21902
	 */
	public function locateWithPositionIsSupported() {
		$sql = 'SELECT * FROM tx_templavoila_tmplobj WHERE LOCATE(\'(fce)\'  , datastructure  ,10)>0';
		$expected = 'SELECT * FROM tx_templavoila_tmplobj WHERE LOCATE(\'(fce)\', datastructure, 10) > 0';
		$result = $this->cleanSql($this->subject->debug_testSQL($sql));
		$this->assertEquals($expected, $result);
	}

	/**
	 * @test
	 * @see http://forge.typo3.org/issues/21902
	 * @see http://forge.typo3.org/issues/21903
	 */
	public function locateWithinCaseIsSupported() {
		$sql = 'SELECT *, CASE WHEN LOCATE(\'(fce)\', datastructure)>0 THEN 2 ELSE 1 END AS scope FROM tx_templavoila_tmplobj';

		$result = $this->subject->debug_testSQL($sql);
		$expected = 'SELECT *, CASE WHEN LOCATE(\'(fce)\', datastructure) > 0 THEN 2 ELSE 1 END AS scope FROM tx_templavoila_tmplobj';
		$this->assertEquals($expected, $this->cleanSql($result));
	}

	///////////////////////////////////////
	// Tests concerning prepared queries
	///////////////////////////////////////
	/**
	 * @test
	 * @see http://forge.typo3.org/issues/23374
	 */
	public function namedPlaceholderIsSupported() {
		$sql = 'SELECT * FROM pages WHERE pid = :pid ORDER BY title';

		$result = $this->subject->debug_testSQL($sql);
		$expected = 'SELECT * FROM pages WHERE pid = :pid ORDER BY title';
		$this->assertEquals($expected, $this->cleanSql($result));
	}

	/**
	 * @test
	 * @see http://forge.typo3.org/issues/23374
	 */
	public function questionMarkPlaceholderIsSupported() {
		$sql = 'SELECT * FROM pages WHERE pid = ? ORDER BY title';

		$result = $this->subject->debug_testSQL($sql);
		$expected = 'SELECT * FROM pages WHERE pid = ? ORDER BY title';
		$this->assertEquals($expected, $this->cleanSql($result));
	}

	/**
	 * @test
	 * @see http://forge.typo3.org/issues/23374
	 */
	public function parametersAreReferenced() {
		$sql = 'SELECT * FROM pages WHERE pid = :pid1 OR pid = :pid2';
		$components = $this->subject->_callRef('parseSELECT', $sql);
		$this->assertInternalType('array', $components['parameters']);
		$this->assertEquals(2, count($components['parameters']));
		$this->assertTrue(isset($components['parameters'][':pid1']));
		$this->assertTrue(isset($components['parameters'][':pid2']));
	}

	/**
	 * @test
	 * @see http://forge.typo3.org/issues/23374
	 */
	public function sameParameterIsReferencedInSubquery() {
		$sql = 'SELECT * FROM pages WHERE uid = :pageId OR uid IN (SELECT uid FROM pages WHERE pid = :pageId)';
		$pageId = 12;
		$components = $this->subject->_callRef('parseSELECT', $sql);
		$components['parameters'][':pageId'][0] = $pageId;

		$result = $this->subject->_callRef('compileSELECT', $components);
		$expected = 'SELECT * FROM pages WHERE uid = 12 OR uid IN (SELECT uid FROM pages WHERE pid = 12)';
		$this->assertEquals($expected, $this->cleanSql($result));
	}

	/**
	 * @test
	 * @see http://forge.typo3.org/issues/23374
	 */
	public function namedParametersMayBeSafelyReplaced() {
		$sql = 'SELECT * FROM pages WHERE pid = :pid AND title NOT LIKE \':pid\'';
		$pid = 12;
		$components = $this->subject->_callRef('parseSELECT', $sql);
		$components['parameters'][':pid'][0] = $pid;

		$result = $this->subject->_callRef('compileSELECT', $components);
		$expected = 'SELECT * FROM pages WHERE pid = ' . $pid . ' AND title NOT LIKE \':pid\'';
		$this->assertEquals($expected, $this->cleanSql($result));
	}

	/**
	 * @test
	 * @see http://forge.typo3.org/issues/23374
	 */
	public function questionMarkParametersMayBeSafelyReplaced() {
		$sql = 'SELECT * FROM pages WHERE pid = ? AND timestamp < ? AND title != \'How to test?\'';
		$parameterValues = array(12, 1281782690);
		$components = $this->subject->_callRef('parseSELECT', $sql);
		for ($i = 0; $i < count($components['parameters']['?']); $i++) {
			$components['parameters']['?'][$i][0] = $parameterValues[$i];
		}

		$result = $this->subject->_callRef('compileSELECT', $components);
		$expected = 'SELECT * FROM pages WHERE pid = 12 AND timestamp < 1281782690 AND title != \'How to test?\'';
		$this->assertEquals($expected, $this->cleanSql($result));
	}
}