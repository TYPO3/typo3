<?php
namespace TYPO3\CMS\Saltedpasswords\Tests\Functional;

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

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Saltedpasswords\SaltedPasswordService;

/**
 * Test case for \TYPO3\CMS\Saltedpasswords\SaltedPasswordService
 */
class SaltedPasswordServiceTest extends \TYPO3\CMS\Core\Tests\FunctionalTestCase
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
     * @var SaltedPasswordService
     */
    protected $subject;

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
        $this->subject = GeneralUtility::makeInstance(SaltedPasswordService::class);
    }

    /**
     * Check if service updates backend user password
     *
     * @test
     */
    public function checkIfServiceUpdatesBackendUserPassword()
    {
        $newPassword = array('password' => '008c5926ca861023c1d2a36653fd88e2');

        $this->subject->pObj = new \stdClass();
        $this->subject->pObj->user_table = 'be_users';

        $this->callInaccessibleMethod($this->subject, 'updatePassword', 3, $newPassword);

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('be_users');

        $currentPassword = $queryBuilder
            ->select('password')
            ->from('be_users')
            ->where($queryBuilder->expr()->eq('uid', 3))
            ->execute()
            ->fetchColumn();

        $this->assertEquals($newPassword['password'], $currentPassword);
    }
}
