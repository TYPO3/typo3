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

namespace TYPO3\CMS\Core\Tests\Functional\Session;

use TYPO3\CMS\Core\Session\SessionManager;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class SessionManagerTest extends FunctionalTestCase
{
    /**
     * @var SessionManager
     */
    protected $subject;

    /**
     * @var array
     */
    protected $testSessionRecords = [
        'randomSessionId1' => [
            // DatabaseSessionBackend::hash('randomSessionId1') with encryption key 12345
            'ses_id' => 'e1ad65e4bad3c29e12c754c8e9f5927e',
            'ses_userid' => 1,
        ],
        'randomSessionId2' => [
            // DatabaseSessionBackend::hash('randomSessionId2') with encryption key 12345
            'ses_id' => '72b1cf1fccc010ddb760c6db03f668db',
            'ses_userid' => 1,
        ],
        'randomSessionId3' => [
            // DatabaseSessionBackend::hash('randomSessionId3') with encryption key 12345
            'ses_id' => '7ee0836849b95d884108486c4a8973f3',
            'ses_userid' => 2,
        ]
    ];

    /**
     * Set configuration for DatabaseSessionBackend
     */
    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'] = '12345';

        $this->subject = new SessionManager();
        $frontendSessionBackend = $this->subject->getSessionBackend('FE');
        foreach ($this->testSessionRecords as $sessionId => $testSessionRecord) {
            $frontendSessionBackend->set($sessionId, $testSessionRecord);
        }
        $backendSessionBackend = $this->subject->getSessionBackend('BE');
        foreach ($this->testSessionRecords as $sessionId => $testSessionRecord) {
            $backendSessionBackend->set($sessionId, $testSessionRecord);
        }
    }

    /**
     * @test
     */
    public function clearAllSessionsByUserIdDestroyAllSessionsForBackend()
    {
        $backendSessionBackend = $this->subject->getSessionBackend('BE');
        $allActiveSessions = $backendSessionBackend->getAll();
        self::assertCount(3, $allActiveSessions);
        $this->subject->invalidateAllSessionsByUserId($backendSessionBackend, 1);
        $allActiveSessions = $backendSessionBackend->getAll();
        self::assertCount(1, $allActiveSessions);
        self::assertSame($this->testSessionRecords['randomSessionId3']['ses_id'], $allActiveSessions[0]['ses_id']);
        self::assertSame(2, (int)$allActiveSessions[0]['ses_userid']);
    }

    /**
     * @test
     */
    public function clearAllSessionsByUserIdDestroyAllSessionsForFrontend()
    {
        $frontendSessionBackend = $this->subject->getSessionBackend('FE');
        $allActiveSessions = $frontendSessionBackend->getAll();
        self::assertCount(3, $allActiveSessions);
        $this->subject->invalidateAllSessionsByUserId($frontendSessionBackend, 1);
        $allActiveSessions = $frontendSessionBackend->getAll();
        self::assertCount(1, $allActiveSessions);
        self::assertSame($this->testSessionRecords['randomSessionId3']['ses_id'], $allActiveSessions[0]['ses_id']);
        self::assertSame(2, (int)$allActiveSessions[0]['ses_userid']);
    }
}
