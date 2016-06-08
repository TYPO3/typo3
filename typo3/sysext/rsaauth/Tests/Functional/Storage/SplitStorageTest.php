<?php
namespace TYPO3\CMS\Rsaauth\Tests\Functional\Storage;

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
use TYPO3\CMS\Rsaauth\Storage\SplitStorage;

/**
 * Test case.
 */
class SplitStorageTest extends \TYPO3\CMS\Core\Tests\FunctionalTestCase
{
    /**
     * XML database fixtures to be loaded into database.
     *
     * @var array
     */
    protected $xmlDatabaseFixtures = [
        'typo3/sysext/rsaauth/Tests/Functional/Fixtures/tx_rsaauth_keys.xml'
    ];

    /**
     * Core extensions to load
     *
     * @var array
     */
    protected $testExtensionsToLoad = [
        'typo3/sysext/rsaauth'
    ];

    /**
     * @var SplitStorage
     */
    protected $subject;

    /**
     * @var string
     */
    protected $testKey = '666cb6d79dc65973df67571bbdc5beca';

    /**
     * @var string
     */
    protected $testKeyLeftPart = '666cb6d79dc65973';

    /**
     * @var string
     */
    protected $testKeyRightPart = 'df67571bbdc5beca';

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
        $this->subject = GeneralUtility::makeInstance(SplitStorage::class);
        // same timestamp as in Fixtures/tx_rsaauth_keys.xml
        $GLOBALS['EXEC_TIME'] = 1465391843;
        $_SESSION['tx_rsaauth_key'] = [1, $this->testKeyLeftPart];
    }

    /**
     * @test
     */
    public function getReturnsKeyFromDatabase()
    {
        $key = $this->subject->get();
        $this->assertEquals($this->testKey, $key);
    }

    /**
     * @test
     */
    public function putInsertsKeyIntoDatabase()
    {
        $this->subject->put($this->testKey);
        $this->assertEquals($this->testKey, $this->subject->get());
    }

    /**
     * @test
     */
    public function getDeletesKeysOlderThan30Minutes()
    {
        $outDatedKeyId = 3;
        $_SESSION['tx_rsaauth_key'] = [1, $this->testKeyLeftPart];

        $key = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('tx_rsaauth_keys')
            ->select(['key_value'], 'tx_rsaauth_keys', ['uid' => $outDatedKeyId])
            ->fetchColumn();

        $this->assertEquals($this->testKeyRightPart, $key);

        $result = $this->subject->get();

        $key = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('tx_rsaauth_keys')
            ->select(['key_value'], 'tx_rsaauth_keys', ['uid' => $outDatedKeyId])
            ->fetchColumn();

        $this->assertFalse($key);
    }

    /**
     * @test
     */
    public function putDeletesKeysOlderThan30Minutes()
    {
        $outDatedKeyId = 3;
        $_SESSION['tx_rsaauth_key'] = [1, $this->testKeyLeftPart];

        $key = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('tx_rsaauth_keys')
            ->select(['key_value'], 'tx_rsaauth_keys', ['uid' => $outDatedKeyId])
            ->fetchColumn();

        $this->assertEquals($this->testKeyRightPart, $key);

        $this->subject->put('testkey');

        $key = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('tx_rsaauth_keys')
            ->select(['key_value'], 'tx_rsaauth_keys', ['uid' => $outDatedKeyId])
            ->fetchColumn();

        $this->assertFalse($key);
    }

    /**
     * @test
     */
    public function putDeletesCurrentKeyIfNullIsGiven()
    {
        $keyToBeDeleted = 1;
        $_SESSION['tx_rsaauth_key'] = [$keyToBeDeleted, $this->testKeyLeftPart];

        $key = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('tx_rsaauth_keys')
            ->select(['key_value'], 'tx_rsaauth_keys', ['uid' => $keyToBeDeleted])
            ->fetchColumn();

        $this->assertEquals($this->testKeyRightPart, $key);

        $this->subject->put(null);

        $key = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('tx_rsaauth_keys')
            ->select(['key_value'], 'tx_rsaauth_keys', ['uid' => $keyToBeDeleted])
            ->fetchColumn();

        $this->assertFalse($key);
    }
}
