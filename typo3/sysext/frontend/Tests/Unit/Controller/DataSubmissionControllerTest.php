<?php
namespace TYPO3\CMS\Frontend\Tests\Unit\Controller;

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
 * Test case
 *
 * @author Christian Kuhn <lolli@schwarzbu.ch>
 */
class DataSubmissionControllerTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	///////////////////////////////
	// tests concerning __destruct
	///////////////////////////////

	/**
	 * Dataprovider for destructorDoesNotRemoveFilesNotWithinTypo3TempDirectory
	 */
	public function invalidFileReferences() {
		return array(
			'not within PATH_site' => array('/tmp/TYPO3-DataSubmissionControllerTest'),
			'does not start with upload_temp_' => array(PATH_site . 'typo3temp/foo'),
			'directory traversal' => array(PATH_site . 'typo3temp/../typo3temp/upload_temp_foo'),
		);
	}

	/**
	 * @test
	 * @dataProvider invalidFileReferences
	 */
	public function destructorDoesNotRemoveFilesNotWithinTypo3TempDirectory($file) {
		if (TYPO3_OS === 'WIN') {
			$this->markTestSkipped('destructorDoesNotRemoveFilesNotWithinTypo3TempDirectory() test not available on Windows.');
		}

		// Create test file
		touch($file);
		if (!is_file($file)) {
			$this->markTestSkipped('destructorDoesNotRemoveFilesNotWithinTypo3TempDirectory() skipped: Test file could not be created');
		}

		$instance = new \TYPO3\CMS\Frontend\Controller\DataSubmissionController(999999999, $lockMethod);
		$t3libLockReflection = new \ReflectionClass('TYPO3\\CMS\\Frontend\\Controller\\DataSubmissionController');
		$t3libLockReflectionResourceProperty = $t3libLockReflection->getProperty('temporaryFiles');
		$t3libLockReflectionResourceProperty->setAccessible(TRUE);
		$t3libLockReflectionResourceProperty->setValue($instance, array($file));

			// Call release method
		$instance->__destruct();

			// Check if file is still there and clean up
		$fileExists = is_file($file);
		if (is_file($file)) {
			unlink($file);
		}

		$this->assertTrue($fileExists);
	}
}
