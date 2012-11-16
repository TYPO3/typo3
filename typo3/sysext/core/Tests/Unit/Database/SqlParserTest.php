<?php
namespace TYPO3\CMS\Core\Tests\Unit\Database;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011 Patrick Schriner <patrick.schriner@diemedialen.de>
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
	 * @var \TYPO3\CMS\Core\Database\SqlParser
	 */
	private $fixture;

	public function setUp() {
		$this->fixture = new \TYPO3\CMS\Core\Database\SqlParser();
	}

	public function tearDown() {
		unset($this->fixture);
	}

	/**
	 * Regression test
	 *
	 * @test
	 * @todo Define visibility
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

}

?>