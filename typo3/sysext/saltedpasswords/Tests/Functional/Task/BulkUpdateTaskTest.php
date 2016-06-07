<?php
namespace TYPO3\CMS\Saltedpasswords\Tests\Functional\Task;

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
use TYPO3\CMS\Saltedpasswords\Task\BulkUpdateTask;

/**
 * Test case for \TYPO3\CMS\Saltedpasswords\Utility\SaltedPasswordsUtility
 */
class BulkUpdateTaskTest extends \TYPO3\CMS\Core\Tests\FunctionalTestCase
{
    /**
     * XML database fixtures to be loaded into database.
     *
     * @var array
     */
    protected $xmlDatabaseFixtures = [
        'typo3/sysext/saltedpasswords/Tests/Functional/Fixtures/be_users.xml',
        'typo3/sysext/saltedpasswords/Tests/Functional/Fixtures/fe_users.xml'
    ];

    /**
     * Core extensions to load
     *
     * @var array
     */
    protected $testExtensionsToLoad = [
        'typo3/sysext/scheduler'
    ];

    /**
     * @var BulkUpdateTask
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
        $this->subject = GeneralUtility::makeInstance(BulkUpdateTask::class);
    }

    /**
     * Test if bulk update task finds backend users to be updated
     *
     * @test
     */
    public function testIfBulkUpdateTaskFindsBackendUsersToBeUpdated()
    {
        $expectedOutput = [
            [
                'uid' => 1,
                'password' => '$P$CDmA2Juu2h9/9MNaKaxtgzZgIVmjkh/'
            ],
            [
                'uid' => 2,
                'password' => 'M$v2AultVYItaCpb.tpdx2aGAue8eL3/'
            ],
            [
                'uid' => 3,
                'password' => '5f4dcc3b5aa765d61d8327deb882cf99'
            ],
            [
                'uid' => 4,
                'password' => ''
            ],
            [
                'uid' => 5,
                'password' => '819b0643d6b89dc9b579fdfc9094f28e'
            ],
            [
                'uid' => 6,
                'password' => '34cc93ece0ba9e3f6f235d4af979b16c'
            ]
        ];

        $this->assertEquals($expectedOutput, $this->callInaccessibleMethod($this->subject, 'findUsersToUpdate', 'BE'));
    }

    /**
     * Test if bulk update task finds frontend users to be updated
     *
     * @test
     */
    public function testIfBulkUpdateTaskFindsFrontendUsersToBeUpdated()
    {
        $expectedOutput = [
            [
                'uid' => 1,
                'password' => '$P$CDmA2Juu2h9/9MNaKaxtgzZgIVmjkh/'
            ],
            [
                'uid' => 2,
                'password' => '5f4dcc3b5aa765d61d8327deb882cf99'
            ]
        ];

        $this->assertEquals($expectedOutput, $this->callInaccessibleMethod($this->subject, 'findUsersToUpdate', 'FE'));
    }

    /**
     * Test, if passwords are updated with salted hashes for a given user list
     *
     * @test
     */
    public function testIfPasswordsAreUpdatedWithSaltedHashesForGivenUserList()
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('fe_users');
        $usersToBeUpdated = $queryBuilder
            ->select('uid', 'password')
            ->from('fe_users')
            ->where($queryBuilder->expr()->eq('uid', 2))
            ->execute()
            ->fetchAll();

        $originalMd5Password = $usersToBeUpdated[0]['password'];

        $this->callInaccessibleMethod($this->subject, 'updatePasswords', 'FE', $usersToBeUpdated);

        $saltedPassword = $queryBuilder
            ->select('password')
            ->from('fe_users')
            ->where($queryBuilder->expr()->eq('uid', 2))
            ->execute()
            ->fetchColumn();

        $this->assertNotEquals($originalMd5Password, $saltedPassword);

        $saltedPasswordsInstance = \TYPO3\CMS\Saltedpasswords\Salt\SaltFactory::getSaltingInstance(substr($saltedPassword, 1));
        $this->assertTrue($saltedPasswordsInstance->checkPassword($originalMd5Password, substr($saltedPassword, 1)));
    }
}
