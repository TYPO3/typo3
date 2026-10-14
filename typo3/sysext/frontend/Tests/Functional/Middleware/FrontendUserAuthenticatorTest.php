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

namespace TYPO3\CMS\Frontend\Tests\Functional\Middleware;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use TYPO3\CMS\Core\Authentication\Event\BeforeRequestTokenProcessedEvent;
use TYPO3\CMS\Core\Authentication\Event\LoginAttemptFailedEvent;
use TYPO3\CMS\Core\Authentication\Mfa\MfaRequiredException;
use TYPO3\CMS\Core\Authentication\Pipeline\AuthenticatorManager;
use TYPO3\CMS\Core\Authentication\Principal;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\SecurityAspect;
use TYPO3\CMS\Core\Crypto\PasswordHashing\PasswordHashFactory;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\EventDispatcher\ListenerProvider;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\RateLimiter\RequestRateLimitedException;
use TYPO3\CMS\Core\Security\Authorization\PrincipalToken;
use TYPO3\CMS\Core\Security\PermissionSet\PrincipalRole;
use TYPO3\CMS\Core\Security\RequestToken;
use TYPO3\CMS\Core\Session\UserSessionManager;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;
use TYPO3\CMS\Frontend\Middleware\FrontendUserAuthenticator;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Tests the frontend authentication middleware: processing of a frontend
 * login request ("logintype=login") through the authentication pipeline.
 */
final class FrontendUserAuthenticatorTest extends FunctionalTestCase
{
    private const string PASSWORD = 'test-password-1234';

    private FrontendUserAuthenticator $subject;
    private RequestHandlerInterface $requestHandler;
    private bool $handlerInvoked = false;
    private ?ServerRequestInterface $handledRequest = null;
    private ?TokenInterface $tokenDuringHandling = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withDatabaseSnapshot(function (): void {
            $passwordHash = $this->get(PasswordHashFactory::class)
                ->getDefaultHashInstance('FE')
                ->getHashedPassword(self::PASSWORD);
            $connectionPool = $this->get(ConnectionPool::class);
            $connectionPool->getConnectionForTable('fe_groups')->insert('fe_groups', [
                'uid' => 5,
                'pid' => 0,
                'title' => 'Members',
            ]);
            $connectionPool->getConnectionForTable('fe_users')->insert('fe_users', [
                'uid' => 10,
                'pid' => 0,
                'username' => 'feuser',
                'password' => $passwordHash,
                'usergroup' => '5',
            ]);
        });
        $this->subject = new FrontendUserAuthenticator(
            $this->get(Context::class),
            $this->get(AuthenticatorManager::class),
            $this->get(TokenStorageInterface::class),
        );
        $this->handlerInvoked = false;
        $this->handledRequest = null;
        $test = $this;
        $this->requestHandler = new class ($test) implements RequestHandlerInterface {
            public function __construct(private readonly FrontendUserAuthenticatorTest $test) {}

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                $this->test->markHandlerInvoked($request);
                return new HtmlResponse('handler-reached');
            }
        };
    }

    public function markHandlerInvoked(ServerRequestInterface $request): void
    {
        $this->handlerInvoked = true;
        $this->handledRequest = $request;
        $this->tokenDuringHandling = $this->get(TokenStorageInterface::class)->getToken();
    }

    #[Test]
    public function loginRequestWithValidRequestTokenAuthenticatesUserAndAppendsSessionCookie(): void
    {
        $response = $this->subject->process($this->buildLoginRequest(self::PASSWORD), $this->requestHandler);

        self::assertTrue($this->handlerInvoked);
        self::assertTrue($this->get(Context::class)->getAspect('frontend.user')->isLoggedIn());
        $principal = $this->handledRequest?->getAttribute('frontend.principal');
        self::assertInstanceOf(Principal::class, $principal);
        self::assertSame(10, $principal->context->id);
        self::assertSame('frontend', $principal->realmIdentifier);
        self::assertSame('feuser', $principal->username);
        self::assertSame(PrincipalRole::USER, $principal->context->role);
        self::assertContains(5, $principal->context->groupIds);
        self::assertSame(['Members'], $principal->groupNames);
        self::assertStringContainsString('fe_typo_user', $response->getHeaderLine('Set-Cookie'));
    }

    #[Test]
    public function principalIsTheTokenOfTheAuthorizationCheckerWhileTheRequestIsHandled(): void
    {
        $this->subject->process($this->buildLoginRequest(self::PASSWORD), $this->requestHandler);

        self::assertInstanceOf(PrincipalToken::class, $this->tokenDuringHandling);
        self::assertSame($this->handledRequest?->getAttribute('frontend.principal'), $this->tokenDuringHandling->getPrincipal());
        self::assertNull($this->get(TokenStorageInterface::class)->getToken());
    }

    #[Test]
    public function anonymousRequestHasNoToken(): void
    {
        $this->subject->process($this->buildRequest(), $this->requestHandler);

        self::assertTrue($this->handlerInvoked);
        self::assertNull($this->tokenDuringHandling);
    }

    #[Test]
    public function loginRequestWithWrongPasswordDoesNotAuthenticateUser(): void
    {
        $this->subject->process($this->buildLoginRequest('wrong-password'), $this->requestHandler);

        self::assertTrue($this->handlerInvoked);
        self::assertFalse($this->get(Context::class)->getAspect('frontend.user')->isLoggedIn());
        self::assertNull($this->handledRequest?->getAttribute('frontend.principal'));
    }

    #[Test]
    public function rateLimitedLoginRequestIsRefusedWithFrontendExceptionCode(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['FE']['loginRateLimit'] = 1;

        $this->expectException(RequestRateLimitedException::class);
        $this->expectExceptionCode(1616175847);
        // The second attempt at the latest exceeds the limit
        $this->subject->process($this->buildLoginRequest('wrong-password'), $this->requestHandler);
        $this->subject->process($this->buildLoginRequest('wrong-password'), $this->requestHandler);
    }

    #[Test]
    public function loginRequestWithoutRequestTokenDoesNotAuthenticateUser(): void
    {
        $request = $this->buildLoginRequest(self::PASSWORD, provideRequestToken: false);

        $this->subject->process($request, $this->requestHandler);

        self::assertTrue($this->handlerInvoked);
        self::assertFalse($this->get(Context::class)->getAspect('frontend.user')->isLoggedIn());
        self::assertNull($this->handledRequest?->getAttribute('frontend.principal'));
    }

    #[Test]
    public function loginRequestWithoutRequestTokenProvidesSessionToEventListeners(): void
    {
        $sessionIsAnonymous = null;
        /** @var Container $container */
        $container = $this->get('service_container');
        $container->set(
            'login-attempt-failed-listener',
            static function (LoginAttemptFailedEvent $event) use (&$sessionIsAnonymous): void {
                $sessionIsAnonymous = $event->getUser()->getSession()->isAnonymous();
            }
        );
        $container->get(ListenerProvider::class)->addListener(LoginAttemptFailedEvent::class, 'login-attempt-failed-listener');

        $this->subject->process($this->buildLoginRequest(self::PASSWORD, provideRequestToken: false), $this->requestHandler);

        self::assertTrue($sessionIsAnonymous);
    }

    #[Test]
    public function loginRequestTrimsSubmittedCredentials(): void
    {
        $securityAspect = SecurityAspect::provideIn($this->get(Context::class));
        $securityAspect->setReceivedRequestToken(RequestToken::create('core/user-auth/fe'));
        $request = $this->buildRequest([
            'logintype' => 'login',
            'user' => ' feuser ',
            'pass' => self::PASSWORD . " \n",
        ], 'POST');

        $this->subject->process($request, $this->requestHandler);

        self::assertTrue($this->get(Context::class)->getAspect('frontend.user')->isLoggedIn());
        self::assertSame(10, $this->handledRequest?->getAttribute('frontend.principal')?->context->id);
    }

    #[Test]
    public function loginRequestWithExpiredSessionCookieAuthenticatesUser(): void
    {
        $loginResponse = $this->subject->process($this->buildLoginRequest(self::PASSWORD), $this->requestHandler);
        self::assertSame(1, preg_match('/fe_typo_user=([^;]+);/', $loginResponse->getHeaderLine('Set-Cookie'), $matches));
        $this->get(ConnectionPool::class)->getConnectionForTable('fe_sessions')
            ->update('fe_sessions', ['ses_tstamp' => 1], ['ses_userid' => 10]);

        $request = $this->buildLoginRequest(self::PASSWORD)->withCookieParams(['fe_typo_user' => rawurldecode($matches[1])]);
        $this->subject->process($request, $this->requestHandler);

        self::assertTrue($this->get(Context::class)->getAspect('frontend.user')->isLoggedIn());
        self::assertSame(10, $this->handledRequest?->getAttribute('frontend.principal')?->context->id);
    }

    #[Test]
    public function sessionIsContinuedOnSubsequentRequest(): void
    {
        $loginResponse = $this->subject->process($this->buildLoginRequest(self::PASSWORD), $this->requestHandler);
        self::assertSame(1, preg_match('/fe_typo_user=([^;]+);/', $loginResponse->getHeaderLine('Set-Cookie'), $matches));

        $request = $this->buildRequest()->withCookieParams(['fe_typo_user' => rawurldecode($matches[1])]);
        $this->subject->process($request, $this->requestHandler);

        self::assertTrue($this->get(Context::class)->getAspect('frontend.user')->isLoggedIn());
        $principal = $this->handledRequest?->getAttribute('frontend.principal');
        self::assertInstanceOf(Principal::class, $principal);
        self::assertSame(10, $principal->context->id);
    }

    #[Test]
    public function logoutRequestDestroysUserSession(): void
    {
        $loginResponse = $this->subject->process($this->buildLoginRequest(self::PASSWORD), $this->requestHandler);
        self::assertSame(1, preg_match('/fe_typo_user=([^;]+);/', $loginResponse->getHeaderLine('Set-Cookie'), $matches));
        $sessionCookie = rawurldecode($matches[1]);

        $logoutRequest = $this->buildRequest(['logintype' => 'logout'])
            ->withCookieParams(['fe_typo_user' => $sessionCookie]);
        $this->subject->process($logoutRequest, $this->requestHandler);
        self::assertFalse($this->get(Context::class)->getAspect('frontend.user')->isLoggedIn());

        // The previous session cookie is worthless now
        $request = $this->buildRequest()->withCookieParams(['fe_typo_user' => $sessionCookie]);
        $this->subject->process($request, $this->requestHandler);
        self::assertFalse($this->get(Context::class)->getAspect('frontend.user')->isLoggedIn());
        self::assertNull($this->handledRequest?->getAttribute('frontend.principal'));
    }

    #[Test]
    public function logoutLinkInTheQueryDestroysUserSession(): void
    {
        $loginResponse = $this->subject->process($this->buildLoginRequest(self::PASSWORD), $this->requestHandler);
        self::assertSame(1, preg_match('/fe_typo_user=([^;]+);/', $loginResponse->getHeaderLine('Set-Cookie'), $matches));
        $sessionCookie = rawurldecode($matches[1]);

        $logoutRequest = $this->buildRequest()
            ->withQueryParams(['logintype' => 'logout'])
            ->withCookieParams(['fe_typo_user' => $sessionCookie]);
        $this->subject->process($logoutRequest, $this->requestHandler);
        self::assertFalse($this->get(Context::class)->getAspect('frontend.user')->isLoggedIn());

        $request = $this->buildRequest()->withCookieParams(['fe_typo_user' => $sessionCookie]);
        $this->subject->process($request, $this->requestHandler);
        self::assertFalse($this->get(Context::class)->getAspect('frontend.user')->isLoggedIn());
    }

    public static function storagePageRestrictionDataProvider(): \Generator
    {
        yield 'storage page of the user' => ['0', true];
        yield 'other storage page' => ['7', false];
    }

    #[Test]
    #[DataProvider('storagePageRestrictionDataProvider')]
    public function loginRequestRespectsStoragePageRestrictionOfTheRequestToken(string $storagePageIds, bool $expectedLoggedIn): void
    {
        $this->registerListener(
            BeforeRequestTokenProcessedEvent::class,
            static function (BeforeRequestTokenProcessedEvent $event) use ($storagePageIds): void {
                $event->getUser()->checkPid_value = $storagePageIds;
            }
        );

        $this->subject->process($this->buildLoginRequest(self::PASSWORD), $this->requestHandler);

        self::assertSame($expectedLoggedIn, $this->get(Context::class)->getAspect('frontend.user')->isLoggedIn());
    }

    #[Test]
    public function storagePageRestrictionIsResetForRequestsWithoutLogin(): void
    {
        $this->subject->process($this->buildRequest(), $this->requestHandler);

        self::assertNull($this->getHandledFrontendUser()->checkPid_value);
    }

    #[Test]
    public function permanentLoginIsEvaluatedForRequestsWithoutLogin(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['FE']['permalogin'] = 2;

        $this->subject->process($this->buildRequest(), $this->requestHandler);

        self::assertTrue($this->getHandledFrontendUser()->isPermanentLogin());
    }

    #[Test]
    public function loginOfUserWithActiveMfaProviderRequiresMfa(): void
    {
        $this->get(ConnectionPool::class)->getConnectionForTable('fe_users')->update(
            'fe_users',
            ['mfa' => '{"totp":{"active":true,"secret":"KRMVATZTJFZUC53FONXW2ZJB"}}'],
            ['uid' => 10]
        );

        $this->expectException(MfaRequiredException::class);
        $this->subject->process($this->buildLoginRequest(self::PASSWORD), $this->requestHandler);
    }

    #[Test]
    public function sessionOfUserWithActiveMfaProviderRequiresMfa(): void
    {
        $this->get(ConnectionPool::class)->getConnectionForTable('fe_users')->update(
            'fe_users',
            ['mfa' => '{"totp":{"active":true,"secret":"KRMVATZTJFZUC53FONXW2ZJB"}}'],
            ['uid' => 10]
        );
        $sessionManager = UserSessionManager::create('FE');
        $session = $sessionManager->elevateToFixatedUserSession($sessionManager->createAnonymousSession(), 10);

        $this->expectException(MfaRequiredException::class);
        $this->subject->process($this->buildRequest()->withCookieParams(['fe_typo_user' => $session->getJwt()]), $this->requestHandler);
    }

    #[Test]
    public function onlineTimestampIsRefreshedOnSessionRestore(): void
    {
        $loginResponse = $this->subject->process($this->buildLoginRequest(self::PASSWORD), $this->requestHandler);
        self::assertSame(1, preg_match('/fe_typo_user=([^;]+);/', $loginResponse->getHeaderLine('Set-Cookie'), $matches));
        $connectionPool = $this->get(ConnectionPool::class);
        $connectionPool->getConnectionForTable('fe_users')->update('fe_users', ['is_online' => 0], ['uid' => 10]);
        $connectionPool->getConnectionForTable('fe_sessions')
            ->update('fe_sessions', ['ses_tstamp' => $GLOBALS['EXEC_TIME'] - 120], ['ses_userid' => 10]);

        $this->subject->process($this->buildRequest()->withCookieParams(['fe_typo_user' => rawurldecode($matches[1])]), $this->requestHandler);

        $isOnline = $connectionPool->getConnectionForTable('fe_users')
            ->select(['is_online'], 'fe_users', ['uid' => 10])->fetchOne();
        self::assertSame($GLOBALS['EXEC_TIME'], (int)$isOnline);
    }

    public static function malformedLoginParametersDataProvider(): \Generator
    {
        yield 'login type as array in the body' => [['logintype' => ['login'], 'user' => 'feuser', 'pass' => self::PASSWORD], []];
        yield 'login type as array in the query' => [[], ['logintype' => ['x']]];
        yield 'username as array' => [['logintype' => 'login', 'user' => ['feuser'], 'pass' => self::PASSWORD], []];
        yield 'password as array' => [['logintype' => 'login', 'user' => 'feuser', 'pass' => [self::PASSWORD]], []];
    }

    #[Test]
    #[DataProvider('malformedLoginParametersDataProvider')]
    public function malformedLoginParametersDoNotAuthenticateUser(array $parsedBody, array $queryParams): void
    {
        $securityAspect = SecurityAspect::provideIn($this->get(Context::class));
        $securityAspect->setReceivedRequestToken(RequestToken::create('core/user-auth/fe'));
        $request = $this->buildRequest($parsedBody, 'POST')->withQueryParams($queryParams);

        $response = $this->subject->process($request, $this->requestHandler);

        self::assertTrue($this->handlerInvoked);
        self::assertSame('handler-reached', (string)$response->getBody());
        self::assertFalse($this->get(Context::class)->getAspect('frontend.user')->isLoggedIn());
    }

    #[Test]
    public function loginTypeInTheQueryOfAGetRequestDoesNotAuthenticateUser(): void
    {
        $securityAspect = SecurityAspect::provideIn($this->get(Context::class));
        $securityAspect->setReceivedRequestToken(RequestToken::create('core/user-auth/fe'));
        $request = $this->buildRequest(['user' => 'feuser', 'pass' => self::PASSWORD])
            ->withQueryParams(['logintype' => 'login']);

        $this->subject->process($request, $this->requestHandler);

        self::assertTrue($this->handlerInvoked);
        self::assertFalse($this->get(Context::class)->getAspect('frontend.user')->isLoggedIn());
    }

    private function getHandledFrontendUser(): FrontendUserAuthentication
    {
        $frontendUser = $this->handledRequest?->getAttribute('frontend.user');
        self::assertInstanceOf(FrontendUserAuthentication::class, $frontendUser);
        return $frontendUser;
    }

    private function registerListener(string $eventClass, \Closure $listener): void
    {
        /** @var Container $container */
        $container = $this->get('service_container');
        $serviceId = 'test-listener-' . md5($eventClass);
        $container->set($serviceId, $listener);
        $container->get(ListenerProvider::class)->addListener($eventClass, $serviceId);
    }

    private function buildLoginRequest(string $password, bool $provideRequestToken = true): ServerRequestInterface
    {
        if ($provideRequestToken) {
            $securityAspect = SecurityAspect::provideIn($this->get(Context::class));
            $securityAspect->setReceivedRequestToken(RequestToken::create('core/user-auth/fe'));
        }
        return $this->buildRequest([
            'logintype' => 'login',
            'user' => 'feuser',
            'pass' => $password,
        ], 'POST');
    }

    private function buildRequest(array $parsedBody = [], string $method = 'GET'): ServerRequestInterface
    {
        $request = new ServerRequest('https://example.com/', $method);
        if ($parsedBody !== []) {
            $request = $request->withParsedBody($parsedBody);
        }
        return $request->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request));
    }
}
