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
 * Testcase for TYPO3\CMS\Core\Database\SqlParser
 */
class SqlParserTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\CMS\Core\Database\SqlParser|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface
	 */
	protected $subject;

	protected function setUp() {
		$this->subject = $this->getAccessibleMock(\TYPO3\CMS\Core\Database\SqlParser::class, array('dummy'));
	}

	/**
	 * Regression test
	 *
	 * @test
	 */
	public function compileWhereClauseDoesNotDropClauses() {
		$clauses = array(
			0 => array(
				'modifier' => '',
				'table' => 'pages',
				'field' => 'fe_group',
				'calc' => '',
				'comparator' => '=',
				'value' => array(
					0 => '',
					1 => '\''
				)
			),
			1 => array(
				'operator' => 'OR',
				'modifier' => '',
				'func' => array(
					'type' => 'IFNULL',
					'default' => array(
						0 => '1',
						1 => '\''
					),
					'table' => 'pages',
					'field' => 'fe_group'
				)
			),
			2 => array(
				'operator' => 'OR',
				'modifier' => '',
				'table' => 'pages',
				'field' => 'fe_group',
				'calc' => '',
				'comparator' => '=',
				'value' => array(
					0 => '0',
					1 => '\''
				)
			),
			3 => array(
				'operator' => 'OR',
				'modifier' => '',
				'func' => array(
					'type' => 'FIND_IN_SET',
					'str' => array(
						0 => '0',
						1 => '\''
					),
					'table' => 'pages',
					'field' => 'fe_group'
				),
				'comparator' => ''
			),
			4 => array(
				'operator' => 'OR',
				'modifier' => '',
				'func' => array(
					'type' => 'FIND_IN_SET',
					'str' => array(
						0 => '-1',
						1 => '\''
					),
					'table' => 'pages',
					'field' => 'fe_group'
				),
				'comparator' => ''
			)
		);
		$output = $this->subject->compileWhereClause($clauses);
		$parts = explode(' OR ', $output);
		$this->assertSame(count($clauses), count($parts));
		$this->assertContains('IFNULL', $output);
	}

	/**
	 * Data provider for trimSqlReallyTrimsAllWhitespace
	 *
	 * @see trimSqlReallyTrimsAllWhitespace
	 */
	public function trimSqlReallyTrimsAllWhitespaceDataProvider() {
		return array(
			'Nothing to trim' => array('SELECT * FROM test WHERE 1=1;', 'SELECT * FROM test WHERE 1=1 '),
			'Space after ;' => array('SELECT * FROM test WHERE 1=1; ', 'SELECT * FROM test WHERE 1=1 '),
			'Space before ;' => array('SELECT * FROM test WHERE 1=1 ;', 'SELECT * FROM test WHERE 1=1 '),
			'Space before and after ;' => array('SELECT * FROM test WHERE 1=1 ; ', 'SELECT * FROM test WHERE 1=1 '),
			'Linefeed after ;' => array('SELECT * FROM test WHERE 1=1' . LF . ';', 'SELECT * FROM test WHERE 1=1 '),
			'Linefeed before ;' => array('SELECT * FROM test WHERE 1=1;' . LF, 'SELECT * FROM test WHERE 1=1 '),
			'Linefeed before and after ;' => array('SELECT * FROM test WHERE 1=1' . LF . ';' . LF, 'SELECT * FROM test WHERE 1=1 '),
			'Tab after ;' => array('SELECT * FROM test WHERE 1=1' . TAB . ';', 'SELECT * FROM test WHERE 1=1 '),
			'Tab before ;' => array('SELECT * FROM test WHERE 1=1;' . TAB, 'SELECT * FROM test WHERE 1=1 '),
			'Tab before and after ;' => array('SELECT * FROM test WHERE 1=1' . TAB . ';' . TAB, 'SELECT * FROM test WHERE 1=1 '),
		);
	}

	/**
	 * @test
	 * @dataProvider trimSqlReallyTrimsAllWhitespaceDataProvider
	 * @param string $sql The SQL to trim
	 * @param string $expected The expected trimmed SQL with single space at the end
	 */
	public function trimSqlReallyTrimsAllWhitespace($sql, $expected) {
		$result = $this->subject->_call('trimSQL', $sql);
		$this->assertSame($expected, $result);
	}


	/**
	 * Data provider for getValueReturnsCorrectValues
	 *
	 * @see getValueReturnsCorrectValues
	 */
	public function getValueReturnsCorrectValuesDataProvider() {
		return array(
			// description => array($parseString, $comparator, $mode, $expected)
			'key definition without length' => array('(pid,input_1), ', '_LIST', 'INDEX', array('pid', 'input_1')),
			'key definition with length' => array('(pid,input_1(30)), ', '_LIST', 'INDEX', array('pid', 'input_1(30)')),
			'key definition without length (no mode)' => array('(pid,input_1), ', '_LIST', '',  array('pid', 'input_1')),
			'key definition with length (no mode)' => array('(pid,input_1(30)), ', '_LIST', '', array('pid', 'input_1(30)')),
			'test1' => array('input_1 varchar(255) DEFAULT \'\' NOT NULL,', '', '', array('input_1')),
			'test2' => array('varchar(255) DEFAULT \'\' NOT NULL,', '', '', array('varchar(255)')),
			'test3' => array('DEFAULT \'\' NOT NULL,', '', '', array('DEFAULT')),
			'test4' => array('\'\' NOT NULL,', '', '', array('', '\'')),
			'test5' => array('NOT NULL,', '', '', array('NOT')),
			'test6' => array('NULL,', '', '', array('NULL')),
			'getValueOrParameter' => array('NULL,', '', '', array('NULL')),
		);
	}

	/**
	 * @test
	 * @dataProvider getValueReturnsCorrectValuesDataProvider
	 * @param string $parseString the string to parse
	 * @param string $comparator The comparator used before. If "NOT IN" or "IN" then the value is expected to be a list of values. Otherwise just an integer (un-quoted) or string (quoted)
	 * @param string $mode The mode, eg. "INDEX
	 * @param string $expected
	 */
	public function getValueReturnsCorrectValues($parseString, $comparator, $mode, $expected) {
		$result = $this->subject->_callRef('getValue', $parseString, $comparator, $mode);
		$this->assertSame($expected, $result);
	}

	/**
	 * Data provider for parseSQL
	 *
	 * @see parseSQL
	 */
	public function parseSQLDataProvider() {
		$testSql = array();
		$testSql[] = 'CREATE TABLE tx_demo (';
		$testSql[] = '	uid int(11) NOT NULL auto_increment,';
		$testSql[] = '	pid int(11) DEFAULT \'0\' NOT NULL,';

		$testSql[] = '	tstamp int(11) unsigned DEFAULT \'0\' NOT NULL,';
		$testSql[] = '	crdate int(11) unsigned DEFAULT \'0\' NOT NULL,';
		$testSql[] = '	cruser_id int(11) unsigned DEFAULT \'0\' NOT NULL,';
		$testSql[] = '	deleted tinyint(4) unsigned DEFAULT \'0\' NOT NULL,';
		$testSql[] = '	hidden tinyint(4) unsigned DEFAULT \'0\' NOT NULL,';
		$testSql[] = '	starttime int(11) unsigned DEFAULT \'0\' NOT NULL,';
		$testSql[] = '	endtime int(11) unsigned DEFAULT \'0\' NOT NULL,';

		$testSql[] = '	input_1 varchar(255) DEFAULT \'\' NOT NULL,';
		$testSql[] = '	input_2 varchar(255) DEFAULT \'\' NOT NULL,';
		$testSql[] = '	select_child int(11) unsigned DEFAULT \'0\' NOT NULL,';

		$testSql[] = '	PRIMARY KEY (uid),';
		$testSql[] = '	KEY parent (pid,input_1),';
		$testSql[] = '	KEY bar (tstamp,input_1(200),input_2(100),endtime)';
		$testSql[] = ');';
		$testSql = implode("\n", $testSql);
		$expected = array(
			'type' => 'CREATETABLE',
			'TABLE' => 'tx_demo',
			'FIELDS' => array(
				'uid' => array(
					'definition' => array(
						'fieldType' => 'int',
						'value' => '11',
						'featureIndex' => array(
							'NOTNULL' => array(
								'keyword' => 'NOT NULL'
							),
							'AUTO_INCREMENT' => array(
								'keyword' => 'auto_increment'
							)
						)
					)
				),
				'pid' => array(
					'definition' => array(
						'fieldType' => 'int',
						'value' => '11',
						'featureIndex' => array(
							'DEFAULT' => array(
								'keyword' => 'DEFAULT',
								'value' => array(
									0 => '0',
									1 => '\'',
								)
							),
							'NOTNULL' => array(
								'keyword' => 'NOT NULL'
							)
						)
					)
				),
				'tstamp' => array(
					'definition' => array(
						'fieldType' => 'int',
						'value' => '11',
						'featureIndex' => array(
							'UNSIGNED' => array(
								'keyword' => 'unsigned'
							),
							'DEFAULT' => array(
								'keyword' => 'DEFAULT',
								'value' => array(
									0 => '0',
									1 => '\''
								)
							),
							'NOTNULL' => array(
								'keyword' => 'NOT NULL'
							)
						)
					)
				),
				'crdate' => array(
					'definition' => array(
						'fieldType' => 'int',
						'value' => '11',
						'featureIndex' => array(
							'UNSIGNED' => array(
								'keyword' => 'unsigned'
							),
							'DEFAULT' => array(
								'keyword' => 'DEFAULT',
								'value' => array(
									0 => '0',
									1 => '\''
								)
							),
							'NOTNULL' => array(
								'keyword' => 'NOT NULL'
							)
						)
					)
				),
				'cruser_id' => array(
					'definition' => array(
						'fieldType' => 'int',
						'value' => '11',
						'featureIndex' => array(
							'UNSIGNED' => array(
								'keyword' => 'unsigned'
							),
							'DEFAULT' => array(
								'keyword' => 'DEFAULT',
								'value' => array(
									0 => '0',
									1 => '\'',
								)
							),
							'NOTNULL' => array(
								'keyword' => 'NOT NULL'
							)
						)
					)
				),
				'deleted' => array(
					'definition' => array(
						'fieldType' => 'tinyint',
						'value' => '4',
						'featureIndex' => array(
							'UNSIGNED' => array(
								'keyword' => 'unsigned'
							),
							'DEFAULT' => array(
								'keyword' => 'DEFAULT',
								'value' => array(
									0 => '0',
									1 => '\''
								)
							),
							'NOTNULL' => array(
								'keyword' => 'NOT NULL'
							)
						)
					)
				),
				'hidden' => array(
					'definition' => array(
						'fieldType' => 'tinyint',
						'value' => '4',
						'featureIndex' => array(
							'UNSIGNED' => array(
								'keyword' => 'unsigned'
							),
							'DEFAULT' => array(
								'keyword' => 'DEFAULT',
								'value' => array(
									0 => '0',
									1 => '\''
								)
							),
							'NOTNULL' => array(
								'keyword' => 'NOT NULL'
							)
						)
					)
				),
				'starttime' => array(
					'definition' => array(
						'fieldType' => 'int',
						'value' => '11',
						'featureIndex' => array(
							'UNSIGNED' => array(
								'keyword' => 'unsigned'
							),
							'DEFAULT' => array(
								'keyword' => 'DEFAULT',
								'value' => array(
									0 => '0',
									1 => '\''
								)
							),
							'NOTNULL' => array(
								'keyword' => 'NOT NULL'
							)
						)
					)
				),
				'endtime' => array(
					'definition' => array(
						'fieldType' => 'int',
						'value' => '11',
						'featureIndex' => array(
							'UNSIGNED' => array(
								'keyword' => 'unsigned'
							),
							'DEFAULT' => array(
								'keyword' => 'DEFAULT',
								'value' => array(
									0 => '0',
									1 => '\'',
								)
							),
							'NOTNULL' => array(
								'keyword' => 'NOT NULL'
							)
						)
					)
				),
				'input_1' => array(
					'definition' => array(
						'fieldType' => 'varchar',
						'value' => '255',
						'featureIndex' => array(
							'DEFAULT' => array(
								'keyword' => 'DEFAULT',
								'value' => array(
									0 => '',
									1 => '\'',
								)
							),
							'NOTNULL' => array(
								'keyword' => 'NOT NULL'
							)
						)
					)
				),
				'input_2' => array(
					'definition' => array(
						'fieldType' => 'varchar',
						'value' => '255',
						'featureIndex' => array(
							'DEFAULT' => array(
								'keyword' => 'DEFAULT',
								'value' => array(
									0 => '',
									1 => '\'',
								)
							),
							'NOTNULL' => array(
								'keyword' => 'NOT NULL'
							)
						)
					)
				),
				'select_child' => array(
					'definition' => array(
						'fieldType' => 'int',
						'value' => '11',
						'featureIndex' => array(
							'UNSIGNED' => array(
								'keyword' => 'unsigned'
							),
							'DEFAULT' => array(
								'keyword' => 'DEFAULT',
								'value' => array(
									0 => '0',
									1 => '\''
								)
							),
							'NOTNULL' => array(
								'keyword' => 'NOT NULL'
							)
						)
					)
				)
			),
			'KEYS' => array(
				'PRIMARYKEY' => array(
					0 => 'uid'
				),
				'parent' => array(
					0 => 'pid',
					1 => 'input_1',
				),
				'bar' => array(
					0 => 'tstamp',
					1 => 'input_1(200)',
					2 => 'input_2(100)',
					3 => 'endtime',
				)
			)
		);

		return array(
			'test1' => array($testSql, $expected)
		);
	}

	/**
	 * @test
	 * @dataProvider parseSQLDataProvider
	 * @param string $sql The SQL to trim
	 * @param array $expected The expected trimmed SQL with single space at the end
	 */
	public function parseSQL($sql, $expected) {
		$result = $this->subject->_callRef('parseSQL', $sql);
		$this->assertSame($expected, $result);
	}
}
