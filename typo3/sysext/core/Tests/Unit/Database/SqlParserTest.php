<?php
namespace TYPO3\CMS\Core\Tests\Unit\Database;

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
/**
 * Testcase for TYPO3\CMS\Core\Database\SqlParser
 *
 * @author Patrick Schriner <patrick.schriner@diemedialen.de>
 */
class SqlParserTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\CMS\Core\Database\SqlParser|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface
	 */
	protected $fixture;

	public function setUp() {
		$this->fixture = $this->getAccessibleMock('\\TYPO3\\CMS\\Core\\Database\\SqlParser', array('dummy'));
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
		$output = $this->fixture->compileWhereClause($clauses);
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
		$result = $this->fixture->_call('trimSQL', $sql);
		$this->assertSame($expected, $result);
	}

}
