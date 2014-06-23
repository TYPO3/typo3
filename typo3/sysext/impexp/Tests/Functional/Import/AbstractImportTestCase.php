<?php
namespace TYPO3\CMS\Impexp\Tests\Functional\Import;

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

use TYPO3\CMS\Core\Utility\GeneralUtility;

require_once __DIR__ . '/../../../../core/Tests/Functional/DataHandling/AbstractDataHandlerActionTestCase.php';

/**
 * Functional test for the ImportExport
 */
abstract class AbstractImportTestCase extends \TYPO3\CMS\Core\Tests\Functional\DataHandling\AbstractDataHandlerActionTestCase {

	/**
	 * @var array
	 */
	protected $coreExtensionsToLoad = array('impexp');

	/**
	 * @var \TYPO3\CMS\Impexp\ImportExport
	 */
	protected $import;

	/**
	 * Absolute path to files that must be removed
	 * after a test - handled in tearDown
	 *
	 * @var array
	 */
	protected $testFilesToDelete = array();

	/**
	 * Set up for set up the backend user, initialize the language object
	 * and creating the ImportExport instance
	 *
	 * @return void
	 */
	public function setUp() {
		parent::setUp();

		$this->import = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Impexp\\ImportExport');
		$this->import->init(0, 'import');
	}

	/**
	 * Tear down for remove of the test files
	 */
	public function tearDown() {
		foreach ($this->testFilesToDelete as $absoluteFileName) {
			if (@is_file($absoluteFileName)) {
				unlink($absoluteFileName);
			}
		}
		parent::tearDown();
	}

	/**
	 * Test if the local filesystem is case sensitive
	 *
	 * @return boolean
	 */
	protected function isCaseSensitiveFilesystem() {
		$caseSensitive = TRUE;
		$path = GeneralUtility::tempnam('aAbB');

		// do the actual sensitivity check
		if (@file_exists(strtoupper($path)) && @file_exists(strtolower($path))) {
			$caseSensitive = FALSE;
		}

		// clean filesystem
		unlink($path);
		return $caseSensitive;
	}

}
