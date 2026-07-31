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

namespace TYPO3\CMS\Core\Tests\Functional\Authentication;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\NullLogger;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Authentication\Mfa\MfaRequiredException;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\SecurityAspect;
use TYPO3\CMS\Core\Crypto\PasswordHashing\PasswordHashFactory;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Expression\CompositeExpression;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Security\RequestToken;
use TYPO3\CMS\Core\Session\UserSession;
use TYPO3\CMS\Core\Tests\Functional\Authentication\Fixtures\AnyUserAuthentication;
use TYPO3\CMS\Core\Tests\Functional\Authentication\Fixtures\FixtureAuthenticationService;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class AbstractUserAuthenticationTest extends FunctionalTestCase
{
    private const PASSWORD = 'test-password-1234';

    protected function setUp(): void
    {
        parent::setUp();
        FixtureAuthenticationService::resetState();
        $passwordHash = $this->get(PasswordHashFactory::class)
            ->getDefaultHashInstance('BE')
            ->getHashedPassword(self::PASSWORD);
        $connection = $this->get(ConnectionPool::class)->getConnectionForTable('be_users');
        $connection->insert('be_users', [
            'uid' => 100,
            'username' => 'testadmin',
            'password' => $passwordHash,
            'admin' => 1,
        ]);
        $connection->insert('be_users', [
            'uid' => 101,
            'username' => 'serviceuser',
            'password' => 'invalid-hash-never-matches',
            'admin' => 0,
        ]);
        $connection->insert('be_users', [
            'uid' => 102,
            'username' => 'mfauser',
            'password' => $passwordHash,
            'admin' => 1,
            'mfa' => json_encode(['totp' => ['active' => true, 'secret' => 'KRMVATZTJFZUC53FONXW2ZJB']]),
        ]);
    }

    protected function tearDown(): void
    {
        FixtureAuthenticationService::resetState();
        parent::tearDown();
    }

    #[Test]
    public function pushModuleDataDoesNotRevealPlainSessionId(): void
    {
        $sessionId = bin2hex(random_bytes(20));
        $userSession = UserSession::createNonFixated($sessionId);
        $subject = new AnyUserAuthentication($userSession);
        $subject->pushModuleData(self::class, true);
        self::assertNotContains($sessionId, $subject->uc['moduleSessionID']);
    }

    #[Test]
    public function getModuleDataResolvesHashedSessionId(): void
    {
        $sessionId = bin2hex(random_bytes(20));
        $userSession = UserSession::createNonFixated($sessionId);
        $subject = new AnyUserAuthentication($userSession);
        $subject->pushModuleData(self::class, true);
        self::assertTrue($subject->getModuleData(self::class));
    }

    #[Test]
    public function getModuleDataFallsBackToPlainSessionId(): void
    {
        $sessionId = bin2hex(random_bytes(20));
        $userSession = UserSession::createNonFixated($sessionId);
        $subject = new AnyUserAuthentication($userSession);
        $subject->uc['moduleData'][self::class] = true;
        $subject->uc['moduleSessionID'][self::class] = $sessionId;
        self::assertTrue($subject->getModuleData(self::class));
    }

    public static function getAuthInfoArrayReturnsEmptyPidListIfNoCheckPidValueIsGivenDataProvider(): array
    {
        return [
            ['', ''],
            [null, ''],
            [0, '0'],
            ['0', '0'],
            ['12,31', '12, 31'],
        ];
    }

    #[DataProvider('getAuthInfoArrayReturnsEmptyPidListIfNoCheckPidValueIsGivenDataProvider')]
    #[Test]
    public function getAuthInfoArrayReturnsCorrectPidConstraintForGivenCheckPidValue(
        int|string|null $checkPid_value,
        string $expectedPids
    ): void {
        $sessionId = bin2hex(random_bytes(20));
        $userSession = UserSession::createNonFixated($sessionId);
        $subject = new AnyUserAuthentication($userSession);
        $subject->user_table = 'be_users';
        $subject->checkPid_value = $checkPid_value;
        $request = new ServerRequest('https://example.com');
        $request = $request->withAttribute('normalizedParams', NormalizedParams::createFromServerParams($request->getServerParams()));
        $authInfoArray = $subject->getAuthInfoArray($request);
        $enableClause = $authInfoArray['db_user']['enable_clause'];
        self::assertInstanceOf(CompositeExpression::class, $enableClause);
        $connection = $this->get(ConnectionPool::class)->getConnectionForTable('be_users');
        $expectedEnableClause = '';
        if ($expectedPids !== '') {
            $expectedEnableClause = $connection->quoteIdentifier('be_users.pid') . ' IN (' . $expectedPids . ')';
        }
        self::assertSame($expectedEnableClause, (string)$enableClause);
    }

    #[Test]
    public function correctCredentialsWithValidRequestTokenLogInUser(): void
    {
        $this->provideValidRequestToken();
        $subject = $this->createSubject();
        $subject->start($this->buildLoginRequest('testadmin', self::PASSWORD));

        self::assertSame(100, $subject->getUserId());
        self::assertSame('testadmin', $subject->getUserName());
        self::assertSame(1, $this->countSessionsOfUser(100));
    }

    #[Test]
    public function incorrectPasswordDoesNotLogInUser(): void
    {
        $this->provideValidRequestToken();
        $subject = $this->createSubject();
        $subject->start($this->buildLoginRequest('testadmin', 'wrong-password'));

        self::assertNull($subject->getUserId());
        self::assertSame(0, $this->countSessionsOfUser(100));
    }

    #[Test]
    public function missingRequestTokenAbortsActiveLogin(): void
    {
        // No request token provided in the security aspect: correct credentials are ignored.
        $subject = $this->createSubject();
        $subject->start($this->buildLoginRequest('testadmin', self::PASSWORD));

        self::assertNull($subject->getUserId());
        self::assertSame(0, $this->countSessionsOfUser(100));
    }

    #[Test]
    public function requestTokenWithForeignScopeAbortsActiveLogin(): void
    {
        $securityAspect = SecurityAspect::provideIn($this->get(Context::class));
        $securityAspect->setReceivedRequestToken(RequestToken::create('core/user-auth/fe'));
        $subject = $this->createSubject();
        $subject->start($this->buildLoginRequest('testadmin', self::PASSWORD));

        self::assertNull($subject->getUserId());
    }

    #[Test]
    public function authServiceReturning200StopsChainAndAuthenticates(): void
    {
        // The fixture service (priority 60) responds before the core service (priority 50)
        // and accepts the user although the submitted password is wrong: return codes
        // >= 200 stop the chain, the core password check never runs.
        $this->provideValidRequestToken();
        $this->registerFixtureAuthService('fixture_auth', 'authUserBE', 60);
        FixtureAuthenticationService::$authUserReturnCodes['fixture_auth'] = 200;
        $subject = $this->createSubject();
        $subject->start($this->buildLoginRequest('testadmin', 'wrong-password'));

        self::assertSame(100, $subject->getUserId());
        self::assertSame(['fixture_auth::authUser'], FixtureAuthenticationService::$calledMethods);
    }

    #[Test]
    public function authServiceReturning100DelegatesToNextServiceWhichAuthenticates(): void
    {
        // 100..199 means "not responsible": the core service decides based on the password.
        $this->provideValidRequestToken();
        $this->registerFixtureAuthService('fixture_auth', 'authUserBE', 60);
        FixtureAuthenticationService::$authUserReturnCodes['fixture_auth'] = 100;
        $subject = $this->createSubject();
        $subject->start($this->buildLoginRequest('testadmin', self::PASSWORD));

        self::assertSame(100, $subject->getUserId());
    }

    #[Test]
    public function authServiceReturning100DelegatesToNextServiceWhichRejects(): void
    {
        $this->provideValidRequestToken();
        $this->registerFixtureAuthService('fixture_auth', 'authUserBE', 60);
        FixtureAuthenticationService::$authUserReturnCodes['fixture_auth'] = 100;
        $subject = $this->createSubject();
        $subject->start($this->buildLoginRequest('testadmin', 'wrong-password'));

        self::assertNull($subject->getUserId());
    }

    #[Test]
    public function authServiceAcceptingWithCodeBelow100IsOverruledByLaterFailingService(): void
    {
        // Characterizes a chain quirk: a return code of 1..99 sets "authenticated"
        // but does NOT stop the chain. The core service still runs, fails the
        // password check and flips the result back to "not authenticated".
        $this->provideValidRequestToken();
        $this->registerFixtureAuthService('fixture_auth', 'authUserBE', 60);
        FixtureAuthenticationService::$authUserReturnCodes['fixture_auth'] = 50;
        $subject = $this->createSubject();
        $subject->start($this->buildLoginRequest('testadmin', 'wrong-password'));

        self::assertNull($subject->getUserId());
    }

    #[Test]
    public function authServiceReturningZeroAbortsChainDespiteValidCredentials(): void
    {
        // Return codes <= 0 abort the chain immediately, the core service never
        // gets a chance to accept the (correct) password.
        $this->provideValidRequestToken();
        $this->registerFixtureAuthService('fixture_auth', 'authUserBE', 60);
        FixtureAuthenticationService::$authUserReturnCodes['fixture_auth'] = 0;
        $subject = $this->createSubject();
        $subject->start($this->buildLoginRequest('testadmin', self::PASSWORD));

        self::assertNull($subject->getUserId());
        self::assertSame(['fixture_auth::authUser'], FixtureAuthenticationService::$calledMethods);
    }

    #[Test]
    public function getUserServiceCanProvideTheUserRecord(): void
    {
        // A getUser service may resolve the user by other means than the submitted
        // username (SSO pattern). The first service returning an array wins.
        $this->provideValidRequestToken();
        $this->registerFixtureAuthService('fixture_auth', 'getUserBE,authUserBE', 60);
        $userRow = $this->get(ConnectionPool::class)->getConnectionForTable('be_users')
            ->select(['*'], 'be_users', ['uid' => 101])->fetchAssociative();
        FixtureAuthenticationService::$getUserReturns['fixture_auth'] = $userRow;
        FixtureAuthenticationService::$authUserReturnCodes['fixture_auth'] = 200;
        $subject = $this->createSubject();
        $subject->start($this->buildLoginRequest('does-not-exist', 'irrelevant'));

        self::assertSame(101, $subject->getUserId());
        self::assertSame('serviceuser', $subject->getUserName());
    }

    #[Test]
    public function userSessionIsReusedOnSubsequentRequest(): void
    {
        $this->provideValidRequestToken();
        $subject = $this->createSubject();
        $subject->start($this->buildLoginRequest('testadmin', self::PASSWORD));
        $sessionJwt = $subject->getSession()->getJwt();

        $subsequentSubject = $this->createSubject();
        $subsequentSubject->start($this->buildSessionRequest($sessionJwt));

        self::assertSame(100, $subsequentSubject->getUserId());
        self::assertSame($subject->getSession()->getIdentifier(), $subsequentSubject->getSession()->getIdentifier());
    }

    #[Test]
    public function logoutDestroysUserSession(): void
    {
        $this->provideValidRequestToken();
        $subject = $this->createSubject();
        $subject->start($this->buildLoginRequest('testadmin', self::PASSWORD));
        $sessionJwt = $subject->getSession()->getJwt();
        self::assertSame(1, $this->countSessionsOfUser(100));

        $request = $this->buildSessionRequest($sessionJwt);
        $request = $request->withParsedBody(['login_status' => 'logout']);
        $logoutSubject = $this->createSubject();
        $logoutSubject->start($request);

        self::assertNull($logoutSubject->getUserId());
        self::assertSame(0, $this->countSessionsOfUser(100));
    }

    #[Test]
    public function mfaRequiredExceptionIsThrownForUserWithActiveProvider(): void
    {
        $this->expectException(MfaRequiredException::class);
        $this->provideValidRequestToken();
        $subject = $this->createSubject();
        $subject->start($this->buildLoginRequest('mfauser', self::PASSWORD));
    }

    #[Test]
    public function sessionOfMfaUserIsCreatedButNotMarkedAsMfaVerified(): void
    {
        $this->provideValidRequestToken();
        $subject = $this->createSubject();
        try {
            $subject->start($this->buildLoginRequest('mfauser', self::PASSWORD));
            self::fail('MfaRequiredException was not thrown');
        } catch (MfaRequiredException) {
            // The user session exists (needed to complete MFA), but the user must
            // not be treated as fully authenticated yet.
            self::assertSame(1, $this->countSessionsOfUser(102));
            self::assertFalse((bool)($subject->getSessionData('mfa') ?? false));
        }
    }

    private function createSubject(): BackendUserAuthentication
    {
        $subject = new BackendUserAuthentication();
        $subject->setLogger(new NullLogger());
        // The login path (writelog() -> LogEntryRepository -> isSystemMaintainer())
        // reads $GLOBALS['BE_USER'] from within the object, so the global must be
        // set before start() is called - as the backend middleware does.
        $GLOBALS['BE_USER'] = $subject;
        return $subject;
    }

    private function buildLoginRequest(string $username, string $password): ServerRequestInterface
    {
        $request = new ServerRequest('https://example.com/typo3/login', 'POST');
        $request = $request->withParsedBody([
            'username' => $username,
            'userident' => $password,
            'login_status' => 'login',
        ]);
        return $request->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request));
    }

    private function buildSessionRequest(string $sessionJwt): ServerRequestInterface
    {
        $request = new ServerRequest('https://example.com/typo3/main');
        $request = $request->withCookieParams([BackendUserAuthentication::getCookieName() => $sessionJwt]);
        return $request->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request));
    }

    private function provideValidRequestToken(): void
    {
        $securityAspect = SecurityAspect::provideIn($this->get(Context::class));
        $securityAspect->setReceivedRequestToken(RequestToken::create('core/user-auth/be'));
    }

    private function registerFixtureAuthService(string $serviceKey, string $subTypes, int $priority): void
    {
        ExtensionManagementUtility::addService('core', 'auth', $serviceKey, [
            'title' => 'Fixture authentication service',
            'description' => 'Configurable authentication service for tests',
            'subtype' => $subTypes,
            'available' => true,
            'priority' => $priority,
            'quality' => $priority,
            'os' => '',
            'exec' => '',
            'className' => FixtureAuthenticationService::class,
        ]);
    }

    private function countSessionsOfUser(int $userId): int
    {
        return (int)$this->get(ConnectionPool::class)->getConnectionForTable('be_sessions')
            ->count('*', 'be_sessions', ['ses_userid' => $userId]);
    }
}
