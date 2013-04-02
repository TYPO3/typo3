<?php
namespace TYPO3\CMS\Core\Tests\Integrity;

/***************************************************************
 * Copyright notice
 *
 * (c) 2012-2013 Christian Kuhn <lolli@schwarzbu.ch>
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * This test case is used in test suites to check for healthy
 * environments after other tests were run.
 *
 * This test is usually executed as the very last file in a suite and
 * should fail if some other test before destroys the environment with
 * invalid mocking or backups.
 */
class IntegrityTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * This test fails if some test before mocked or substituted
	 * $GLOBALS['typo3CacheManager'] but did not reconstitute correctly.
	 *
	 * @test
	 */
	public function globalsTypo3CacheManagerIsInstanceOfCoreCacheManager() {
		$this->assertTrue(is_object($GLOBALS['typo3CacheManager']));
		$this->assertTrue($GLOBALS['typo3CacheManager'] instanceof \TYPO3\CMS\Core\Cache\CacheManager);
	}

	/**
	 * This test fails if some test before called
	 * \TYPO3\CMS\Core\Utility\GeneralUtility::purgeInstances() without a proper
	 * backup via \TYPO3\CMS\Core\Utility\GeneralUtility::getSingletonInstances()
	 * and a reconstitution via \TYPO3\CMS\Core\Utility\GeneralUtility::resetSingletonInstances().
	 *
	 * The test for CacheManager should never fail since this object is
	 * already instantiated during bootstrap and must always be there.
	 *
	 * @test
	 */
	public function standardSingletonIsRegistered() {
		$registeredSingletons = \TYPO3\CMS\Core\Utility\GeneralUtility::getSingletonInstances();
		$this->assertArrayHasKey('TYPO3\CMS\Core\Cache\CacheManager', $registeredSingletons);
	}
}

?>