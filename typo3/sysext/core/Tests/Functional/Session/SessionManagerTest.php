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
            'ses_id' => '92728358061fb01f95498e33ec4661e1edac4b59c18a06f2f80047747c749515',
            'ses_userid' => 1,
        ],
        'randomSessionId2' => [
            // DatabaseSessionBackend::hash('randomSessionId2') with encryption key 12345
            'ses_id' => '531b1305780519abe3e2c6b8857d2efc51ed1944242a597c0b2dd76f94876897',
            'ses_userid' => 1,
        ],
        'randomSessionId3' => [
            // DatabaseSessionBackend::hash('randomSessionId3') with encryption key 12345
            'ses_id' => '696a4c67e53a429327c82f09eaf20b2c634deed68a96d5c1d6cc28cf3d009654',
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
