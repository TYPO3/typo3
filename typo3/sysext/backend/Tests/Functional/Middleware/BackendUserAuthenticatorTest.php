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

namespace TYPO3\CMS\Backend\Tests\Functional\Middleware;

use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Component\DependencyInjection\ServiceLocator;
use TYPO3\CMS\Backend\Middleware\BackendUserAuthenticator;
use TYPO3\CMS\Backend\Routing\Route;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Security\MfaChallengeResponseFactory;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Authentication\Pipeline\AuthenticationResult;
use TYPO3\CMS\Core\Authentication\Pipeline\AuthenticatorInterface;
use TYPO3\CMS\Core\Authentication\Pipeline\AuthenticatorManager;
use TYPO3\CMS\Core\Authentication\Pipeline\Realm;
use TYPO3\CMS\Core\Authentication\Principal;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\SecurityAspect;
use TYPO3\CMS\Core\Crypto\PasswordHashing\PasswordHashFactory;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Security\PermissionSet\PrincipalRole;
use TYPO3\CMS\Core\Security\RequestToken;
use TYPO3\CMS\Core\Tests\Functional\Authentication\Fixtures\FixtureAuthenticationService;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Tests the backend authentication middleware: processing of a backend
 * login POST request through the authentication pipeline and the
 * route-level authentication requirement.
 */
final class BackendUserAuthenticatorTest extends FunctionalTestCase
{
    private const string PASSWORD = 'test-password-1234';

    private BackendUserAuthenticator $subject;
    private RequestHandlerInterface $requestHandler;
    private bool $handlerInvoked = false;
    private ?ServerRequestInterface $handledRequest = null;

    protected function setUp(): void
    {
        parent::setUp();
        $passwordHash = $this->get(PasswordHashFactory::class)
            ->getDefaultHashInstance('BE')
            ->getHashedPassword(self::PASSWORD);
        $this->get(ConnectionPool::class)->getConnectionForTable('be_users')->insert('be_users', [
            'uid' => 100,
            'username' => 'testadmin',
            'password' => $passwordHash,
            'admin' => 1,
        ]);
        $this->subject = new BackendUserAuthenticator(
            $this->get(Context::class),
            $this->get(LanguageServiceFactory::class),
            $this->get(UriBuilder::class),
            $this->get(AuthenticatorManager::class),
            $this->get(MfaChallengeResponseFactory::class),
        );
        $this->handlerInvoked = false;
        $this->handledRequest = null;
        $test = $this;
        $this->requestHandler = new class ($test) implements RequestHandlerInterface {
            public function __construct(private readonly BackendUserAuthenticatorTest $test) {}
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                $this->test->markHandlerInvoked($request);
                return new HtmlResponse('handler-reached');
            }
        };
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['BE_USER'], $GLOBALS['LANG']);
        parent::tearDown();
    }

    public function markHandlerInvoked(ServerRequestInterface $request): void
    {
        $this->handlerInvoked = true;
        $this->handledRequest = $request;
    }

    #[Test]
    public function loginPostWithValidRequestTokenAuthenticatesUserAndAppendsSessionCookie(): void
    {
        $this->provideValidRequestToken();
        $request = $this->buildRequest(new Route('/login', ['access' => 'anonymous', '_identifier' => 'login']), 'POST')
            ->withParsedBody([
                'username' => 'testadmin',
                'userident' => self::PASSWORD,
                'login_status' => 'login',
            ]);

        $response = $this->subject->process($request, $this->requestHandler);

        self::assertTrue($this->handlerInvoked);
        self::assertTrue($this->get(Context::class)->getAspect('backend.user')->isLoggedIn());
        self::assertSame(100, $GLOBALS['BE_USER']->getUserId());
        self::assertStringContainsString('be_typo_user', $response->getHeaderLine('Set-Cookie'));
    }

    #[Test]
    public function legacyUserOfTheOutcomeIsUsedInsteadOfTheGlobalUser(): void
    {
        $authenticatorManager = $this->get(AuthenticatorManager::class);
        $authenticator = new class ($authenticatorManager) implements AuthenticatorInterface {
            public function __construct(private readonly AuthenticatorManager $authenticatorManager) {}
            public function authenticate(ServerRequestInterface $request, Realm $realm): AuthenticationResult
            {
                $result = $this->authenticatorManager->authenticate($request, $realm);
                $GLOBALS['BE_USER'] = new BackendUserAuthentication();
                return $result;
            }
        };
        $subject = new BackendUserAuthenticator(
            $this->get(Context::class),
            $this->get(LanguageServiceFactory::class),
            $this->get(UriBuilder::class),
            new AuthenticatorManager(new ServiceLocator(['legacy' => static fn() => $authenticator]), ['backend' => ['legacy']]),
            $this->get(MfaChallengeResponseFactory::class),
        );
        $this->provideValidRequestToken();
        $request = $this->buildRequest(new Route('/login', ['access' => 'anonymous', '_identifier' => 'login']), 'POST')
            ->withParsedBody([
                'username' => 'testadmin',
                'userident' => self::PASSWORD,
                'login_status' => 'login',
            ]);

        $subject->process($request, $this->requestHandler);

        self::assertTrue($this->get(Context::class)->getAspect('backend.user')->isLoggedIn());
        self::assertSame(100, $GLOBALS['BE_USER']->getUserId());
    }

    #[Test]
    public function loginPostProvidesPrincipalAsRequestAttribute(): void
    {
        $this->provideValidRequestToken();
        $request = $this->buildRequest(new Route('/login', ['access' => 'anonymous', '_identifier' => 'login']), 'POST')
            ->withParsedBody([
                'username' => 'testadmin',
                'userident' => self::PASSWORD,
                'login_status' => 'login',
            ]);

        $this->subject->process($request, $this->requestHandler);

        $principal = $this->handledRequest?->getAttribute('backend.principal');
        self::assertInstanceOf(Principal::class, $principal);
        self::assertSame(100, $principal->context->id);
        self::assertSame('backend', $principal->realmIdentifier);
        self::assertSame('testadmin', $principal->username);
    }

    #[Test]
    public function principalOfAdministratorCarriesAdminRole(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['systemMaintainers'] = [];

        $this->subject->process($this->buildLoginRequest('testadmin'), $this->requestHandler);

        $principal = $this->handledRequest?->getAttribute('backend.principal');
        self::assertInstanceOf(Principal::class, $principal);
        self::assertSame(PrincipalRole::ADMIN, $principal->context->role);
    }

    #[Test]
    public function principalOfSystemMaintainerCarriesMaintainerRole(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['systemMaintainers'] = [100];

        $this->subject->process($this->buildLoginRequest('testadmin'), $this->requestHandler);

        $principal = $this->handledRequest?->getAttribute('backend.principal');
        self::assertInstanceOf(Principal::class, $principal);
        self::assertSame(PrincipalRole::MAINTAINER, $principal->context->role);
    }

    #[Test]
    public function principalOfEditorCarriesUserRoleAndGroups(): void
    {
        $connectionPool = $this->get(ConnectionPool::class);
        $connectionPool->getConnectionForTable('be_groups')->insert('be_groups', [
            'uid' => 7,
            'title' => 'Editors',
        ]);
        $connectionPool->getConnectionForTable('be_users')->insert('be_users', [
            'uid' => 101,
            'username' => 'testeditor',
            'password' => $this->get(PasswordHashFactory::class)
                ->getDefaultHashInstance('BE')
                ->getHashedPassword(self::PASSWORD),
            'admin' => 0,
            'usergroup' => '7',
        ]);

        $this->subject->process($this->buildLoginRequest('testeditor'), $this->requestHandler);

        $principal = $this->handledRequest?->getAttribute('backend.principal');
        self::assertInstanceOf(Principal::class, $principal);
        self::assertSame(101, $principal->context->id);
        self::assertSame(PrincipalRole::USER, $principal->context->role);
        self::assertSame([7], $principal->context->groupIds);
        self::assertSame(['Editors'], $principal->groupNames);
    }

    #[Test]
    public function loginPostWithoutRequestTokenDoesNotAuthenticateUser(): void
    {
        $request = $this->buildRequest(new Route('/login', ['access' => 'anonymous', '_identifier' => 'login']), 'POST')
            ->withParsedBody([
                'username' => 'testadmin',
                'userident' => self::PASSWORD,
                'login_status' => 'login',
            ]);

        $response = $this->subject->process($request, $this->requestHandler);

        // The route is anonymous, so the request still reaches the handler,
        // but no user has been authenticated and no principal is provided.
        self::assertTrue($this->handlerInvoked);
        self::assertFalse($this->get(Context::class)->getAspect('backend.user')->isLoggedIn());
        self::assertNull($GLOBALS['BE_USER']->getUserId());
        self::assertNull($this->handledRequest?->getAttribute('backend.principal'));
        self::assertSame('handler-reached', (string)$response->getBody());
    }

    #[Test]
    public function protectedRouteChallengesMfaPendingSessionWithRedirectToMfaRoute(): void
    {
        $passwordHash = $this->get(PasswordHashFactory::class)
            ->getDefaultHashInstance('BE')
            ->getHashedPassword(self::PASSWORD);
        $this->get(ConnectionPool::class)->getConnectionForTable('be_users')->insert('be_users', [
            'uid' => 102,
            'username' => 'mfauser',
            'password' => $passwordHash,
            'admin' => 1,
            'mfa' => '{"totp":{"active":true,"secret":"KRMVATZTJFZUC53FONXW2ZJB"}}',
        ]);
        // Login on the public login route: the MFA requirement is left to the
        // login controller there, but a partial session cookie is issued.
        $this->provideValidRequestToken();
        $loginRequest = $this->buildRequest(new Route('/login', ['access' => 'anonymous', '_identifier' => 'login']), 'POST')
            ->withParsedBody([
                'username' => 'mfauser',
                'userident' => self::PASSWORD,
                'login_status' => 'login',
            ]);
        $loginResponse = $this->subject->process($loginRequest, $this->requestHandler);
        self::assertSame(1, preg_match('/be_typo_user=([^;]+);/', $loginResponse->getHeaderLine('Set-Cookie'), $matches));

        // Requesting a protected route with the partial session is answered
        // with a challenge: a redirect to the MFA route.
        $this->handlerInvoked = false;
        $request = $this->buildRequest(new Route('/module/web/layout', ['_identifier' => 'web_layout']))
            ->withCookieParams(['be_typo_user' => rawurldecode($matches[1])]);

        $response = $this->subject->process($request, $this->requestHandler);

        self::assertFalse($this->handlerInvoked);
        self::assertSame(302, $response->getStatusCode());
        self::assertStringContainsString('/auth/mfa', $response->getHeaderLine('location'));
        self::assertStringContainsString('identifier=totp', $response->getHeaderLine('location'));
    }

    #[Test]
    public function loginOfUserWithoutRequiredMfaRedirectsToMfaSetupWithSessionCookie(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['BE']['requireMfa'] = 1;

        $response = $this->subject->process($this->buildLoginRequest('testadmin'), $this->requestHandler);

        self::assertFalse($this->handlerInvoked);
        self::assertSame(302, $response->getStatusCode());
        self::assertStringContainsString('/setup/mfa', $response->getHeaderLine('location'));
        self::assertStringContainsString('be_typo_user', $response->getHeaderLine('Set-Cookie'));
        self::assertSame('no-cache, no-store', $response->getHeaderLine('Cache-Control'));
    }

    #[Test]
    public function mfaPendingSessionProvidesNoPrincipalOnMfaRoute(): void
    {
        $passwordHash = $this->get(PasswordHashFactory::class)
            ->getDefaultHashInstance('BE')
            ->getHashedPassword(self::PASSWORD);
        $this->get(ConnectionPool::class)->getConnectionForTable('be_users')->insert('be_users', [
            'uid' => 102,
            'username' => 'mfauser',
            'password' => $passwordHash,
            'admin' => 1,
            'mfa' => '{"totp":{"active":true,"secret":"KRMVATZTJFZUC53FONXW2ZJB"}}',
        ]);
        $loginResponse = $this->subject->process($this->buildLoginRequest('mfauser'), $this->requestHandler);
        // The password has been verified, but the second factor is pending
        self::assertNull($this->handledRequest?->getAttribute('backend.principal'));
        self::assertSame(1, preg_match('/be_typo_user=([^;]+);/', $loginResponse->getHeaderLine('Set-Cookie'), $matches));

        $this->handlerInvoked = false;
        $this->handledRequest = null;
        $request = $this->buildRequest(new Route('/auth/mfa', ['_identifier' => 'auth_mfa']))
            ->withCookieParams(['be_typo_user' => rawurldecode($matches[1])]);

        $response = $this->subject->process($request, $this->requestHandler);

        // The MFA route is processed with the legacy user object as before,
        // but without a principal.
        self::assertTrue($this->handlerInvoked);
        self::assertSame('handler-reached', (string)$response->getBody());
        self::assertSame(102, $GLOBALS['BE_USER']->getUserId());
        self::assertNull($this->getHandledPrincipal());
    }

    #[Test]
    public function loginPostWithWrongPasswordDoesNotAuthenticateUser(): void
    {
        $this->provideValidRequestToken();
        $request = $this->buildRequest(new Route('/login', ['access' => 'anonymous', '_identifier' => 'login']), 'POST')
            ->withParsedBody([
                'username' => 'testadmin',
                'userident' => 'wrong-password',
                'login_status' => 'login',
            ]);

        $response = $this->subject->process($request, $this->requestHandler);

        self::assertTrue($this->handlerInvoked);
        self::assertFalse($this->get(Context::class)->getAspect('backend.user')->isLoggedIn());
        self::assertNull($GLOBALS['BE_USER']->getUserId());
        self::assertNull($this->handledRequest?->getAttribute('backend.principal'));
        self::assertSame('handler-reached', (string)$response->getBody());
    }

    #[Test]
    public function loginPostIsProcessedByLegacyServiceChainWhenThirdPartyServiceIsRegistered(): void
    {
        // Registering any additional authentication service routes the whole
        // authentication through the legacy service chain authenticator.
        ExtensionManagementUtility::addService('core', 'auth', 'middleware_test_service', [
            'title' => 'Fixture authentication service',
            'description' => 'Configurable authentication service for tests',
            'subtype' => 'getUserBE,authUserBE',
            'available' => true,
            'priority' => 60,
            'quality' => 60,
            'os' => '',
            'exec' => '',
            'className' => FixtureAuthenticationService::class,
        ]);
        FixtureAuthenticationService::resetState();
        $this->provideValidRequestToken();
        $request = $this->buildRequest(new Route('/login', ['access' => 'anonymous', '_identifier' => 'login']), 'POST')
            ->withParsedBody([
                'username' => 'testadmin',
                'userident' => self::PASSWORD,
                'login_status' => 'login',
            ]);

        $response = $this->subject->process($request, $this->requestHandler);

        // The fixture service is "not responsible", the core authentication
        // service of the chain performs the password verification.
        self::assertNotSame([], FixtureAuthenticationService::$calledMethods);
        self::assertTrue($this->get(Context::class)->getAspect('backend.user')->isLoggedIn());
        self::assertSame(100, $GLOBALS['BE_USER']->getUserId());
        $principal = $this->handledRequest?->getAttribute('backend.principal');
        self::assertInstanceOf(Principal::class, $principal);
        self::assertSame(100, $principal->context->id);
        self::assertStringContainsString('be_typo_user', $response->getHeaderLine('Set-Cookie'));
    }

    #[Test]
    public function protectedRouteRedirectsToLoginForAnonymousRequest(): void
    {
        $request = $this->buildRequest(new Route('/module/web/layout', ['_identifier' => 'web_layout']));

        $response = $this->subject->process($request, $this->requestHandler);

        self::assertFalse($this->handlerInvoked);
        self::assertSame(302, $response->getStatusCode());
        self::assertStringContainsString('/typo3/login', $response->getHeaderLine('location'));
    }

    #[Test]
    public function protectedAjaxRouteReturns401ForAnonymousRequest(): void
    {
        $request = $this->buildRequest(new Route('/ajax/some/endpoint', ['ajax' => true, '_identifier' => 'some_endpoint']));

        $response = $this->subject->process($request, $this->requestHandler);

        self::assertFalse($this->handlerInvoked);
        self::assertSame(401, $response->getStatusCode());
    }

    #[Test]
    public function anonymousRouteIsProcessedWithoutUser(): void
    {
        // Any route - also one provided by a third-party extension, e.g. an SSO
        // callback endpoint - can opt out of authentication via the route access.
        $request = $this->buildRequest(new Route('/sso/callback', ['access' => 'anonymous', '_identifier' => 'sso_callback']));

        $response = $this->subject->process($request, $this->requestHandler);

        self::assertTrue($this->handlerInvoked);
        self::assertSame('handler-reached', (string)$response->getBody());
        self::assertFalse($this->get(Context::class)->getAspect('backend.user')->isLoggedIn());
    }

    private function buildRequest(Route $route, string $method = 'GET'): ServerRequestInterface
    {
        $request = new ServerRequest('https://example.com/typo3' . $route->getPath(), $method);
        $request = $request->withAttribute('route', $route);
        return $request->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request));
    }

    private function getHandledPrincipal(): mixed
    {
        return $this->handledRequest?->getAttribute('backend.principal');
    }

    private function buildLoginRequest(string $username): ServerRequestInterface
    {
        $this->provideValidRequestToken();
        return $this->buildRequest(new Route('/login', ['access' => 'anonymous', '_identifier' => 'login']), 'POST')
            ->withParsedBody([
                'username' => $username,
                'userident' => self::PASSWORD,
                'login_status' => 'login',
            ]);
    }

    private function provideValidRequestToken(): void
    {
        $securityAspect = SecurityAspect::provideIn($this->get(Context::class));
        $securityAspect->setReceivedRequestToken(RequestToken::create('core/user-auth/be'));
    }
}
