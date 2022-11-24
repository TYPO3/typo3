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

namespace TYPO3\CMS\Core\Tests\Unit\Session;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\NullLogger;
use TYPO3\CMS\Core\Authentication\IpLocker;
use TYPO3\CMS\Core\Security\JwtTrait;
use TYPO3\CMS\Core\Session\Backend\Exception\SessionNotFoundException;
use TYPO3\CMS\Core\Session\Backend\SessionBackendInterface;
use TYPO3\CMS\Core\Session\UserSession;
use TYPO3\CMS\Core\Session\UserSessionManager;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class UserSessionManagerTest extends UnitTestCase
{
    use JwtTrait;

    public function willExpireDataProvider(): array
    {
        return [
            [
                'sessionLifetime' => 120,
                'gracePeriod' => 120,
                'shouldBeMarkedAsExpired' => true,
            ],
            [
                'sessionLifetime' => 120,
                'gracePeriod' => 60,
                'shouldBeMarkedAsExpired' => false,
            ],
            [
                'sessionLifetime' => 120,
                'gracePeriod' => 240,
                'shouldBeMarkedAsExpired' => true,
            ],
        ];
    }

    /**
     * @test
     * @dataProvider willExpireDataProvider
     */
    public function willExpireWillExpire(int $sessionLifetime, int $gracePeriod, bool $expectedResult): void
    {
        $sessionBackendMock = $this->createMock(SessionBackendInterface::class);
        $subject = new UserSessionManager(
            $sessionBackendMock,
            $sessionLifetime,
            new IpLocker(0, 0)
        );
        $session = $subject->createAnonymousSession();
        self::assertEquals($expectedResult, $subject->willExpire($session, $gracePeriod));
    }

    public function hasExpiredIsCalculatedCorrectly(): void
    {
        $GLOBALS['EXEC_TIME'] = time();
        $sessionBackendMock = $this->createMock(SessionBackendInterface::class);
        $subject = new UserSessionManager(
            $sessionBackendMock,
            60,
            new IpLocker(0, 0)
        );
        $expiredSession = UserSession::createFromRecord('random-string', ['ses_tstamp' => time()-500]);
        self::assertTrue($subject->hasExpired($expiredSession));
        $newSession = UserSession::createFromRecord('random-string', ['ses_tstamp' => time()]);
        self::assertFalse($subject->hasExpired($newSession));
    }

    /**
     * @test
     */
    public function createFromRequestOrAnonymousCreatesProperSessionObjectForValidSessionJwt(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'] = 'secret-encryption-key-test';
        $sessionBackendMock = $this->createMock(SessionBackendInterface::class);
        $sessionBackendMock->method('get')->with('valid-session')->willReturn([
            'ses_id' => 'valid-session',
            'ses_userid' => 13,
            'ses_data' => serialize(['propertyA' => 42, 'propertyB' => 'great']),
            'ses_tstamp' => time(),
            'ses_iplock' => '[DISABLED]',
        ]);
        $subject = new UserSessionManager(
            $sessionBackendMock,
            50,
            new IpLocker(0, 0)
        );
        $subject->setLogger(new NullLogger());
        $validSessionJwt = self::encodeHashSignedJwt(
            [
                'identifier' => 'valid-session',
                'time' => (new \DateTimeImmutable())->format(\DateTimeImmutable::RFC3339),
            ],
            self::createSigningKeyFromEncryptionKey(UserSession::class)
        );
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getCookieParams')->willReturn(['bar' => $validSessionJwt]);
        $persistedSession = $subject->createFromRequestOrAnonymous($request, 'bar');
        self::assertEquals(13, $persistedSession->getUserId());
        self::assertFalse($persistedSession->isAnonymous());
        self::assertFalse($persistedSession->isNew());
        self::assertEquals(42, $persistedSession->get('propertyA'));
        self::assertEquals('great', $persistedSession->get('propertyB'));
        self::assertNull($persistedSession->get('propertyC'));
    }

    /**
     * @test
     */
    public function createFromRequestOrAnonymousCreatesProperSessionObjectForInvalidSession(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'] = 'secret-encryption-key-test';
        $sessionBackendMock = $this->createMock(SessionBackendInterface::class);
        $sessionBackendMock->method('get')->with('invalid-session')->willThrowException(
            new SessionNotFoundException('Session not found', 1669358326)
        );
        $subject = new UserSessionManager(
            $sessionBackendMock,
            50,
            new IpLocker(0, 0)
        );
        $subject->setLogger(new NullLogger());

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getCookieParams')->willReturnOnConsecutiveCalls([], ['foo' => 'invalid-session']);
        $anonymousSession = $subject->createFromRequestOrAnonymous($request, 'foo');
        self::assertTrue($anonymousSession->isNew());
        self::assertTrue($anonymousSession->isAnonymous());

        $anonymousSessionFromInvalidBackendRequest = $subject->createFromRequestOrAnonymous($request, 'foo');
        self::assertTrue($anonymousSessionFromInvalidBackendRequest->isNew());
        self::assertTrue($anonymousSessionFromInvalidBackendRequest->isAnonymous());
    }

    /**
     * @test
     */
    public function updateSessionWillSetLastUpdated(): void
    {
        $sessionBackendMock = $this->createMock(SessionBackendInterface::class);
        $sessionBackendMock->method('update')->with(self::anything(), self::anything())->willReturn([
            'ses_id' => 'valid-session',
            'ses_userid' => 13,
            'ses_data' => serialize(['propertyA' => 42, 'propertyB' => 'great']),
            'ses_tstamp' => 7654321,
            'ses_iplock' => '[DISABLED]',
        ]);
        $subject = new UserSessionManager(
            $sessionBackendMock,
            60,
            new IpLocker(0, 0)
        );
        $session = UserSession::createFromRecord('random-string', ['ses_tstamp' => time()-500]);
        $session = $subject->updateSession($session);
        self::assertSame(7654321, $session->getLastUpdated());
    }

    /**
     * @test
     */
    public function fixateAnonymousSessionWillUpdateSessionObject(): void
    {
        $sessionBackendMock = $this->createMock(SessionBackendInterface::class);
        $sessionBackendMock->method('set')->with(self::anything(), self::anything())->willReturn([
            'ses_id' => 'valid-session',
            'ses_userid' => 0,
            'ses_data' => serialize(['propertyA' => 42, 'propertyB' => 'great']),
            'ses_tstamp' => 7654321,
            'ses_iplock' => IpLocker::DISABLED_LOCK_VALUE,
        ]);
        $subject = new UserSessionManager(
            $sessionBackendMock,
            60,
            new IpLocker(0, 0)
        );
        $session = UserSession::createFromRecord('random-string', ['ses_tstamp' => time()-500]);
        $session = $subject->fixateAnonymousSession($session);
        self::assertSame(IpLocker::DISABLED_LOCK_VALUE, $session->getIpLock());
        self::assertNull($session->getUserId());
        self::assertSame(7654321, $session->getLastUpdated());
    }
}
