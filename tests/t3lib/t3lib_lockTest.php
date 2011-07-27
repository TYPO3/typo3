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

	///////////////////////////////
	// tests concerning release
	///////////////////////////////

	/**
	 * Dataprovider for releaseRemovesLockfileInTypo3TempLocks
	 */
	public function fileBasedLockMethods() {
		return array(
			'simple' => array('simple'),
			'flock' => array('flock'),
		);
	}

	/**
	 * @test
	 * @dataProvider fileBasedLockMethods
	 */
	public function releaseRemovesLockfileInTypo3TempLocks($lockMethod) {
			// Use a very high id to be unique
		$instance = new t3lib_lock(999999999, 'simple');
			// Disable logging
		$instance->setEnableLogging(FALSE);
			// File pointer to current lock file
		$lockFile = $instance->getResource();
		$instance->acquire();

		$instance->release();

		$this->assertFalse(is_file($lockFile));
	}

	/**
	 * Dataprovider for releaseDoesNotRemoveFilesNotWithinTypo3TempLocksDirectory
	 */
	public function invalidFileReferences() {
		return array(
			'simple not within PATH_site' => array('simple', '/tmp/TYPO3-Lock-Test'),
			'flock not withing PATH_site' => array('flock', '/tmp/TYPO3-Lock-Test'),
			'simple directory traversal' => array('simple', PATH_site . 'typo3temp/../typo3temp/locks/foo'),
			'flock directory traversal' => array('flock', PATH_site . 'typo3temp/../typo3temp/locks/foo'),
			'simple directory traversal 2' => array('simple', PATH_site . 'typo3temp/locks/../locks/foo'),
			'flock directory traversal 2' => array('flock', PATH_site . 'typo3temp/locks/../locks/foo'),
			'simple within uploads' => array('simple', PATH_site . 'uploads/TYPO3-Lock-Test'),
			'flock within uploads' => array('flock', PATH_site . 'uploads/TYPO3-Lock-Test'),
		);
	}

	/**
	 * @test
	 * @dataProvider invalidFileReferences
	 */
	public function releaseDoesNotRemoveFilesNotWithinTypo3TempLocksDirectory($lockMethod, $file) {
		if (TYPO3_OS === 'WIN') {
			$this->markTestSkipped('releaseDoesNotRemoveFilesNotWithinTypo3TempLocksDirectory() test not available on Windows.');
		}
			// Reflection needs php 5.3.2 or above
		if (version_compare(phpversion(), '5.3.2', '<')) {
			$this->markTestSkipped('releaseDoesNotRemoveFilesNotWithinTypo3TempLocksDirectory() test not available with php version smaller than 5.3.2');
		}

			// Create test file
		touch($file);
		if (!is_file($file)) {
			$this->markTestSkipped('releaseDoesNotRemoveFilesNotWithinTypo3TempLocksDirectory() skipped: Test file could not be created');
		}

			// Create t3lib_lock instance, set lockfile to invalid path
		$instance = new t3lib_lock(999999999, $lockMethod);
		$instance->setEnableLogging(FALSE);
		$t3libLockReflection = new ReflectionClass('t3lib_lock');
		$t3libLockReflectionResourceProperty = $t3libLockReflection->getProperty('resource');
		$t3libLockReflectionResourceProperty->setAccessible(TRUE);
		$t3libLockReflectionResourceProperty->setValue($instance, $file);
		$t3libLockReflectionAcquiredProperty = $t3libLockReflection->getProperty('isAcquired');
		$t3libLockReflectionAcquiredProperty->setAccessible(TRUE);
		$t3libLockReflectionAcquiredProperty->setValue($instance, TRUE);

			// Call release method
		$instance->release();

			// Check if file is still there and clean up
		$fileExists = is_file($file);
		if (is_file($file)) {
			unlink($file);
		}

		$this->assertTrue($fileExists);
	}
}
?>
