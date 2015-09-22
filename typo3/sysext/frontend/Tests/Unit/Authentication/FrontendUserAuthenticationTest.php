<?php
declare(strict_types=1);
namespace TYPO3\CMS\Frontend\Tests\Unit\Authentication;

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

use TYPO3\CMS\Core\Session\Backend\Exception\SessionNotFoundException;
use TYPO3\CMS\Core\Session\Backend\SessionBackendInterface;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;
use TYPO3\CMS\Sv\AuthenticationService;
use TYPO3\Components\TestingFramework\Core\AccessibleObjectInterface;
use TYPO3\Components\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test cases for FrontendUserAuthentication
 */
class FrontendUserAuthenticationTest extends UnitTestCase
{

    /** @var FrontendUserAuthentication|\PHPUnit_Framework_MockObject_MockObject|AccessibleObjectInterface */
    protected $subject;

    /**
     * Sets up FrontendUserAuthentication mock
     */
    protected function setUp()
    {
        parent::setUp();
        $this->subject = $this->getMockBuilder($this->buildAccessibleProxy(FrontendUserAuthentication::class))
            ->setMethods([
                'getSessionBackend',
                'createSessionId',
                'getCookie',
                'ipLockClause_remoteIPNumber',
                'hashLockClause_getHashInt',
                'getLoginFormData',
                'getRawUserByUid',
                'getAuthInfoArray',
                'setSessionCookie',
                'removeCookie',
                'getAuthServices',
                'createUserSession',
                'updateLoginTimestamp'
            ])
            ->getMock();

        $this->subject->method('getAuthInfoArray')->willReturn([]);

        $this->subject->method('getRawUserByUid')->willReturn([
            'uid' => 1,
            'username' => 'existingUserName',
            'password' => 'abc',
            'deleted' => 0,
            'disabled' => 0
        ])->with(1);

        $this->subject->method('ipLockClause_remoteIPNumber')->willReturn(0);
        $this->subject->method('hashLockClause_getHashInt')->willReturn(0);
    }

    /**
     * user properties should not be set for anonymous sessions
     *
     * @test
     */
    public function userFieldsIsNotSetForAnonymousSessions()
    {
        // Mock SessionBackend
        $sessionBackend = $this->getMockBuilder(SessionBackendInterface::class)->getMock();
        $oldSessionRecord = [
            'ses_id' => 'oldSessionId',
            'ses_data' => serialize(['foo' => 'bar']),
            'ses_anonymous' => true,
            'ses_iplock' => 0,
        ];
        $sessionBackend->method('get')->willReturn($oldSessionRecord);
        $this->subject->method('getSessionBackend')->willReturn($sessionBackend);
        $this->subject->expects($this->never())->method('createSessionId');

        // Load anonymous sessions
        $this->subject->method('getCookie')->willReturn('oldSessionId');

        $this->subject->start();
        $this->assertArrayNotHasKey('uid', $this->subject->user);
        $this->assertEquals(['foo' => 'bar'], $this->subject->_get('sessionData'));
        $this->assertEquals('oldSessionId', $this->subject->id);
    }

    /**
     * @test
     */
    public function storeSessionDataOnAnonymousUserWithNoData()
    {
        // Mock SessionBackend
        $sessionBackend = $this->getMockBuilder(SessionBackendInterface::class)->getMock();
        $this->subject->method('getSessionBackend')->willReturn($sessionBackend);
        $this->subject->method('createSessionId')->willReturn('newSessionId');

        $sessionBackend->expects($this->never())->method('set');
        $sessionBackend->expects($this->never())->method('update');

        $this->subject->start();
        $this->subject->storeSessionData();
    }

    /**
     * Setting and immediately removing session data should be handled correctly.
     * No write operations should be made
     *
     * @test
     */
    public function canSetAndUnsetSessionKey()
    {
        // Mock SessionBackend
        $sessionBackend = $this->getMockBuilder(SessionBackendInterface::class)->getMock();
        $this->subject->method('getSessionBackend')->willReturn($sessionBackend);
        $this->subject->method('createSessionId')->willReturn('newSessionId');

        $sessionBackend->expects($this->never())->method('set');
        $sessionBackend->expects($this->never())->method('update');

        $this->subject->start();
        $this->subject->setSessionData('foo', 'bar');
        $this->subject->removeSessionData();
        $this->assertAttributeEmpty('sessionData', $this->subject);
        $this->subject->storeSessionData();
    }

    /**
     * A user that is not signed in should be able to have associated session data
     *
     * @test
     */
    public function canSetSessionDataForAnonymousUser()
    {
        $sessionBackend = $this->getMockBuilder(SessionBackendInterface::class)->getMock();
        // Mock SessionBackend
        $this->subject->method('getSessionBackend')->willReturn($sessionBackend);

        $this->subject->method('createSessionId')->willReturn('newSessionId');

        $expectedSessionRecord = [
            'ses_anonymous' => 1,
            'ses_data' => serialize(['foo' => 'bar'])
        ];

        $sessionBackend->expects($this->any())->method('get');
        $sessionBackend->expects($this->once())->method('set')->with('newSessionId', new \PHPUnit_Framework_Constraint_ArraySubset($expectedSessionRecord));

        $this->subject->start();
        $this->assertEmpty($this->subject->_get('sessionData'));
        $this->assertEmpty($this->subject->user);
        $this->subject->setSessionData('foo', 'bar');
        $this->assertAttributeNotEmpty('sessionData', $this->subject);
        $this->subject->storeSessionData();
    }

    /**
     * Session data should be loaded when a session cookie is available and user user is authenticated
     *
     * @test
     */
    public function canLoadExistingAuthenticatedSession()
    {
        // Mock SessionBackend
        $sessionBackend = $this->getMockBuilder(SessionBackendInterface::class)->getMock();
        $sessionBackend->method('get')->willReturn(
            [
                'ses_id' => 'existingId',
                'ses_userid' => 1, // fe_user with uid 0 assumed in database, see fixtures.xml
                'ses_data' => serialize(['foo' => 'bar']),
                'ses_iplock' => 0,
                'ses_tstamp' => time() + 100 // Return a time in future to make avoid mocking $GLOBALS['EXEC_TIME']
            ]
        );
        $this->subject->method('getSessionBackend')->willReturn($sessionBackend);
        $this->subject->method('getCookie')->willReturn('existingId');

        $this->subject->start();
        $this->assertFalse($this->subject->_get('loginFailure'));
        $this->assertAttributeNotEmpty('user', $this->subject);
        $this->assertEquals('existingUserName', $this->subject->user['username']);
    }

    /**
     * @test
     */
    public function canLogUserInWithoutAnonymousSession()
    {
        // Mock SessionBackend
        $sessionBackend = $this->getMockBuilder(SessionBackendInterface::class)->getMock();

        $sessionBackend->expects($this->at(0))->method('get')->willThrowException(new SessionNotFoundException('testing', 1486163180));

        // Mock a login attempt
        $this->subject->method('getLoginFormData')->willReturn([
            'status' => 'login',
            'uname' => 'existingUserName',
            'uident' => 'abc'
        ]);
        $this->subject->method('createSessionId')->willReturn('newSessionId');

        $authServiceMock = $this->getMockBuilder(AuthenticationService::class)->getMock();
        $authServiceMock->method('getUser')->willReturn([
            'uid' => 1,
            'username' => 'existingUserName'
        ]);

        $authServiceMock->method('authUser')->willReturn(true); // Auth services can return true or 200

        // We need to wrap the array to something thats is \Traversable, in PHP 7.1 we can use traversable pseudo type instead
        $this->subject->method('getAuthServices')->willReturn(new \ArrayIterator([$authServiceMock]));

        $this->subject->method('createUserSession')->willReturn([
            'ses_id' => 'newSessionId'
        ]);

        $this->subject->method('getSessionBackend')->willReturn($sessionBackend);
        $this->subject->method('getCookie')->willReturn(null);

        $this->subject->start();
        $this->assertFalse($this->subject->_get('loginFailure'));
        $this->assertEquals('existingUserName', $this->subject->user['username']);
    }

    /**
     * Session data set before a user is signed in should be preserved when signing in
     *
     * @test
     */
    public function canPreserveSessionDataWhenAuthenticating()
    {
        // Mock SessionBackend
        $sessionBackend = $this->getMockBuilder(SessionBackendInterface::class)->getMock();

        $oldSessionRecord = [
            'ses_id' => 'oldSessionId',
            'ses_data' => serialize(['foo' => 'bar']),
            'ses_anonymous' => 1,
            'ses_iplock' => 0,
        ];

        // Return old, non authenticated session
        $sessionBackend->method('get')->willReturn($oldSessionRecord);

        $expectedSessionRecord = array_merge(
            $oldSessionRecord,
            [
                //ses_id is overwritten by the session backend
                'ses_anonymous' => 0
            ]
        );

        $expectedUserId = 1;

        $sessionBackend->expects($this->once())->method('set')->with(
            'newSessionId',
            $this->equalTo($expectedSessionRecord)
        )->willReturnArgument(1);

        $this->subject->method('getSessionBackend')->willReturn($sessionBackend);
        // Load old sessions
        $this->subject->method('getCookie')->willReturn('oldSessionId');
        $this->subject->method('createSessionId')->willReturn('newSessionId');

        // Mock a login attempt
        $this->subject->method('getLoginFormData')->willReturn([
            'status' => 'login',
            'uname' => 'existingUserName',
            'uident' => 'abc'
        ]);

        $authServiceMock = $this->getMockBuilder(AuthenticationService::class)->getMock();
        $authServiceMock->method('getUser')->willReturn([
            'uid' => 1,
            'username' => 'existingUserName'
        ]);

        $authServiceMock->method('authUser')->willReturn(true); // Auth services can return true or 200

        // We need to wrap the array to something thats is \Traversable, in PHP 7.1 we can use traversable pseudo type instead
        $this->subject->method('getAuthServices')->willReturn(new \ArrayIterator([$authServiceMock]));

        // Should call regenerateSessionId
        // New session should be stored with with old values
        $this->subject->start();

        $this->assertEquals('newSessionId', $this->subject->id);
        $this->assertEquals($expectedUserId, $this->subject->user['uid']);
        $this->subject->setSessionData('foobar', 'baz');
        $this->assertArraySubset(['foo' => 'bar'], $this->subject->_get('sessionData'));
        $this->assertTrue($this->subject->sesData_change);
    }

    /**
     * removeSessionData should clear all session data
     *
     * @test
     */
    public function canRemoveSessionData()
    {
        // Mock SessionBackend
        $sessionBackend = $this->getMockBuilder(SessionBackendInterface::class)->getMock();
        $sessionBackend->method('get')->willReturn(
            [
                'ses_id' => 'existingId',
                'ses_userid' => 1, // fe_user with uid 0 assumed in database, see fixtures.xml
                'ses_data' => serialize(['foo' => 'bar']),
                'ses_iplock' => 0,
                'ses_tstamp' => time() + 100 // Return a time in future to make avoid mocking $GLOBALS['EXEC_TIME']
            ]
        );
        $this->subject->method('getSessionBackend')->willReturn($sessionBackend);
        $this->subject->method('getCookie')->willReturn('existingId');

        $this->subject->start();

        $this->subject->removeSessionData();
        $this->assertEmpty($this->subject->getSessionData('foo'));
        $this->subject->storeSessionData();
        $this->assertEmpty($this->subject->getSessionData('foo'));
    }

    /**
     * @test
     *
     * If a user has an anonymous session, and its data is set to null, then the record is removed
     *
     */
    public function destroysAnonymousSessionIfDataIsNull()
    {
        $sessionBackend = $this->getMockBuilder(SessionBackendInterface::class)->getMock();
        // Mock SessionBackend
        $this->subject->method('getSessionBackend')->willReturn($sessionBackend);

        $this->subject->method('createSessionId')->willReturn('newSessionId');

        $expectedSessionRecord = [
            'ses_anonymous' => 1,
            'ses_data' => serialize(['foo' => 'bar'])
        ];

        $sessionBackend->expects($this->at(0))->method('get')->willThrowException(new SessionNotFoundException('testing', 1486045419));
        $sessionBackend->expects($this->at(1))->method('get')->willThrowException(new SessionNotFoundException('testing', 1486045420));
        $sessionBackend->expects($this->at(2))->method('get')->willReturn([
        'ses_id' => 'newSessionId',
            'ses_anonymous' => 1
        ]);

        $sessionBackend->expects($this->once())
            ->method('set')
            ->with('newSessionId', new \PHPUnit_Framework_Constraint_ArraySubset($expectedSessionRecord))
            ->willReturn([
                'ses_id' => 'newSessionId',
                'ses_anonymous' => 1,
                'ses_data' => serialize(['foo' => 'bar'])
            ]);

        // Can set and store session data
        $this->subject->start();
        $this->assertEmpty($this->subject->_get('sessionData'));
        $this->assertEmpty($this->subject->user);
        $this->subject->setSessionData('foo', 'bar');
        $this->assertAttributeNotEmpty('sessionData', $this->subject);
        $this->subject->storeSessionData();

        // Should delete session after setting to null
        $this->subject->setSessionData('foo', null);
        $this->assertAttributeEmpty('sessionData', $this->subject);
        $sessionBackend->expects($this->once())->method('remove')->with('newSessionId');
        $sessionBackend->expects($this->never())->method('update');

        $this->subject->storeSessionData();
    }

    /**
     * @test
     * Any session data set when logged in should be preserved when logging out
     *
     */
    public function sessionDataShouldBePreservedOnLogout()
    {
        $sessionBackend = $this->getMockBuilder(SessionBackendInterface::class)->getMock();
        $this->subject->method('getSessionBackend')->willReturn($sessionBackend);
        $this->subject->method('createSessionId')->willReturn('newSessionId');

        $sessionBackend->method('get')->willReturn(
            [
                'ses_id' => 'existingId',
                'ses_userid' => 1,
                'ses_data' => serialize(['foo' => 'bar']),
                'ses_iplock' => 0,
                'ses_tstamp' => time() + 100 // Return a time in future to make avoid mocking $GLOBALS['EXEC_TIME']
            ]
        );
        $this->subject->method('getSessionBackend')->willReturn($sessionBackend);
        $this->subject->method('getCookie')->willReturn('existingId');

        $this->subject->method('getRawUserByUid')->willReturn([
            'uid' => 1,
        ]);

        // fix logout data
        // Mock a logout attempt
        $this->subject->method('getLoginFormData')->willReturn([
            'status' => 'logout',

        ]);

        $sessionBackend->expects($this->once())->method('set')->with('newSessionId', $this->anything())->willReturnArgument(1);
        $sessionBackend->expects($this->once())->method('remove')->with('existingId');

        // start
        $this->subject->start();
        // asset that session data is there
        $this->assertNotEmpty($this->subject->user);
        $this->assertEquals(1, (int)$this->subject->user['ses_anonymous']);
        $this->assertEquals(['foo' => 'bar'], $this->subject->_get('sessionData'));

        $this->assertEquals('newSessionId', $this->subject->id);
    }
}
