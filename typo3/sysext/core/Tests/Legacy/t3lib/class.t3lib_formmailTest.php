<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011 Christian Kuhn <lolli@schwarzbu.ch>
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
 * Testcase for t3lib_formmail
 *
 * This legacy test will be removed if t3lib_formmail is removed
 *
 * @author Christian Kuhn <lolli@schwarzbu.ch>
 *
 */
class t3lib_formmailTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	///////////////////////////////
	// tests concerning __destruct
	///////////////////////////////

	/**
	 * Dataprovider for destructorDoesNotRemoveFilesNotWithinTypo3TempDirectory
	 */
	public function invalidFileReferences() {
		return array(
			'not within PATH_site' => array('/tmp/TYPO3-Formmail-Test'),
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
			// Reflection needs php 5.3.2 or above
		if (version_compare(phpversion(), '5.3.2', '<')) {
			$this->markTestSkipped('destructorDoesNotRemoveFilesNotWithinTypo3TempDirectory() test not available with php version smaller than 5.3.2');
		}

			// Create test file
		touch($file);
		if (!is_file($file)) {
			$this->markTestSkipped('destructorDoesNotRemoveFilesNotWithinTypo3TempDirectory() skipped: Test file could not be created');
		}

			// Create t3lib_formmail instance, inject invalid file
		$instance = new t3lib_formmail(999999999, $lockMethod);
		$t3libLockReflection = new ReflectionClass('t3lib_formmail');
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
?>