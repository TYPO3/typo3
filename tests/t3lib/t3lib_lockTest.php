<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2011 Christian Kuhn <lolli@schwarzbu.ch>
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
 * Testcase for t3lib_lock
 *
 * @author Christian Kuhn <lolli@schwarzbu.ch>
 *
 * @package TYPO3
 * @subpackage t3lib
 */

class t3lib_lockTest extends tx_phpunit_testcase {

	/**
	 * Enable backup of global and system variables
	 *
	 * @var boolean
	 */
	protected $backupGlobals = TRUE;
	
	/**
	 * Exclude TYPO3_DB from backup/ restore of $GLOBALS
	 * because resource types cannot be handled during serializing
	 *
	 * @var array
	 */
	protected $backupGlobalsBlacklist = array('TYPO3_DB');

	///////////////////////////////
	// tests concerning acquire
	///////////////////////////////a

	/**
	 * @test
	 */
	public function acquireFixesPermissionsOnLockFileIfUsingSimpleLogging() {
		if (TYPO3_OS == 'WIN') {
			$this->markTestSkipped('acquireFixesPermissionsOnLockFileIfUsingSimpleLogging() test not available on Windows.');
		}

			// Use a very high id to be unique
		$instance = new t3lib_lock(999999999, 'simple');
		$pathOfLockFile = $instance->getResource();
		$GLOBALS['TYPO3_CONF_VARS']['BE']['fileCreateMask'] = '0777';

			// Acquire lock, get actual file permissions and clean up
		$instance->acquire();
		clearstatcache();
		$resultFilePermissions = substr(decoct(fileperms($pathOfLockFile)), 2);
		$instance->__destruct();

		$this->assertEquals($resultFilePermissions, '0777');
	}
}
?>
