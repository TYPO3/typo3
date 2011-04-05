<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2011 Patrick Schriner <patrick.schriner@diemedialen.de>
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
 * Testcase for class t3lib_sqlparser.
 *
 * @author Patrick Schriner <patrick.schriner@diemedialen.de>
 *
 * @package TYPO3
 * @subpackage t3lib
 */
class t3lib_sqlparserTest extends tx_phpunit_testcase {
	/**
	 * @var t3lib_sqlparser
	 */
	private $fixture;

	public function setUp() {
		$this->fixture = new t3lib_sqlparser();
	}

	public function tearDown() {
		unset(
			$this->fixture
		);
	}

	/**
	 * Regression test
	 *
	 * @test
	 */
	function compileWhereClauseDoesNotDropClauses() {
		$clauses = 	array(
			0 => array (
 				'modifier' => '',
 				'table' => 'pages',
 				'field' => 'fe_group',
 				'calc' => '',
 				'comparator' => '=',
 				'value' => array (
 					0 => '',
 					1 => '\'',
 				),
 			),
 			1 => array (
 				'operator' => 'OR',
 				'modifier' => '',
 				'func' => array (
					 'type' => 'IFNULL',
					 'default' => array (
						 0 => '1',
						 1 => '\'',
					 ),
					 'table' => 'pages',
					 'field' => 'fe_group',
				 ),
			 ),
			 2 => array (
				 'operator' => 'OR',
				 'modifier' => '',
				 'table' => 'pages',
				 'field' => 'fe_group',
				 'calc' => '',
				 'comparator' => '=',
				 'value' => array (
					 0 => '0',
					 1 => '\'',
				 ),
			 ),
			 3 => array (
				 'operator' => 'OR',
				 'modifier' => '',
				 'func' => array (
					 'type' => 'FIND_IN_SET',
					 'str' => array (
						 0 => '0',
						 1 => '\'',
					 ),
					 'table' => 'pages',
					 'field' => 'fe_group',
				 ),
				 'comparator' => '',
			 ),
			 4 => array (
				 'operator' => 'OR',
				 'modifier' => '',
				 'func' => array (
					 'type' => 'FIND_IN_SET',
					 'str' => array (
						 0 => '-1',
						 1 => '\'',
				 	),

				 'table' => 'pages',
				 'field' => 'fe_group',
				 ),
			 	'comparator' => '',
			 ),
		);
		$output = $this->fixture->compileWhereClause($clauses);
		$parts = explode(' OR ', $output);
		$this->assertSame(count($clauses), count($parts));
		$this->assertContains('IFNULL', $output);
	}
}