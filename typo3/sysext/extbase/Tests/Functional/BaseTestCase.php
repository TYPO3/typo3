<?php
/***************************************************************
 *  Copyright notice
 *
 *  This class is a backport of the corresponding class of TYPO3 Flow.
 *  All credits go to the TYPO3 Flow team.
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
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
/**
 * Base database testcase for the Extbase extension.
 *
 * This base test case creates a test database and can populate
 * rows defined in fixtures to it.
 *
 * This class is used in the FAL<->extbase connection tests like
 * \TYPO3\CMS\Extbase\Tests\Functional\Domain\Model\FileContextTest. It is
 * currently marked as experimental!
 *
 * @api experimental! This class is experimental and subject to change!
 */
abstract class Tx_Extbase_Tests_Functional_BaseTestCase extends Tx_Phpunit_Database_TestCase {

	/**
	 * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface The object manager
	 */
	protected $objectManager;

	/**
	 * Injects an untainted clone of the object manager and all its referencing
	 * objects for every test.
	 *
	 * @return void
	 */
	public function runBare() {
		$objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
		$this->objectManager = clone $objectManager;
		parent::runBare();
	}

	protected function setUp() {
		$this->createDatabase();
		$this->useTestDatabase();
		$this->importStdDb();
		$this->importExtensions(array('cms', 'extbase'));
	}

	protected function tearDown() {
		$this->dropDatabase();
	}
}

?>