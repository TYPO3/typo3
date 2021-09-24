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

use TYPO3\CMS\Core\Session\Backend\DatabaseSessionBackend;
use TYPO3\CMS\Core\Session\Backend\Exception\SessionNotCreatedException;
use TYPO3\CMS\Core\Session\Backend\Exception\SessionNotFoundException;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Test case
 */
class DatabaseSessionBackendTest extends FunctionalTestCase
{
    /**
     * @var DatabaseSessionBackend
     */
    protected $subject;

    /**
     * @var array
     */
    protected $testSessionRecord = [
        // DatabaseSessionBackend::hash('randomSessionId') with encryption key 12345
        'ses_id' => '21c0e911565a67315cdc384889c470fd291feafbfa62e31ecf7409430640bc7a',
        'ses_userid' => 1,
        // serialize(['foo' => 'bar', 'boo' => 'far'])
        'ses_data' => 'a:2:{s:3:"foo";s:3:"bar";s:3:"boo";s:3:"far";}',
    ];

    /**
     * Set configuration for DatabaseSessionBackend
     */
    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'] = '12345';

        $this->subject = new DatabaseSessionBackend();
        $this->subject->initialize('default', [
            'table' => 'fe_sessions',
            'has_anonymous' => true,
        ]);
    }

    /**
     * @test
     */
    public function canValidateSessionBackend(): void
    {
        $this->subject->validateConfiguration();
    }

    /**
     * @test
     * @covers SessionBackendInterface::set
     */
    public function sessionDataIsStoredProperly(): void
    {
        $record = $this->subject->set('randomSessionId', $this->testSessionRecord);

        $expected = array_merge($this->testSessionRecord, ['ses_tstamp' => $GLOBALS['EXEC_TIME']]);

        self::assertEquals($expected, $record);
        self::assertSame($expected['ses_data'], $this->subject->get('randomSessionId')['ses_data']);
        self::assertSame($expected['ses_userid'], (int)$this->subject->get('randomSessionId')['ses_userid']);
    }

    /**
     * @test
     */
    public function anonymousSessionDataIsStoredProperly(): void
    {
        $record = $this->subject->set('randomSessionId', array_merge($this->testSessionRecord, ['ses_userid' => 0]));

        $expected = array_merge($this->testSessionRecord, ['ses_userid' => 0, 'ses_tstamp' => $GLOBALS['EXEC_TIME']]);

        self::assertEquals($expected, $record);
        self::assertSame($expected['ses_data'], $this->subject->get('randomSessionId')['ses_data']);
        self::assertSame($expected['ses_userid'], (int)$this->subject->get('randomSessionId')['ses_userid']);
    }

    /**
     * @test
     * @covers SessionBackendInterface::get
     */
    public function throwExceptionOnNonExistingSessionId(): void
    {
        $this->expectException(SessionNotFoundException::class);
        $this->expectExceptionCode(1481885483);
        $this->subject->get('IDoNotExist');
    }

    /**
     * @test
     * @covers SessionBackendInterface::update
     */
    public function mergeSessionDataWithNewData(): void
    {
        $this->subject->set('randomSessionId', $this->testSessionRecord);

        $updateData = [
            'ses_data' => serialize(['foo' => 'baz', 'idontwantto' => 'set the world on fire']),
            'ses_tstamp' => $GLOBALS['EXEC_TIME'],
        ];
        $expectedMergedData = array_merge($this->testSessionRecord, $updateData);
        $this->subject->update('randomSessionId', $updateData);
        $fetchedRecord = $this->subject->get('randomSessionId');
        self::assertSame($expectedMergedData['ses_data'], $fetchedRecord['ses_data']);
        self::assertSame($expectedMergedData['ses_userid'], (int)$fetchedRecord['ses_userid']);
    }

    /**
     * @test
     * @covers SessionBackendInterface::set
     */
    public function existingSessionMustNotBeOverridden(): void
    {
        $this->expectException(SessionNotCreatedException::class);
        $this->expectExceptionCode(1481895005);

        $this->subject->set('randomSessionId', $this->testSessionRecord);

        $newData = array_merge($this->testSessionRecord, ['ses_data' => serialize(['foo' => 'baz', 'idontwantto' => 'set the world on fire'])]);
        $this->subject->set('randomSessionId', $newData);
    }

    /**
     * @test
     * @covers SessionBackendInterface::update
     */
    public function cannotChangeSessionId(): void
    {
        $this->subject->set('randomSessionId', $this->testSessionRecord);

        $newSessionId = 'newRandomSessionId';
        $newData = $this->testSessionRecord;
        $newData['ses_id'] = $newSessionId;

        // old session id has to exist, no exception must be thrown at this point
        $this->subject->get('randomSessionId');

        // Change session id
        $this->subject->update('randomSessionId', $newData);

        // no session with key newRandomSessionId should exist
        $this->expectException(SessionNotFoundException::class);
        $this->expectExceptionCode(1481885483);
        $this->subject->get('newRandomSessionId');
    }

    /**
     * @test
     * @covers SessionBackendInterface::remove
     */
    public function sessionGetsDestroyed(): void
    {
        $this->subject->set('randomSessionId', $this->testSessionRecord);

        // Remove session
        self::assertTrue($this->subject->remove('randomSessionId'));

        // Check if session was really removed
        $this->expectException(SessionNotFoundException::class);
        $this->expectExceptionCode(1481885483);
        $this->subject->get('randomSessionId');
    }

    /**
     * @test
     * @covers SessionBackendInterface::getAll
     */
    public function canLoadAllSessions(): void
    {
        $this->subject->set('randomSessionId', $this->testSessionRecord);
        $this->subject->set('randomSessionId2', $this->testSessionRecord);

        // Check if session was really removed
        self::assertCount(2, $this->subject->getAll());
    }

    /**
     * @test
     */
    public function canCollectGarbage(): void
    {
        $GLOBALS['EXEC_TIME'] = 150;
        $authenticatedSession = array_merge($this->testSessionRecord, ['ses_id' => 'authenticatedSession']);
        $anonymousSession = array_merge($this->testSessionRecord, ['ses_id' => 'anonymousSession', 'ses_userid' => 0]);

        $this->subject->set('authenticatedSession', $authenticatedSession);
        $this->subject->set('anonymousSession', $anonymousSession);

        // Assert that we set authenticated session correctly
        self::assertSame(
            $authenticatedSession['ses_data'],
            $this->subject->get('authenticatedSession')['ses_data']
        );
        self::assertSame(
            $authenticatedSession['ses_userid'],
            (int)$this->subject->get('authenticatedSession')['ses_userid']
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
            (int)$this->subject->get('authenticatedSession')['ses_userid']
        );

        // Non-authenticated session should be removed
        $this->expectException(SessionNotFoundException::class);
        $this->expectExceptionCode(1481885483);
        $this->subject->get('anonymousSession');
    }

    /**
     * @test
     */
    public function canPartiallyUpdateAfterGet(): void
    {
        $updatedRecord = array_merge(
            $this->testSessionRecord,
            ['ses_tstamp' => $GLOBALS['EXEC_TIME']]
        );
        $sessionId = 'randomSessionId';
        $this->subject->set($sessionId, $this->testSessionRecord);
        $this->subject->update($sessionId, []);
        self::assertSame($updatedRecord['ses_data'], $this->subject->get($sessionId)['ses_data']);
    }
}
