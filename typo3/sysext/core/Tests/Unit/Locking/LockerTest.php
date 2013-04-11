<?php
namespace TYPO3\CMS\Core\Tests\Unit\Locking;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2013 Christian Kuhn <lolli@schwarzbu.ch>
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
 * Testcase for \TYPO3\CMS\Core\Locking\Locker
 *
 * @author Christian Kuhn <lolli@schwarzbu.ch>
 */
class LockerTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	///////////////////////////////
	// tests concerning __construct
	///////////////////////////////
	/**
	 * @test
	 */
	public function constructorUsesDefaultLockingMethodSimple() {
		$instance = new \TYPO3\CMS\Core\Locking\Locker('999999999');
		$this->assertSame('simple', $instance->getMethod());
	}

	/**
	 * @test
	 */
	public function constructorSetsMethodToGivenParameter() {
		$instance = new \TYPO3\CMS\Core\Locking\Locker('999999999', 'flock');
		$this->assertSame('flock', $instance->getMethod());
	}

	/**
	 * @test
	 */
	public function constructorDoesNotThrowExceptionIfUsingDisableMethod() {
		$instance = new \TYPO3\CMS\Core\Locking\Locker('999999999', 'disable');
	}

	/**
	 * @test
	 * @expectedException \InvalidArgumentException
	 */
	public function constructorThrowsExceptionForNotExistingLockingMethod() {
		$instance = new \TYPO3\CMS\Core\Locking\Locker('999999999', 'foo');
	}

	/**
	 * @test
	 */
	public function constructorUsesDefaultValueForLoops() {
		$instance = new \TYPO3\CMS\Core\Locking\Locker('999999999');
		$instance->setEnableLogging(FALSE);
		$t3libLockReflection = new \ReflectionClass('TYPO3\\CMS\\Core\\Locking\\Locker');
		$t3libLockReflectionResourceProperty = $t3libLockReflection->getProperty('loops');
		$t3libLockReflectionResourceProperty->setAccessible(TRUE);
		$this->assertSame(150, $t3libLockReflectionResourceProperty->getValue($instance));
	}

	/**
	 * @test
	 */
	public function constructorSetsLoopsToGivenNumberOfLoops() {
		$instance = new \TYPO3\CMS\Core\Locking\Locker('999999999', 'simple', 10);
		$instance->setEnableLogging(FALSE);
		$t3libLockReflection = new \ReflectionClass('TYPO3\\CMS\\Core\\Locking\\Locker');
		$t3libLockReflectionResourceProperty = $t3libLockReflection->getProperty('loops');
		$t3libLockReflectionResourceProperty->setAccessible(TRUE);
		$this->assertSame(10, $t3libLockReflectionResourceProperty->getValue($instance));
	}

	/**
	 * @test
	 */
	public function constructorUsesDefaultValueForSteps() {
		$instance = new \TYPO3\CMS\Core\Locking\Locker('999999999');
		$instance->setEnableLogging(FALSE);
		$t3libLockReflection = new \ReflectionClass('TYPO3\\CMS\\Core\\Locking\\Locker');
		$t3libLockReflectionResourceProperty = $t3libLockReflection->getProperty('step');
		$t3libLockReflectionResourceProperty->setAccessible(TRUE);
		$this->assertSame(200, $t3libLockReflectionResourceProperty->getValue($instance));
	}

	/**
	 * @test
	 */
	public function constructorSetsStepToGivenNumberOfStep() {
		$instance = new \TYPO3\CMS\Core\Locking\Locker('999999999', 'simple', 0, 10);
		$instance->setEnableLogging(FALSE);
		$t3libLockReflection = new \ReflectionClass('TYPO3\\CMS\\Core\\Locking\\Locker');
		$t3libLockReflectionResourceProperty = $t3libLockReflection->getProperty('step');
		$t3libLockReflectionResourceProperty->setAccessible(TRUE);
		$this->assertSame(10, $t3libLockReflectionResourceProperty->getValue($instance));
	}

	/**
	 * @test
	 */
	public function constructorCreatesLockDirectoryIfNotExisting() {
		\TYPO3\CMS\Core\Utility\GeneralUtility::rmdir(PATH_site . 'typo3temp/locks/', TRUE);
		$instance = new \TYPO3\CMS\Core\Locking\Locker('999999999', 'simple');
		$this->assertTrue(is_dir(PATH_site . 'typo3temp/locks/'));
	}

	/**
	 * @test
	 */
	public function constructorSetsIdToMd5OfStringIfUsingSimleLocking() {
		$instance = new \TYPO3\CMS\Core\Locking\Locker('999999999', 'simple');
		$this->assertSame(md5('999999999'), $instance->getId());
	}

	/**
	 * @test
	 */
	public function constructorSetsResourceToPathWithIdIfUsingSimpleLocking() {
		$instance = new \TYPO3\CMS\Core\Locking\Locker('999999999', 'simple');
		$this->assertSame(PATH_site . 'typo3temp/locks/' . md5('999999999'), $instance->getResource());
	}

	/**
	 * @test
	 */
	public function constructorSetsIdToAbsCrc32OfIdStringIfUsingSemaphoreLocking() {
		if (!function_exists('sem_get')) {
			$this->markTestSkipped('The system does not support semaphore base locking.');
		}
		$instance = new \TYPO3\CMS\Core\Locking\Locker('999999999', 'semaphore');
		$this->assertSame(abs(crc32('999999999')), $instance->getId());
	}

	/**
	 * @test
	 */
	public function constructorSetsResourceToSemaphoreResourceIfUsingSemaphoreLocking() {
		if (!function_exists('sem_get')) {
			$this->markTestSkipped('The system does not support semaphore base locking.');
		}
		$instance = new \TYPO3\CMS\Core\Locking\Locker('999999999', 'semaphore');
		$this->assertTrue(is_resource($instance->getResource()));
	}

	///////////////////////////////
	// tests concerning acquire
	///////////////////////////////
	/**
	 * @test
	 */
	public function acquireFixesPermissionsOnLockFileIfUsingSimpleLogging() {
		if (TYPO3_OS == 'WIN') {
			$this->markTestSkipped('acquireFixesPermissionsOnLockFileIfUsingSimpleLogging() test not available on Windows.');
		}
		// Use a very high id to be unique
		$instance = new \TYPO3\CMS\Core\Locking\Locker(999999999, 'simple');
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
			'flock' => array('flock')
		);
	}

	/**
	 * @test
	 * @dataProvider fileBasedLockMethods
	 */
	public function releaseRemovesLockfileInTypo3TempLocks($lockMethod) {
		// Use a very high id to be unique
		$instance = new \TYPO3\CMS\Core\Locking\Locker(999999999, $lockMethod);
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
			'flock within uploads' => array('flock', PATH_site . 'uploads/TYPO3-Lock-Test')
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
		// Create instance, set lockfile to invalid path
		$instance = new \TYPO3\CMS\Core\Locking\Locker(999999999, $lockMethod);
		$instance->setEnableLogging(FALSE);
		$t3libLockReflection = new \ReflectionClass('TYPO3\\CMS\\Core\\Locking\\Locker');
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