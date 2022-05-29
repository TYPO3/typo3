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

use Doctrine\DBAL\Result;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\NullLogger;
use TYPO3\CMS\Core\Authentication\AuthenticationService;
use TYPO3\CMS\Core\Authentication\IpLocker;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Expression\CompositeExpression;
use TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Http\Request;
use TYPO3\CMS\Core\Session\Backend\SessionBackendInterface;
use TYPO3\CMS\Core\Session\UserSession;
use TYPO3\CMS\Core\Session\UserSessionManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test cases for FrontendUserAuthentication
 *
 * @todo: Some of these tests would be better suited as functional tests
 */
class FrontendUserAuthenticationTest extends UnitTestCase
{
    use ProphecyTrait;

    private const NOT_CHECKED_INDICATOR = '--not-checked--';

    protected bool $resetSingletonInstances = true;

    /**
     * User properties should not be set for anonymous sessions
     *
     * @test
     */
    public function userFieldIsNotSetForAnonymousSessions(): void
    {
        $uniqueSessionId = StringUtility::getUniqueId('test');

        // Prepare a request with session id cookie
        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getCookieParams()->willReturn(['fe_typo_user' => $uniqueSessionId]);
        $request->getParsedBody()->willReturn([]);
        $request->getQueryParams()->willReturn([]);

        // This setup fakes the "getAuthInfoArray() db call
        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);
        $connectionPoolProphecy = $this->prophesize(ConnectionPool::class);
        $connectionPoolProphecy->getQueryBuilderForTable('fe_users')->willReturn($queryBuilderProphecy->reveal());
        GeneralUtility::addInstance(ConnectionPool::class, $connectionPoolProphecy->reveal());
        $expressionBuilderProphecy = $this->prophesize(ExpressionBuilder::class);
        $queryBuilderProphecy->expr()->willReturn($expressionBuilderProphecy->reveal());
        $compositeExpressionProphecy = $this->prophesize(CompositeExpression::class);
        $expressionBuilderProphecy->and(Argument::cetera())->willReturn($compositeExpressionProphecy->reveal());
        $expressionBuilderProphecy->in(Argument::cetera())->willReturn('');

        // Main session backend setup
        $sessionBackendProphecy = $this->prophesize(SessionBackendInterface::class);
        $sessionRecord = [
            'ses_id' => $uniqueSessionId . self::NOT_CHECKED_INDICATOR,
            'ses_data' => serialize(['foo' => 'bar']),
            'ses_tstamp' => time(),
            'ses_userid' => 0,
            'ses_iplock' => '[DISABLED]',
        ];
        $sessionBackendProphecy->get($uniqueSessionId)->shouldBeCalled()->willReturn($sessionRecord);

        $userSessionManager = new UserSessionManager(
            $sessionBackendProphecy->reveal(),
            86400,
            new IpLocker(0, 0)
        );
        $subject = new FrontendUserAuthentication();
        $subject->setLogger(new NullLogger());
        $subject->initializeUserSessionManager($userSessionManager);
        $subject->start($request->reveal());

        self::assertIsNotArray($subject->user);
        self::assertEquals('bar', $subject->getSessionData('foo'));
        self::assertEquals($uniqueSessionId, $subject->getSession()->getIdentifier());
    }

    /**
     * @test
     */
    public function storeSessionDataOnAnonymousUserWithNoData(): void
    {
        // This setup fakes the "getAuthInfoArray() db call
        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);
        $connectionPoolProphecy = $this->prophesize(ConnectionPool::class);
        $connectionPoolProphecy->getQueryBuilderForTable('fe_users')->willReturn($queryBuilderProphecy->reveal());
        GeneralUtility::addInstance(ConnectionPool::class, $connectionPoolProphecy->reveal());
        $expressionBuilderProphecy = $this->prophesize(ExpressionBuilder::class);
        $queryBuilderProphecy->expr()->willReturn($expressionBuilderProphecy->reveal());
        $compositeExpressionProphecy = $this->prophesize(CompositeExpression::class);
        $expressionBuilderProphecy->and(Argument::cetera())->willReturn($compositeExpressionProphecy->reveal());
        $expressionBuilderProphecy->in(Argument::cetera())->willReturn('');

        $userSessionManager = $this->prophesize(UserSessionManager::class);
        $userSessionManager->createFromRequestOrAnonymous(Argument::cetera())->willReturn(UserSession::createNonFixated('newSessionId'));
        // Verify new session id is generated
        $userSessionManager->createAnonymousSession()->willReturn(UserSession::createNonFixated('newSessionId'));
        // set() and update() shouldn't be called since no session cookie is set
        $userSessionManager->elevateToFixatedUserSession(Argument::cetera())->shouldNotBeCalled();
        $userSessionManager->updateSession(Argument::cetera())->shouldNotBeCalled();

        $subject = new FrontendUserAuthentication();
        $subject->setLogger(new NullLogger());
        $subject->initializeUserSessionManager($userSessionManager->reveal());
        $subject->start($this->prophesize(ServerRequestInterface::class)->reveal());
        $subject->storeSessionData();
    }

    /**
     * Setting and immediately removing session data should be handled correctly.
     * No write operations should be made
     *
     * @test
     */
    public function canSetAndUnsetSessionKey(): void
    {
        $uniqueSessionId = StringUtility::getUniqueId('test');

        // This setup fakes the "getAuthInfoArray() db call
        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);
        $connectionPoolProphecy = $this->prophesize(ConnectionPool::class);
        $connectionPoolProphecy->getQueryBuilderForTable('fe_users')->willReturn($queryBuilderProphecy->reveal());
        GeneralUtility::addInstance(ConnectionPool::class, $connectionPoolProphecy->reveal());
        $expressionBuilderProphecy = $this->prophesize(ExpressionBuilder::class);
        $queryBuilderProphecy->expr()->willReturn($expressionBuilderProphecy->reveal());
        $compositeExpressionProphecy = $this->prophesize(CompositeExpression::class);
        $expressionBuilderProphecy->and(Argument::cetera())->willReturn($compositeExpressionProphecy->reveal());
        $expressionBuilderProphecy->in(Argument::cetera())->willReturn('');

        $sessionRecord = [
            'ses_id' => $uniqueSessionId . self::NOT_CHECKED_INDICATOR,
            'ses_data' => serialize(['foo' => 'bar']),
            'ses_userid' => 0,
            'ses_iplock' => '[DISABLED]',
        ];
        $userSession = UserSession::createFromRecord($sessionRecord['ses_id'], $sessionRecord);

        // Main session backend setup
        $userSessionManager = $this->prophesize(UserSessionManager::class);
        $userSessionManager->createFromRequestOrAnonymous(Argument::cetera())->willReturn($userSession);
        // Verify new session id is generated
        $userSessionManager->createAnonymousSession()->willReturn(UserSession::createNonFixated('newSessionId'));
        // set() and update() shouldn't be called since no session cookie is set
        // remove() should be called with given session id
        $userSessionManager->isSessionPersisted(Argument::cetera())->shouldBeCalled()->willReturn(true);
        $userSessionManager->removeSession(Argument::cetera())->shouldBeCalled();

        // set() and update() shouldn't be called since no session cookie is set
        $userSessionManager->elevateToFixatedUserSession(Argument::cetera())->shouldNotBeCalled();
        $userSessionManager->updateSession(Argument::cetera())->shouldNotBeCalled();

        $subject = new FrontendUserAuthentication();
        $subject->initializeUserSessionManager($userSessionManager->reveal());
        $subject->setLogger(new NullLogger());
        $subject->start($this->prophesize(ServerRequestInterface::class)->reveal());
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

        // This setup fakes the "getAuthInfoArray() db call
        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);
        $connectionPoolProphecy = $this->prophesize(ConnectionPool::class);
        $connectionPoolProphecy->getQueryBuilderForTable('fe_users')->willReturn($queryBuilderProphecy->reveal());
        GeneralUtility::addInstance(ConnectionPool::class, $connectionPoolProphecy->reveal());
        $expressionBuilderProphecy = $this->prophesize(ExpressionBuilder::class);
        $queryBuilderProphecy->expr()->willReturn($expressionBuilderProphecy->reveal());
        $compositeExpressionProphecy = $this->prophesize(CompositeExpression::class);
        $expressionBuilderProphecy->and(Argument::cetera())->willReturn($compositeExpressionProphecy->reveal());
        $expressionBuilderProphecy->in(Argument::cetera())->willReturn('');

        // Main session backend setup
        $userSession = UserSession::createNonFixated($uniqueSessionId);
        $userSessionManager = $this->prophesize(UserSessionManager::class);
        $userSessionManager->createFromRequestOrAnonymous(Argument::cetera())->willReturn($userSession);
        $userSessionManager->createAnonymousSession(Argument::cetera())->willReturn($userSession);
        // Verify new session id is generated
        // set() and update() shouldn't be called since no session cookie is set
        // remove() should be called with given session id
        $userSessionManager->isSessionPersisted(Argument::cetera())->shouldBeCalled()->willReturn(true);
        $userSessionManager->removeSession(Argument::cetera())->shouldNotBeCalled();

        // set() and update() shouldn't be called since no session cookie is set
        $userSessionManager->elevateToFixatedUserSession(Argument::cetera())->shouldNotBeCalled();
        $userSessionManager->updateSession(Argument::cetera())->shouldBeCalled();

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
        $userSessionManager->updateSession($userSessionToBePersisted)->shouldBeCalled();

        $subject = new FrontendUserAuthentication();
        $subject->initializeUserSessionManager($userSessionManager->reveal());
        $subject->setLogger(new NullLogger());
        $subject->start($this->prophesize(ServerRequestInterface::class)->reveal());
        self::assertEmpty($subject->getSessionData($uniqueSessionId));
        self::assertEmpty($subject->user);
        $subject->setSessionData('foo', 'bar');
        self::assertNotNull($subject->getSessionData('foo'));

        // Suppress "headers already sent" errors - phpunit does that internally already
        $prev = error_reporting(0);
        $subject->storeSessionData();
        error_reporting($prev);
    }

    /**
     * Session data should be loaded when a session cookie is available and a user is authenticated
     *
     * @test
     */
    public function canLoadExistingAuthenticatedSession(): void
    {
        $uniqueSessionId = StringUtility::getUniqueId('test');
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
        $expressionBuilderProphecy->and(Argument::cetera())->willReturn($compositeExpressionProphecy->reveal());
        $expressionBuilderProphecy->in(Argument::cetera())->willReturn('');

        // Main session backend setup
        $sessionRecord = [
            'ses_id' => $uniqueSessionId . self::NOT_CHECKED_INDICATOR,
            'ses_userid' => 1,
            'ses_iplock' => '[DISABLED]',
            'ses_tstamp' => $currentTime,
            'ses_data' => serialize(['foo' => 'bar']),
            'ses_permanent' => 0,
        ];
        $userSession = UserSession::createFromRecord($uniqueSessionId, $sessionRecord);
        $userSessionManager = $this->prophesize(UserSessionManager::class);
        $userSessionManager->createAnonymousSession()->willReturn(UserSession::createNonFixated('not-in-use'));
        $userSessionManager->createFromRequestOrAnonymous(Argument::cetera())->willReturn($userSession);
        $userSessionManager->hasExpired($userSession)->willReturn(false);
        $userSessionManager->updateSessionTimestamp($userSession)->shouldBeCalled()->willReturn($userSession);

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
        $statementFeUserProphecy = $this->prophesize(Result::class);
        $queryBuilderFeUserProphecy->executeQuery()->shouldBeCalled()->willReturn($statementFeUserProphecy->reveal());
        $statementFeUserProphecy->fetchAssociative()->willReturn(
            [
                'uid' => 1,
                'username' => 'existingUserName',
                'password' => 'abc',
                'deleted' => 0,
                'disabled' => 0,
            ]
        );

        $subject = new FrontendUserAuthentication();
        $subject->initializeUserSessionManager($userSessionManager->reveal());
        $subject->setLogger(new NullLogger());
        $subject->start($this->prophesize(ServerRequestInterface::class)->reveal());

        self::assertNotNull($subject->user);
        self::assertEquals('existingUserName', $subject->user['username']);
    }

    /**
     * @test
     */
    public function canLogUserInWithoutAnonymousSession(): void
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
        $expressionBuilderProphecy->and(Argument::cetera())->willReturn($compositeExpressionProphecy->reveal());
        $expressionBuilderProphecy->in(Argument::cetera())->willReturn('');

        // Main session backend setup
        $userSession = UserSession::createNonFixated('newSessionId');
        $elevatedUserSession = UserSession::createFromRecord('newSessionId', ['ses_userid' => 1], true);
        $userSessionManager = $this->prophesize(UserSessionManager::class);
        $userSessionManager->createAnonymousSession()->willReturn(UserSession::createNonFixated('not-in-use'));
        $userSessionManager->createFromRequestOrAnonymous(Argument::cetera())->willReturn($userSession);
        $userSessionManager->removeSession($userSession)->shouldBeCalled();
        $userSessionManager->elevateToFixatedUserSession(Argument::cetera())->shouldBeCalled()->willReturn($elevatedUserSession);

        // Mock the login data and auth services here since fully prophesize this is a lot of hassle
        $subject = $this->getAccessibleMock(
            FrontendUserAuthentication::class,
            [
                'getLoginFormData',
                'getAuthServices',
                'updateLoginTimestamp',
                'setSessionCookie',
            ]
        );
        $subject->setLogger(new NullLogger());
        $subject->initializeUserSessionManager($userSessionManager->reveal());

        // Mock a login attempt
        $subject->method('getLoginFormData')->willReturn([
            'status' => 'login',
            'uname' => 'existingUserName',
            'uident' => 'abc',
        ]);

        $authServiceMock = $this->getMockBuilder(AuthenticationService::class)->getMock();
        $authServiceMock->method('getUser')->willReturn([
            'uid' => 1,
            'username' => 'existingUserName',
        ]);
        // Auth services can return status codes: 0 (failed/abort), 100 (not responsible, continue), 200 (ok)
        $authServiceMock->method('authUser')->willReturn(200);
        // We need to wrap the array to something thats is \Traversable, in PHP 7.1 we can use traversable pseudo type instead
        $subject->method('getAuthServices')->willReturn(new \ArrayIterator([$authServiceMock]));
        $subject->start($this->prophesize(ServerRequestInterface::class)->reveal());
        self::assertEquals('existingUserName', $subject->user['username']);
    }
}
