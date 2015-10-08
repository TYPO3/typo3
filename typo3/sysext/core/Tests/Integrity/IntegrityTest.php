<?php
namespace TYPO3\CMS\Core\Tests\Integrity;

/*
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
 * This test case is used in test suites to check for healthy
 * environments after other tests were run.
 *
 * This test is usually executed as the very last file in a suite and
 * should fail if some other test before destroys the environment with
 * invalid mocking or backups.
 */
class IntegrityTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
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
    public function standardSingletonIsRegistered()
    {
        $registeredSingletons = \TYPO3\CMS\Core\Utility\GeneralUtility::getSingletonInstances();
        $this->assertArrayHasKey(\TYPO3\CMS\Core\Cache\CacheManager::class, $registeredSingletons);
        $this->assertTrue($registeredSingletons[\TYPO3\CMS\Core\Cache\CacheManager::class] instanceof \TYPO3\CMS\Core\Cache\CacheManager);
    }
}
