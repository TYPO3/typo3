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
use Psr\Log\NullLogger;
use TYPO3\CMS\Backend\Middleware\BackendUserAuthenticator;
use TYPO3\CMS\Backend\Routing\Route;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\SecurityAspect;
use TYPO3\CMS\Core\Crypto\PasswordHashing\PasswordHashFactory;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\RateLimiter\RateLimiterFactoryInterface;
use TYPO3\CMS\Core\Security\RequestToken;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Tests the backend authentication middleware: processing of a backend
 * login POST request and the route-level authentication requirement.
 */
final class BackendUserAuthenticatorTest extends FunctionalTestCase
{
    private const string PASSWORD = 'test-password-1234';

    private BackendUserAuthenticator $subject;
    private RequestHandlerInterface $requestHandler;
    private bool $handlerInvoked = false;

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
            $this->get(RateLimiterFactoryInterface::class),
            new NullLogger(),
            $this->get(UriBuilder::class),
        );
        $this->handlerInvoked = false;
        $test = $this;
        $this->requestHandler = new class ($test) implements RequestHandlerInterface {
            public function __construct(private readonly BackendUserAuthenticatorTest $test) {}
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                $this->test->markHandlerInvoked();
                return new HtmlResponse('handler-reached');
            }
        };
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['BE_USER'], $GLOBALS['LANG']);
        parent::tearDown();
    }

    public function markHandlerInvoked(): void
    {
        $this->handlerInvoked = true;
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
        // but no user has been authenticated.
        self::assertTrue($this->handlerInvoked);
        self::assertFalse($this->get(Context::class)->getAspect('backend.user')->isLoggedIn());
        self::assertNull($GLOBALS['BE_USER']->getUserId());
        self::assertSame('handler-reached', (string)$response->getBody());
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

    private function provideValidRequestToken(): void
    {
        $securityAspect = SecurityAspect::provideIn($this->get(Context::class));
        $securityAspect->setReceivedRequestToken(RequestToken::create('core/user-auth/be'));
    }
}
