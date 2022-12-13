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

namespace TYPO3\CMS\Frontend\Tests\Unit\Authentication;

use Psr\Log\NullLogger;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Session\UserSession;
use TYPO3\CMS\Core\Session\UserSessionManager;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class FrontendUserAuthenticationTest extends UnitTestCase
{
    protected bool $resetSingletonInstances = true;

    /**
     * Setting and immediately removing session data should be handled correctly.
     * No write operations should be made
     *
     * @test
     */
    public function canSetAndUnsetSessionKey(): void
    {
        $uniqueSessionId = StringUtility::getUniqueId('test');

        $sessionRecord = [
            'ses_id' => $uniqueSessionId . '--not-checked--',
            'ses_data' => serialize(['foo' => 'bar']),
            'ses_userid' => 0,
            'ses_iplock' => '[DISABLED]',
        ];
        $userSession = UserSession::createFromRecord($sessionRecord['ses_id'], $sessionRecord);

        // Main session backend setup
        $userSessionManagerMock = $this->createMock(UserSessionManager::class);
        $userSessionManagerMock->method('createFromRequestOrAnonymous')->with(self::anything())->willReturn($userSession);
        // Verify new session id is generated
        $userSessionManagerMock->method('createAnonymousSession')->willReturn(UserSession::createNonFixated('newSessionId'));
        // set() and update() shouldn't be called since no session cookie is set
        // remove() should be called with given session id
        $userSessionManagerMock->expects(self::once())->method('isSessionPersisted')->with(self::anything())->willReturn(true);
        $userSessionManagerMock->expects(self::once())->method('removeSession')->with(self::anything());

        // set() and update() shouldn't be called since no session cookie is set
        $userSessionManagerMock->expects(self::never())->method('elevateToFixatedUserSession')->with(self::anything());
        $userSessionManagerMock->expects(self::never())->method('updateSession')->with(self::anything());

        $subject = new FrontendUserAuthentication();
        $subject->initializeUserSessionManager($userSessionManagerMock);
        $subject->setLogger(new NullLogger());
        $subject->start(new ServerRequest());
        $subject->setSessionData('foo', 'bar');
        $subject->removeSessionData();
        self::assertNull($subject->getSessionData('someKey'));
    }

    /**
     * A user that is not signed in should be able to have associated session data
     *
     * @test
     */
    public function canSetSessionDataForAnonymousUser(): void
    {
        $uniqueSessionId = StringUtility::getUniqueId('test');
        $currentTime = $GLOBALS['EXEC_TIME'];

        // Main session backend setup
        $userSession = UserSession::createNonFixated($uniqueSessionId);
        $userSessionManagerMock = $this->createMock(UserSessionManager::class);
        $userSessionManagerMock->method('createFromRequestOrAnonymous')->withAnyParameters()->willReturn($userSession);
        $userSessionManagerMock->method('createAnonymousSession')->withAnyParameters()->willReturn($userSession);
        // Verify new session id is generated
        // set() and update() shouldn't be called since no session cookie is set
        // remove() should be called with given session id
        $userSessionManagerMock->expects(self::once())->method('isSessionPersisted')->with(self::anything())->willReturn(true);
        $userSessionManagerMock->expects(self::never())->method('removeSession')->with(self::anything());

        // set() and update() shouldn't be called since no session cookie is set
        $userSessionManagerMock->expects(self::never())->method('elevateToFixatedUserSession')->with(self::anything());
        $userSessionManagerMock->expects(self::once())->method('updateSession')->with(self::anything());

        // new session should be written
        $sessionRecord = [
            'ses_id' => 'newSessionId',
            'ses_iplock' => '',
            'ses_userid' => 0,
            'ses_tstamp' => $currentTime,
            'ses_data' => serialize(['foo' => 'bar']),
            'ses_permanent' => 0,
        ];
        $userSessionToBePersisted = UserSession::createFromRecord($uniqueSessionId, $sessionRecord, true);
        $userSessionToBePersisted->set('foo', 'bar');
        $userSessionManagerMock->expects(self::once())->method('updateSession')->with($userSessionToBePersisted);

        $subject = new FrontendUserAuthentication();
        $subject->initializeUserSessionManager($userSessionManagerMock);
        $subject->setLogger(new NullLogger());
        $subject->start(new ServerRequest());
        self::assertEmpty($subject->getSessionData($uniqueSessionId));
        self::assertEmpty($subject->user);
        $subject->setSessionData('foo', 'bar');
        self::assertNotNull($subject->getSessionData('foo'));

        // Suppress "headers already sent" errors - phpunit does that internally already
        $prev = error_reporting(0);
        $subject->storeSessionData();
        error_reporting($prev);
    }
}
