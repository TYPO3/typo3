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

use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Authentication\Pipeline\AuthenticatorManager;
use TYPO3\CMS\Core\Authentication\Principal;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\SecurityAspect;
use TYPO3\CMS\Core\Crypto\PasswordHashing\PasswordHashFactory;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\RateLimiter\RequestRateLimitedException;
use TYPO3\CMS\Core\Security\PermissionSet\PrincipalRole;
use TYPO3\CMS\Core\Security\RequestToken;
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

    protected function setUp(): void
    {
        parent::setUp();
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
        $this->subject = new FrontendUserAuthenticator(
            $this->get(Context::class),
            $this->get(AuthenticatorManager::class),
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
