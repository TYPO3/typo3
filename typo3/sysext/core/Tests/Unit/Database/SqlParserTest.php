<?php
namespace TYPO3\CMS\Core\Tests\Unit\Database;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011-2013 Patrick Schriner <patrick.schriner@diemedialen.de>
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
 * Testcase for TYPO3\CMS\Core\Database\SqlParser
 *
 * @author Patrick Schriner <patrick.schriner@diemedialen.de>
 */
class SqlParserTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\CMS\Core\Database\SqlParser|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface
	 */
	private $fixture;

	public function setUp() {
		$this->fixture = $this->getAccessibleMock('\\TYPO3\\CMS\\Core\\Database\\SqlParser', array('dummy'));
	}

	public function tearDown() {
		unset($this->fixture);
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

?>