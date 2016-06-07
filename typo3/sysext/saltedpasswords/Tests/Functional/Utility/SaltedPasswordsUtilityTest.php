<?php
namespace TYPO3\CMS\Saltedpasswords\Tests\Functional\Utility;

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

use TYPO3\CMS\Saltedpasswords\Utility\SaltedPasswordsUtility;

/**
 * Test case for \TYPO3\CMS\Saltedpasswords\Utility\SaltedPasswordsUtility
 */
class SaltedPasswordsUtilityTest extends \TYPO3\CMS\Core\Tests\FunctionalTestCase
{

    /**
     * XML database fixtures to be loaded into database.
     *
     * @var array
     */
    protected $xmlDatabaseFixtures = [
        'typo3/sysext/saltedpasswords/Tests/Functional/Fixtures/be_users.xml'
    ];

    /**
     * Sets up this test suite.
     *
     * @return void
     */
    protected function setUp()
    {
        parent::setUp();
        foreach ($this->xmlDatabaseFixtures as $fixture) {
            $this->importDataSet($fixture);
        }
    }

    /**
     * Check if salted password utility returns the correct number of backend users with insecure passwords
     *
     * @test
     */
    public function checkIfNumberOfBackendUsersWithInsecurePasswordsIsFetchedCorrectly()
    {
        $this->assertEquals(3, SaltedPasswordsUtility::getNumberOfBackendUsersWithInsecurePassword());
    }
}
