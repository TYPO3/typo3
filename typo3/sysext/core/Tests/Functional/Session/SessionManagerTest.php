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

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Session\Backend\DatabaseSessionBackend;
use TYPO3\CMS\Core\Session\Backend\SessionBackendInterface;
use TYPO3\CMS\Core\Session\SessionManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class SessionManagerTest extends FunctionalTestCase
{
    private SessionManager $subject;

    private array $testSessionRecords = [
        'randomSessionId1' => [
            // DatabaseSessionBackend::hash('randomSessionId1') with encryption key 12345
            'ses_id' => '66c4ba45e3d19bc8726e70e5cf837f8ec7cf2e79df51a06b10dcde49eb7faa5e',
            'ses_userid' => 1,
        ],
        'randomSessionId2' => [
            // DatabaseSessionBackend::hash('randomSessionId2') with encryption key 12345
            'ses_id' => 'e9fc8b8b5c9d1e925b7d35fb8b87e2ac35ba7cea34d7a1d7154fb68e3e47c7aa',
            'ses_userid' => 1,
        ],
        'randomSessionId3' => [
            // DatabaseSessionBackend::hash('randomSessionId3') with encryption key 12345
            'ses_id' => '58b7a851e5afceded1fdf37d25b3bcfbbcfc1e01840fdb6fe011b4a72fdda062',
            'ses_userid' => 2,
        ],
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

    #[Test]
    public function getSessionBackendUsesDefaultBackendFromConfiguration(): void
    {
        self::assertInstanceOf(DatabaseSessionBackend::class, $this->subject->getSessionBackend('BE'));
    }

    #[Test]
    public function clearAllSessionsByUserIdDestroyAllSessionsForBackend(): void
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

    #[Test]
    public function clearAllSessionsByUserIdDestroyAllSessionsForFrontend(): void
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

    #[Test]
    public function getSessionBackendReturnsExpectedSessionBackendBasedOnConfiguration(): void
    {
        $backendMock = $this->createMock(SessionBackendInterface::class);
        $backendClassName = get_class($backendMock);
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['session']['myidentifier'] = [
            'backend'  => $backendClassName,
            'options' => [],
        ];
        $backendMock->expects($this->atLeastOnce())->method('initialize')->with(self::anything());
        $backendMock->expects($this->atLeastOnce())->method('validateConfiguration');
        GeneralUtility::addInstance($backendClassName, $backendMock);
        self::assertInstanceOf($backendClassName, $this->subject->getSessionBackend('myidentifier'));
    }
}
