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

use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Authentication\IpLocker;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Session\Backend\Exception\SessionNotFoundException;
use TYPO3\CMS\Core\Session\Backend\SessionBackendInterface;
use TYPO3\CMS\Core\Session\UserSession;
use TYPO3\CMS\Core\Session\UserSessionManager;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class UserSessionManagerTest extends UnitTestCase
{
    use ProphecyTrait;

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
        $sessionBackendProphecy = $this->prophesize(SessionBackendInterface::class);
        $subject = new UserSessionManager(
            $sessionBackendProphecy->reveal(),
            $sessionLifetime,
            new IpLocker(0, 0),
            'FE'
        );
        $session = $subject->createAnonymousSession();
        self::assertEquals($expectedResult, $subject->willExpire($session, $gracePeriod));
    }

    public function hasExpiredIsCalculatedCorrectly(): void
    {
        $GLOBALS['EXEC_TIME'] = time();
        $sessionBackendProphecy = $this->prophesize(SessionBackendInterface::class);
        $subject = new UserSessionManager(
            $sessionBackendProphecy->reveal(),
            60,
            new IpLocker(0, 0),
            'FE'
        );
        $expiredSession = UserSession::createFromRecord('random-string', ['ses_tstamp' => time() - 500]);
        self::assertTrue($subject->hasExpired($expiredSession));
        $newSession = UserSession::createFromRecord('random-string', ['ses_tstamp' => time()]);
        self::assertFalse($subject->hasExpired($newSession));
    }

    /**
     * @test
     */
    public function createFromRequestOrAnonymousCreatesProperSessionObjects(): void
    {
        $cookieDomain = 'example.org';
        $normalizedParams = $this->createMock(NormalizedParams::class);
        $normalizedParams->method('getRequestHostOnly')->willReturn($cookieDomain);
        $key = sha1($GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'] . '/' . UserSession::class . '/' . $cookieDomain);
        $sessionId = 'valid-session';
        $signature = hash_hmac('sha256', $sessionId, $key);
        $validSession = $sessionId . '.' . $signature;
        $sessionBackendProphecy = $this->prophesize(SessionBackendInterface::class);
        $sessionBackendProphecy->get('invalid-session')->willThrow(SessionNotFoundException::class);
        $sessionBackendProphecy->get($validSession)->willReturn([
            'ses_id' => 'valid-session',
            'ses_userid' => 13,
            'ses_data' => serialize(['propertyA' => 42, 'propertyB' => 'great']),
            'ses_tstamp' => time(),
            'ses_iplock' => '[DISABLED]',
        ]);
        $subject = new UserSessionManager(
            $sessionBackendProphecy->reveal(),
            50,
            new IpLocker(0, 0),
            'FE'
        );
        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getCookieParams()->willReturn([]);
        $request->getServerParams()->willReturn(['HTTP_HOST' => $cookieDomain]);
        $request->getAttribute('normalizedParams')->willReturn($normalizedParams);
        $GLOBALS['TYPO3_REQUEST'] = $request->reveal();
        $anonymousSession = $subject->createFromRequestOrAnonymous($request->reveal(), 'foo');
        self::assertTrue($anonymousSession->isNew());
        self::assertTrue($anonymousSession->isAnonymous());

        $request->getCookieParams()->willReturn(['foo' => 'invalid-session', 'bar' => $validSession]);
        $anonymousSessionFromInvalidBackendRequest = $subject->createFromRequestOrAnonymous($request->reveal(), 'foo');
        self::assertTrue($anonymousSessionFromInvalidBackendRequest->isNew());
        self::assertTrue($anonymousSessionFromInvalidBackendRequest->isAnonymous());
        $persistedSession = $subject->createFromRequestOrAnonymous($request->reveal(), 'bar');

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
    public function updateSessionWillSetLastUpdated(): void
    {
        $sessionBackendProphecy = $this->prophesize(SessionBackendInterface::class);
        $sessionBackendProphecy->update(Argument::any(), Argument::any())->willReturn([
            'ses_id' => 'valid-session',
            'ses_userid' => 13,
            'ses_data' => serialize(['propertyA' => 42, 'propertyB' => 'great']),
            'ses_tstamp' => 7654321,
            'ses_iplock' => '[DISABLED]',
        ]);
        $subject = new UserSessionManager(
            $sessionBackendProphecy->reveal(),
            60,
            new IpLocker(0, 0),
            'FE'
        );
        $session = UserSession::createFromRecord('random-string', ['ses_tstamp' => time() - 500]);
        $session = $subject->updateSession($session);
        self::assertSame(7654321, $session->getLastUpdated());
    }

    /**
     * @test
     */
    public function fixateAnonymousSessionWillUpdateSessionObject(): void
    {
        $sessionBackendProphecy = $this->prophesize(SessionBackendInterface::class);
        $sessionBackendProphecy->set(Argument::any(), Argument::any())->willReturn([
            'ses_id' => 'valid-session',
            'ses_userid' => 0,
            'ses_data' => serialize(['propertyA' => 42, 'propertyB' => 'great']),
            'ses_tstamp' => 7654321,
            'ses_iplock' => IpLocker::DISABLED_LOCK_VALUE,
        ]);
        $subject = new UserSessionManager(
            $sessionBackendProphecy->reveal(),
            60,
            new IpLocker(0, 0),
            'FE'
        );
        $session = UserSession::createFromRecord('random-string', ['ses_tstamp' => time() - 500]);
        $session = $subject->fixateAnonymousSession($session);
        self::assertSame(IpLocker::DISABLED_LOCK_VALUE, $session->getIpLock());
        self::assertNull($session->getUserId());
        self::assertSame(7654321, $session->getLastUpdated());
    }
}
