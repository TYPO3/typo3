<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 Xavier Perseguers <typo3@perseguers.ch>
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


require_once('BaseTestCase.php');

/**
 * Testcase for class ux_t3lib_db.
 * 
 * $Id$
 *
 * @author Xavier Perseguers <typo3@perseguers.ch>
 *
 * @package TYPO3
 * @subpackage dbal
 */
class db_general_testcase extends BaseTestCase {

	/**
	 * @var ux_t3lib_db (extended to make protected methods public)
	 */
	protected $fixture;

	/**
	 * @var array
	 */
	protected $loadedExtensions;

	/**
	 * Prepares the environment before running a test.
	 */
	public function setUp() {
			// Backup list of loaded extensions
		$this->loadedExtensions = $GLOBALS['TYPO3_LOADED_EXT'];

		$className =  self::buildAccessibleProxy('ux_t3lib_db');
		$this->fixture = new $className;
	}

	/**
	 * Cleans up the environment after running a test.
	 */
	public function tearDown() {
			// Clear DBAL-generated cache files
		$this->fixture->clearCachedFieldInfo();
		unset($this->fixture);
			// Restore list of loaded extensions
		$GLOBALS['TYPO3_LOADED_EXT'] = $this->loadedExtensions;
	}

	/**
	 * Cleans a SQL query.
	 *  
	 * @param mixed $sql
	 * @return mixed (string or array)
	 */
	private function cleanSql($sql) {
		if (!is_string($sql)) {
			return $sql;
		}

		$sql = str_replace("\n", ' ', $sql);
		$sql = preg_replace('/\s+/', ' ', $sql);
		return $sql;
	}

	/**
	 * @test
	 * @see http://bugs.typo3.org/view.php?id=12515
	 */
	public function concatCanBeParsedAfterLikeOperator() {
		$query = $this->cleanSql($this->fixture->SELECTquery(
			'*',
			'sys_refindex, tx_dam_file_tracking',
			'sys_refindex.tablename = \'tx_dam_file_tracking\''
			. ' AND sys_refindex.ref_string LIKE CONCAT(tx_dam_file_tracking.file_path, tx_dam_file_tracking.file_name)'
		));
		$expected = 'SELECT * FROM sys_refindex, tx_dam_file_tracking WHERE sys_refindex.tablename = \'tx_dam_file_tracking\'';
		$expected .= ' AND sys_refindex.ref_string LIKE CONCAT(tx_dam_file_tracking.file_path,tx_dam_file_tracking.file_name)';
		$this->assertEquals($expected, $query);
	}
}
?>