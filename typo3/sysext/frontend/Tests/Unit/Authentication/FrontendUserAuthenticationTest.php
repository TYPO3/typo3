<?php
declare(strict_types = 1);
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

use Doctrine\DBAL\Statement;
use Prophecy\Argument;
use Psr\Log\NullLogger;
use TYPO3\CMS\Core\Authentication\AuthenticationService;
use TYPO3\CMS\Core\Crypto\Random;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Expression\CompositeExpression;
use TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Session\Backend\Exception\SessionNotFoundException;
use TYPO3\CMS\Core\Session\Backend\SessionBackendInterface;
use TYPO3\CMS\Core\Session\SessionManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test cases for FrontendUserAuthentication
 *
 * @todo: Some of these tests would be better suited as functional tests
 */
class FrontendUserAuthenticationTest extends UnitTestCase
{
    private const NOT_CHECKED_INDICATOR = '--not-checked--';

    /**
     * @var bool Reset singletons created by subject
     */
    protected $resetSingletonInstances = true;

    /**
     * User properties should not be set for anonymous sessions
     *
     * @test
     */
    public function userFieldIsNotSetForAnonymousSessions()
    {
        $uniqueSessionId = $this->getUniqueId('test');
        $_COOKIE['fe_typo_user'] = $uniqueSessionId;

        // This setup fakes the "getAuthInfoArray() db call
        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);
        $connectionPoolProphecy = $this->prophesize(ConnectionPool::class);
        $connectionPoolProphecy->getQueryBuilderForTable('fe_users')->willReturn($queryBuilderProphecy->reveal());
        GeneralUtility::addInstance(ConnectionPool::class, $connectionPoolProphecy->reveal());
        $expressionBuilderProphecy = $this->prophesize(ExpressionBuilder::class);
        $queryBuilderProphecy->expr()->willReturn($expressionBuilderProphecy->reveal());
        $compositeExpressionProphecy = $this->prophesize(CompositeExpression::class);
        $expressionBuilderProphecy->andX(Argument::cetera())->willReturn($compositeExpressionProphecy->reveal());
        $expressionBuilderProphecy->in(Argument::cetera())->willReturn('');

        // Main session backend setup
        $sessionBackendProphecy = $this->prophesize(SessionBackendInterface::class);
        $sessionRecord = [
            'ses_id' => $uniqueSessionId . self::NOT_CHECKED_INDICATOR,
            'ses_data' => serialize(['foo' => 'bar']),
            'ses_anonymous' => true,
            'ses_iplock' => '[DISABLED]',
        ];
        $sessionBackendProphecy->get($uniqueSessionId)->shouldBeCalled()->willReturn($sessionRecord);
        $sessionManagerProphecy = $this->prophesize(SessionManager::class);
        GeneralUtility::setSingletonInstance(SessionManager::class, $sessionManagerProphecy->reveal());
        $sessionManagerProphecy->getSessionBackend('FE')->willReturn($sessionBackendProphecy->reveal());

        $subject = new FrontendUserAuthentication();
        $subject->setLogger(new NullLogger());
        $subject->gc_probability = -1;
        $subject->start();

        $this->assertArrayNotHasKey('uid', $subject->user);
        $this->assertEquals('bar', $subject->getSessionData('foo'));
        $this->assertEquals($uniqueSessionId, $subject->id);
    }

    /**
     * @test
     */
    public function storeSessionDataOnAnonymousUserWithNoData()
    {
        // This setup fakes the "getAuthInfoArray() db call
        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);
        $connectionPoolProphecy = $this->prophesize(ConnectionPool::class);
        $connectionPoolProphecy->getQueryBuilderForTable('fe_users')->willReturn($queryBuilderProphecy->reveal());
        GeneralUtility::addInstance(ConnectionPool::class, $connectionPoolProphecy->reveal());
        $expressionBuilderProphecy = $this->prophesize(ExpressionBuilder::class);
        $queryBuilderProphecy->expr()->willReturn($expressionBuilderProphecy->reveal());
        $compositeExpressionProphecy = $this->prophesize(CompositeExpression::class);
        $expressionBuilderProphecy->andX(Argument::cetera())->willReturn($compositeExpressionProphecy->reveal());
        $expressionBuilderProphecy->in(Argument::cetera())->willReturn('');

        // Main session backend setup
        $sessionBackendProphecy = $this->prophesize(SessionBackendInterface::class);
        $sessionManagerProphecy = $this->prophesize(SessionManager::class);
        GeneralUtility::setSingletonInstance(SessionManager::class, $sessionManagerProphecy->reveal());
        $sessionManagerProphecy->getSessionBackend('FE')->willReturn($sessionBackendProphecy->reveal());
        // @todo Session handling is not used/evaluated at all in this test

        // Verify new session id is generated
        $randomProphecy = $this->prophesize(Random::class);
        $randomProphecy->generateRandomHexString(32)->shouldBeCalled()->willReturn('newSessionId');
        GeneralUtility::addInstance(Random::class, $randomProphecy->reveal());

        // set() and update() shouldn't be called since no session cookie is set
        $sessionBackendProphecy->set(Argument::cetera())->shouldNotBeCalled();
        $sessionBackendProphecy->update(Argument::cetera())->shouldNotBeCalled();

        $subject = new FrontendUserAuthentication();
        $subject->setLogger(new NullLogger());
        $subject->gc_probability = -1;
        $subject->start();
        $subject->storeSessionData();
    }

    /**
     * Setting and immediately removing session data should be handled correctly.
     * No write operations should be made
     *
     * @test
     */
    public function canSetAndUnsetSessionKey()
    {
        $uniqueSessionId = $this->getUniqueId('test');
        $_COOKIE['fe_typo_user'] = $uniqueSessionId;

        // This setup fakes the "getAuthInfoArray() db call
        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);
        $connectionPoolProphecy = $this->prophesize(ConnectionPool::class);
        $connectionPoolProphecy->getQueryBuilderForTable('fe_users')->willReturn($queryBuilderProphecy->reveal());
        GeneralUtility::addInstance(ConnectionPool::class, $connectionPoolProphecy->reveal());
        $expressionBuilderProphecy = $this->prophesize(ExpressionBuilder::class);
        $queryBuilderProphecy->expr()->willReturn($expressionBuilderProphecy->reveal());
        $compositeExpressionProphecy = $this->prophesize(CompositeExpression::class);
        $expressionBuilderProphecy->andX(Argument::cetera())->willReturn($compositeExpressionProphecy->reveal());
        $expressionBuilderProphecy->in(Argument::cetera())->willReturn('');

        // Main session backend setup
        $sessionBackendProphecy = $this->prophesize(SessionBackendInterface::class);
        $sessionRecord = [
            'ses_id' => $uniqueSessionId . self::NOT_CHECKED_INDICATOR,
            'ses_data' => serialize(['foo' => 'bar']),
            'ses_anonymous' => true,
            'ses_iplock' => '[DISABLED]',
        ];
        $sessionBackendProphecy->get($uniqueSessionId)->shouldBeCalled()->willReturn($sessionRecord);
        $sessionManagerProphecy = $this->prophesize(SessionManager::class);
        GeneralUtility::setSingletonInstance(SessionManager::class, $sessionManagerProphecy->reveal());
        $sessionManagerProphecy->getSessionBackend('FE')->willReturn($sessionBackendProphecy->reveal());

        // set() and update() shouldn't be called since no session cookie is set
        $sessionBackendProphecy->set(Argument::cetera())->shouldNotBeCalled();
        $sessionBackendProphecy->update(Argument::cetera())->shouldNotBeCalled();
        // remove() should be called with given session id
        $sessionBackendProphecy->remove($uniqueSessionId)->shouldBeCalled();

        $subject = new FrontendUserAuthentication();
        $subject->setLogger(new NullLogger());
        $subject->gc_probability = -1;
        $subject->start();
        $subject->setSessionData('foo', 'bar');
        $subject->removeSessionData();
        $this->assertAttributeEmpty('sessionData', $subject);
    }

    /**
     * A user that is not signed in should be able to have associated session data
     *
     * @test
     */
    public function canSetSessionDataForAnonymousUser()
    {
        $uniqueSessionId = $this->getUniqueId('test');
        $_COOKIE['fe_typo_user'] = $uniqueSessionId;
        $currentTime = $GLOBALS['EXEC_TIME'];

        // This setup fakes the "getAuthInfoArray() db call
        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);
        $connectionPoolProphecy = $this->prophesize(ConnectionPool::class);
        $connectionPoolProphecy->getQueryBuilderForTable('fe_users')->willReturn($queryBuilderProphecy->reveal());
        GeneralUtility::addInstance(ConnectionPool::class, $connectionPoolProphecy->reveal());
        $expressionBuilderProphecy = $this->prophesize(ExpressionBuilder::class);
        $queryBuilderProphecy->expr()->willReturn($expressionBuilderProphecy->reveal());
        $compositeExpressionProphecy = $this->prophesize(CompositeExpression::class);
        $expressionBuilderProphecy->andX(Argument::cetera())->willReturn($compositeExpressionProphecy->reveal());
        $expressionBuilderProphecy->in(Argument::cetera())->willReturn('');

        // Main session backend setup
        $sessionBackendProphecy = $this->prophesize(SessionBackendInterface::class);
        $sessionBackendProphecy->get($uniqueSessionId)->shouldBeCalled()->willThrow(new SessionNotFoundException('testing', 1486676313));
        $sessionManagerProphecy = $this->prophesize(SessionManager::class);
        GeneralUtility::setSingletonInstance(SessionManager::class, $sessionManagerProphecy->reveal());
        $sessionManagerProphecy->getSessionBackend('FE')->willReturn($sessionBackendProphecy->reveal());

        // Verify new session id is generated
        $randomProphecy = $this->prophesize(Random::class);
        $randomProphecy->generateRandomHexString(32)->shouldBeCalled()->willReturn('newSessionId');
        GeneralUtility::addInstance(Random::class, $randomProphecy->reveal());

        // set() and update() shouldn't be called since no session cookie is set
        $sessionBackendProphecy->update(Argument::cetera())->shouldNotBeCalled();
        $sessionBackendProphecy->get('newSessionId')->shouldBeCalled()->willThrow(new SessionNotFoundException('testing', 1486676314));

        // new session should be written
        $sessionBackendProphecy->set(
            'newSessionId',
            [
                'ses_id' => 'newSessionId',
                'ses_iplock' => '[DISABLED]',
                'ses_userid' => 0,
                'ses_tstamp' => $currentTime,
                'ses_data' => serialize(['foo' => 'bar']),
                'ses_permanent' => 0,
                'ses_anonymous' => 1 // sic!
            ]
        )->shouldBeCalled();

        $subject = new FrontendUserAuthentication();
        $subject->setLogger(new NullLogger());
        $subject->gc_probability = -1;
        $subject->start();
        $subject->lockIP = 0;
        $this->assertEmpty($subject->getSessionData($uniqueSessionId));
        $this->assertEmpty($subject->user);
        $subject->setSessionData('foo', 'bar');
        $this->assertAttributeNotEmpty('sessionData', $subject);

        // Suppress "headers already sent" errors - phpunit does that internally already
        $prev = error_reporting(0);
        $subject->storeSessionData();
        error_reporting($prev);
    }

    /**
     * Session data should be loaded when a session cookie is available and user user is authenticated
     *
     * @test
     */
    public function canLoadExistingAuthenticatedSession()
    {
        $uniqueSessionId = $this->getUniqueId('test');
        $_COOKIE['fe_typo_user'] = $uniqueSessionId;
        $currentTime = $GLOBALS['EXEC_TIME'];

        // This setup fakes the "getAuthInfoArray() db call
        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);
        $connectionPoolProphecy = $this->prophesize(ConnectionPool::class);
        $connectionPoolProphecy->getQueryBuilderForTable('fe_users')->willReturn($queryBuilderProphecy->reveal());
        GeneralUtility::addInstance(ConnectionPool::class, $connectionPoolProphecy->reveal());
        $expressionBuilderProphecy = $this->prophesize(ExpressionBuilder::class);
        $queryBuilderProphecy->expr()->willReturn($expressionBuilderProphecy->reveal());
        $compositeExpressionProphecy = $this->prophesize(CompositeExpression::class);
        $expressionBuilderProphecy->andX(Argument::cetera())->willReturn($compositeExpressionProphecy->reveal());
        $expressionBuilderProphecy->in(Argument::cetera())->willReturn('');

        // Main session backend setup
        $sessionBackendProphecy = $this->prophesize(SessionBackendInterface::class);
        $sessionManagerProphecy = $this->prophesize(SessionManager::class);
        GeneralUtility::setSingletonInstance(SessionManager::class, $sessionManagerProphecy->reveal());
        $sessionManagerProphecy->getSessionBackend('FE')->willReturn($sessionBackendProphecy->reveal());

        // a valid session is returned
        $sessionBackendProphecy->get($uniqueSessionId)->shouldBeCalled()->willReturn(
            [
                'ses_id' => $uniqueSessionId . self::NOT_CHECKED_INDICATOR,
                'ses_userid' => 1,
                'ses_iplock' => '[DISABLED]',
                'ses_tstamp' => $currentTime,
                'ses_data' => serialize(['foo' => 'bar']),
                'ses_permanent' => 0,
                'ses_anonymous' => 0 // sic!
            ]
        );

        // Mock call to fe_users table and let it return a valid user row
        $connectionPoolFeUserProphecy = $this->prophesize(ConnectionPool::class);
        GeneralUtility::addInstance(ConnectionPool::class, $connectionPoolFeUserProphecy->reveal());
        $queryBuilderFeUserProphecy = $this->prophesize(QueryBuilder::class);
        $queryBuilderFeUserProphecyRevelation = $queryBuilderFeUserProphecy->reveal();
        $connectionPoolFeUserProphecy->getQueryBuilderForTable('fe_users')->willReturn($queryBuilderFeUserProphecyRevelation);
        $queryBuilderFeUserProphecy->select('*')->willReturn($queryBuilderFeUserProphecyRevelation);
        $queryBuilderFeUserProphecy->setRestrictions(Argument::cetera())->shouldBeCalled();
        $queryBuilderFeUserProphecy->from('fe_users')->shouldBeCalled()->willReturn($queryBuilderFeUserProphecyRevelation);
        $expressionBuilderFeUserProphecy = $this->prophesize(ExpressionBuilder::class);
        $queryBuilderFeUserProphecy->expr()->willReturn($expressionBuilderFeUserProphecy->reveal());
        $queryBuilderFeUserProphecy->createNamedParameter(Argument::cetera())->willReturnArgument(0);
        $expressionBuilderFeUserProphecy->eq(Argument::cetera())->willReturn('1=1');
        $queryBuilderFeUserProphecy->where(Argument::cetera())->shouldBeCalled()->willReturn($queryBuilderFeUserProphecyRevelation);
        $statementFeUserProphecy = $this->prophesize(Statement::class);
        $queryBuilderFeUserProphecy->execute()->shouldBeCalled()->willReturn($statementFeUserProphecy->reveal());
        $statementFeUserProphecy->fetch()->willReturn(
            [
                'uid' => 1,
                'username' => 'existingUserName',
                'password' => 'abc',
                'deleted' => 0,
                'disabled' => 0
            ]
        );

        $subject = new FrontendUserAuthentication();
        $subject->setLogger(new NullLogger());
        $subject->gc_probability = -1;
        $subject->start();

        $this->assertAttributeNotEmpty('user', $subject);
        $this->assertEquals('existingUserName', $subject->user['username']);
    }

    /**
     * @test
     */
    public function canLogUserInWithoutAnonymousSession()
    {
        $GLOBALS['BE_USER'] = [];
        // This setup fakes the "getAuthInfoArray() db call
        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);
        $connectionPoolProphecy = $this->prophesize(ConnectionPool::class);
        $connectionPoolProphecy->getQueryBuilderForTable('fe_users')->willReturn($queryBuilderProphecy->reveal());
        GeneralUtility::addInstance(ConnectionPool::class, $connectionPoolProphecy->reveal());
        $expressionBuilderProphecy = $this->prophesize(ExpressionBuilder::class);
        $queryBuilderProphecy->expr()->willReturn($expressionBuilderProphecy->reveal());
        $compositeExpressionProphecy = $this->prophesize(CompositeExpression::class);
        $expressionBuilderProphecy->andX(Argument::cetera())->willReturn($compositeExpressionProphecy->reveal());
        $expressionBuilderProphecy->in(Argument::cetera())->willReturn('');

        // Main session backend setup
        $sessionBackendProphecy = $this->prophesize(SessionBackendInterface::class);
        $sessionManagerProphecy = $this->prophesize(SessionManager::class);
        GeneralUtility::setSingletonInstance(SessionManager::class, $sessionManagerProphecy->reveal());
        $sessionManagerProphecy->getSessionBackend('FE')->willReturn($sessionBackendProphecy->reveal());

        // no session exists, yet
        $sessionBackendProphecy->get('newSessionId')->willThrow(new SessionNotFoundException('testing', 1486676358));
        $sessionBackendProphecy->remove('newSessionId')->shouldBeCalled();

        // Verify new session id is generated
        $randomProphecy = $this->prophesize(Random::class);
        $randomProphecy->generateRandomHexString(32)->shouldBeCalled()->willReturn('newSessionId');
        GeneralUtility::addInstance(Random::class, $randomProphecy->reveal());

        // Mock the login data and auth services here since fully prophesize this is a lot of hassle
        $subject = $this->getMockBuilder($this->buildAccessibleProxy(FrontendUserAuthentication::class))
            ->setMethods([
                'getLoginFormData',
                'getAuthServices',
                'createUserSession',
                'getCookie',
            ])
            ->getMock();
        $subject->setLogger(new NullLogger());
        $subject->gc_probability = -1;

        // Mock a login attempt
        $subject->method('getLoginFormData')->willReturn([
            'status' => 'login',
            'uname' => 'existingUserName',
            'uident' => 'abc'
        ]);

        $authServiceMock = $this->getMockBuilder(AuthenticationService::class)->getMock();
        $authServiceMock->method('getUser')->willReturn([
            'uid' => 1,
            'username' => 'existingUserName'
        ]);
        // Auth services can return true or 200
        $authServiceMock->method('authUser')->willReturn(true);
        // We need to wrap the array to something thats is \Traversable, in PHP 7.1 we can use traversable pseudo type instead
        $subject->method('getAuthServices')->willReturn(new \ArrayIterator([$authServiceMock]));

        $subject->method('createUserSession')->willReturn([
            'ses_id' => 'newSessionId'
        ]);

        $subject->method('getCookie')->willReturn(null);

        $subject->start();
        $this->assertFalse($subject->_get('loginFailure'));
        $this->assertEquals('existingUserName', $subject->user['username']);
    }

    /**
     * Session data set before a user is signed in should be preserved when signing in
     *
     * @test
     */
    public function canPreserveSessionDataWhenAuthenticating()
    {
        $this->markTestSkipped('Test is flaky, convert to a functional test');
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
        $this->markTestSkipped('Test is flaky, convert to a functional test');
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
     */
    public function destroysAnonymousSessionIfDataIsNull()
    {
        $this->markTestSkipped('Test is flaky, convert to a functional test');
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
        $sessionBackend->expects($this->at(2))->method('get')->willReturn(
            [
                'ses_id' => 'newSessionId',
                'ses_anonymous' => 1
            ]
        );

        $sessionBackend->expects($this->once())
            ->method('set')
            ->with('newSessionId', new \PHPUnit_Framework_Constraint_ArraySubset($expectedSessionRecord))
            ->willReturn([
                'ses_id' => 'newSessionId',
                'ses_anonymous' => 1,
                'ses_data' => serialize(['foo' => 'bar']),
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
     */
    public function sessionDataShouldBePreservedOnLogout()
    {
        $this->markTestSkipped('Test is flaky, convert to a functional test');
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
