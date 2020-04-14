<?php

declare(strict_types=1);

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

namespace TYPO3\CMS\Core\Tests\Functional\Session\Backend;

use TYPO3\CMS\Core\Session\Backend\Exception\SessionNotCreatedException;
use TYPO3\CMS\Core\Session\Backend\Exception\SessionNotFoundException;
use TYPO3\CMS\Core\Session\Backend\Exception\SessionNotUpdatedException;
use TYPO3\CMS\Core\Session\Backend\RedisSessionBackend;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Test case
 *
 * @requires extension redis
 */
class RedisSessionBackendTest extends FunctionalTestCase
{
    /**
     * @var RedisSessionBackend Prepared and connected redis test subject
     */
    protected $subject;

    /**
     * @var array
     */
    protected $testSessionRecord = [
        'ses_id' => 'randomSessionId',
        'ses_userid' => 1,
        // serialize(['foo' => 'bar', 'boo' => 'far'])
        'ses_data' => 'a:2:{s:3:"foo";s:3:"bar";s:3:"boo";s:3:"far";}',
    ];

    /**
     * Set configuration for RedisSessionBackend
     */
    protected function setUp(): void
    {
        parent::setUp();

        if (!getenv('typo3TestingRedisHost')) {
            self::markTestSkipped('environment variable "typo3TestingRedisHost" must be set to run this test');
        }
        // Note we assume that if that typo3TestingRedisHost env is set, we can use that for testing,
        // there is no test to see if the daemon is actually up and running. Tests will fail if env
        // is set but daemon is down.

        // We know this env is set, otherwise setUp() would skip the tests
        $redisHost = getenv('typo3TestingRedisHost');
        // If typo3TestingRedisPort env is set, use it, otherwise fall back to standard port
        $env = getenv('typo3TestingRedisPort');
        $redisPort = is_string($env) ? (int)$env : 6379;

        $redis = new \Redis();
        $redis->connect($redisHost, $redisPort);
        $redis->select(0);
        // Clear db to ensure no sessions exist currently
        $redis->flushDB();

        $this->subject = new RedisSessionBackend();
        $this->subject->initialize(
            'default',
            [
                'database' => 0,
                'port' => $redisPort,
                'hostname' => $redisHost
            ]
        );
    }

    /**
     * @test
     */
    public function cannotUpdateNonExistingRecord()
    {
        $this->expectException(SessionNotUpdatedException::class);
        $this->expectExceptionCode(1484389971);
        $this->subject->update('iSoNotExist', []);
    }

    /**
     * @test
     */
    public function canValidateSessionBackend()
    {
        $this->subject->validateConfiguration();
    }

    /**
     * @test
     * @covers SessionBackendInterface::set
     */
    public function sessionDataIsStoredProperly()
    {
        $record = $this->subject->set('randomSessionId', $this->testSessionRecord);

        $expected = array_merge($this->testSessionRecord, ['ses_tstamp' => $GLOBALS['EXEC_TIME']]);

        self::assertEquals($record, $expected);
        $result = $this->subject->get('randomSessionId');
        self::assertSame($expected, $result);
    }

    /**
     * @test
     */
    public function anonymousSessionDataIsStoredProperly()
    {
        $record = $this->subject->set('randomSessionId', array_merge($this->testSessionRecord, ['ses_anonymous' => 1]));

        $expected = array_merge($this->testSessionRecord, ['ses_anonymous' => 1, 'ses_tstamp' => $GLOBALS['EXEC_TIME']]);

        self::assertEquals($record, $expected);
        self::assertSame($expected, $this->subject->get('randomSessionId'));
    }

    /**
     * @test
     * @covers SessionBackendInterface::get
     */
    public function throwExceptionOnNonExistingSessionId()
    {
        $this->expectException(SessionNotFoundException::class);
        $this->expectExceptionCode(1481885583);
        $this->subject->get('IDoNotExist');
    }

    /**
     * @test
     * @covers SessionBackendInterface::update
     */
    public function mergeSessionDataWithNewData()
    {
        $this->subject->set('randomSessionId', $this->testSessionRecord);

        $updateData = [
            'ses_data' => serialize(['foo' => 'baz', 'idontwantto' => 'set the world on fire']),
            'ses_tstamp' => $GLOBALS['EXEC_TIME']
        ];
        $expectedMergedData = array_merge($this->testSessionRecord, $updateData);
        $this->subject->update('randomSessionId', $updateData);
        $fetchedRecord = $this->subject->get('randomSessionId');
        self::assertSame($expectedMergedData, $fetchedRecord);
    }

    /**
     * @test
     * @covers SessionBackendInterface::set
     */
    public function existingSessionMustNotBeOverridden()
    {
        $this->expectException(SessionNotCreatedException::class);
        $this->expectExceptionCode(1481895647);

        $this->subject->set('randomSessionId', $this->testSessionRecord);

        $newData = array_merge($this->testSessionRecord, ['ses_data' => serialize(['foo' => 'baz', 'idontwantto' => 'set the world on fire'])]);
        $this->subject->set('randomSessionId', $newData);
    }

    /**
     * @test
     * @covers SessionBackendInterface::update
     */
    public function cannotChangeSessionId()
    {
        $this->subject->set('randomSessionId', $this->testSessionRecord);

        $newSessionId = 'newRandomSessionId';
        $newData = array_merge($this->testSessionRecord, ['ses_id' => $newSessionId]);

        // old session id has to exist, no exception must be thrown at this point
        $this->subject->get('randomSessionId');

        // Change session id
        $this->subject->update('randomSessionId', $newData);

        // no session with key newRandomSessionId should exist
        $this->expectException(SessionNotFoundException::class);
        $this->expectExceptionCode(1481885583);
        $this->subject->get('newRandomSessionId');
    }

    /**
     * @test
     * @covers SessionBackendInterface::remove
     */
    public function sessionGetsDestroyed()
    {
        $this->subject->set('randomSessionId', $this->testSessionRecord);

        // Remove session
        self::assertTrue($this->subject->remove('randomSessionId'));

        // Check if session was really removed
        $this->expectException(SessionNotFoundException::class);
        $this->expectExceptionCode(1481885583);
        $this->subject->get('randomSessionId');
    }

    /**
     * @test
     * @covers SessionBackendInterface::getAll
     */
    public function canLoadAllSessions()
    {
        $this->subject->set('randomSessionId', $this->testSessionRecord);
        $this->subject->set('randomSessionId2', $this->testSessionRecord);

        // Check if session was really removed
        self::assertEquals(2, count($this->subject->getAll()));
    }

    /**
     * @test
     */
    public function canCollectGarbage()
    {
        $GLOBALS['EXEC_TIME'] = 150;
        $authenticatedSession = array_merge($this->testSessionRecord, ['ses_id' => 'authenticatedSession']);
        $anonymousSession = array_merge($this->testSessionRecord, ['ses_id' => 'anonymousSession', 'ses_anonymous' => 1]);

        $this->subject->set('authenticatedSession', $authenticatedSession);
        $this->subject->set('anonymousSession', $anonymousSession);

        // Assert that we set authenticated session correctly
        self::assertSame(
            $authenticatedSession['ses_data'],
            $this->subject->get('authenticatedSession')['ses_data']
        );
        self::assertSame(
            $authenticatedSession['ses_userid'],
            $this->subject->get('authenticatedSession')['ses_userid']
        );

        // assert that we set anonymous session correctly
        self::assertSame(
            $anonymousSession['ses_data'],
            $this->subject->get('anonymousSession')['ses_data']
        );

        // Run the garbage collection
        $GLOBALS['EXEC_TIME'] = 200;
        // 150 + 10 < 200 but 150 + 60 >= 200
        $this->subject->collectGarbage(60, 10);

        // Authenticated session should still be there
        self::assertSame(
            $authenticatedSession['ses_data'],
            $this->subject->get('authenticatedSession')['ses_data']
        );
        self::assertSame(
            $authenticatedSession['ses_userid'],
            $this->subject->get('authenticatedSession')['ses_userid']
        );

        // Non-authenticated session should be removed
        $this->expectException(SessionNotFoundException::class);
        $this->expectExceptionCode(1481885583);
        $this->subject->get('anonymousSession');
    }

    /**
     * @test
     */
    public function canPartiallyUpdateAfterGet()
    {
        $updatedRecord = array_merge(
            $this->testSessionRecord,
            ['ses_tstamp' => $GLOBALS['EXEC_TIME']]
        );
        $sessionId = 'randomSessionId';
        $this->subject->set($sessionId, $this->testSessionRecord);
        $this->subject->update($sessionId, []);
        self::assertSame($updatedRecord, $this->subject->get($sessionId));
    }
}
