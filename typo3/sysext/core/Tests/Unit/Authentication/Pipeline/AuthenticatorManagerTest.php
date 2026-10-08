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

namespace TYPO3\CMS\Core\Tests\Unit\Authentication\Pipeline;

use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\DependencyInjection\ServiceLocator;
use TYPO3\CMS\Core\Authentication\Pipeline\Authenticated;
use TYPO3\CMS\Core\Authentication\Pipeline\AuthenticationResult;
use TYPO3\CMS\Core\Authentication\Pipeline\AuthenticatorInterface;
use TYPO3\CMS\Core\Authentication\Pipeline\AuthenticatorManager;
use TYPO3\CMS\Core\Authentication\Pipeline\Challenge;
use TYPO3\CMS\Core\Authentication\Pipeline\Failure;
use TYPO3\CMS\Core\Authentication\Pipeline\NotApplicable;
use TYPO3\CMS\Core\Authentication\Pipeline\Realm;
use TYPO3\CMS\Core\Authentication\Principal;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Security\PermissionSet\PrincipalContext;
use TYPO3\CMS\Core\Security\PermissionSet\PrincipalRole;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class AuthenticatorManagerTest extends UnitTestCase
{
    #[Test]
    public function returnsNotApplicableWithoutAuthenticators(): void
    {
        $subject = self::createManager([]);
        $result = $subject->authenticate(new ServerRequest('https://example.com/'), Realm::backend());
        self::assertInstanceOf(NotApplicable::class, $result);
    }

    #[Test]
    public function firstConclusiveResultWins(): void
    {
        $principal = self::createPrincipal('backend');
        $subject = self::createManager(['backend' => [
            self::createAuthenticator(new NotApplicable()),
            self::createAuthenticator(new Authenticated($principal)),
            self::createAuthenticator(new Failure('must-not-be-reached')),
        ]]);
        $result = $subject->authenticate(new ServerRequest('https://example.com/'), Realm::backend());
        self::assertInstanceOf(Authenticated::class, $result);
        self::assertSame($principal, $result->principal);
    }

    #[Test]
    public function failureStopsTheChain(): void
    {
        $subject = self::createManager(['backend' => [
            self::createAuthenticator(new Failure('invalid-credentials', 'Login failed')),
            self::createAuthenticator(new Authenticated(self::createPrincipal('backend'))),
        ]]);
        $result = $subject->authenticate(new ServerRequest('https://example.com/'), Realm::backend());
        self::assertInstanceOf(Failure::class, $result);
        self::assertSame('invalid-credentials', $result->reason);
        self::assertSame('Login failed', $result->userFacingMessage);
    }

    #[Test]
    public function challengeStopsTheChain(): void
    {
        $response = new Response(null, 401);
        $subject = self::createManager(['backend' => [
            self::createAuthenticator(new Challenge($response)),
            self::createAuthenticator(new Authenticated(self::createPrincipal('backend'))),
        ]]);
        $result = $subject->authenticate(new ServerRequest('https://example.com/'), Realm::backend());
        self::assertInstanceOf(Challenge::class, $result);
        self::assertSame($response, $result->response);
    }

    #[Test]
    public function onlyAuthenticatorsOfTheRealmAreAsked(): void
    {
        $subject = self::createManager([
            'backend' => [self::createAuthenticator(new Failure('backend'))],
            'api' => [self::createAuthenticator(new Failure('api'))],
        ]);
        $request = new ServerRequest('https://example.com/');

        $backendResult = $subject->authenticate($request, Realm::backend());
        self::assertInstanceOf(Failure::class, $backendResult);
        self::assertSame('backend', $backendResult->reason);
        $apiResult = $subject->authenticate($request, Realm::custom('api'));
        self::assertInstanceOf(Failure::class, $apiResult);
        self::assertSame('api', $apiResult->reason);
        self::assertInstanceOf(NotApplicable::class, $subject->authenticate($request, Realm::custom('other')));
        self::assertInstanceOf(NotApplicable::class, $subject->authenticate($request, Realm::frontend()));
    }

    #[Test]
    public function frontendAuthenticatorsServeTheFrontendRealmOfEverySite(): void
    {
        $authenticator = new class implements AuthenticatorInterface {
            /** @var list<string> */
            public array $askedRealms = [];

            public function authenticate(ServerRequestInterface $request, Realm $realm): AuthenticationResult
            {
                $this->askedRealms[] = $realm->identifier;
                return new NotApplicable();
            }
        };
        $subject = self::createManager(['frontend' => [$authenticator]]);
        $request = new ServerRequest('https://example.com/');

        $subject->authenticate($request, Realm::backend());
        $subject->authenticate($request, Realm::frontend());
        $subject->authenticate($request, Realm::frontend(new Site('main', 1, [])));

        self::assertSame(['frontend', 'frontend:main'], $authenticator->askedRealms);
    }

    #[Test]
    public function principalOfAnotherRealmIsRefused(): void
    {
        $subject = self::createManager(['api' => [
            self::createAuthenticator(new Authenticated(self::createPrincipal('backend'))),
        ]]);
        $this->expectException(\LogicException::class);
        $this->expectExceptionCode(1791491203);
        $subject->authenticate(new ServerRequest('https://example.com/'), Realm::custom('api'));
    }

    #[Test]
    public function attributesOfTheOutcomeAreProvided(): void
    {
        $user = new \stdClass();
        $subject = new Authenticated(self::createPrincipal('backend'), ['legacyUser' => $user, 'empty' => null]);

        self::assertTrue($subject->hasAttribute('legacyUser'));
        self::assertSame($user, $subject->getAttribute('legacyUser'));
        self::assertTrue($subject->hasAttribute('empty'));
        self::assertFalse($subject->hasAttribute('unknown'));
    }

    #[Test]
    public function missingAttributeIsRefused(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1792934547);
        new Authenticated(self::createPrincipal('backend'))->getAttribute('unknown');
    }

    /**
     * @param array<string, list<AuthenticatorInterface>> $authenticatorsByRealm
     */
    private static function createManager(array $authenticatorsByRealm): AuthenticatorManager
    {
        $factories = [];
        $idsByRealm = [];
        foreach ($authenticatorsByRealm as $realm => $authenticators) {
            foreach ($authenticators as $index => $authenticator) {
                $id = $realm . '.' . $index;
                $factories[$id] = static fn(): AuthenticatorInterface => $authenticator;
                $idsByRealm[$realm][] = $id;
            }
        }
        return new AuthenticatorManager(new ServiceLocator($factories), $idsByRealm);
    }

    private static function createPrincipal(string $realmIdentifier): Principal
    {
        return new Principal(new PrincipalContext(PrincipalRole::USER, 13), $realmIdentifier, 'editor');
    }

    private static function createAuthenticator(AuthenticationResult $result): AuthenticatorInterface
    {
        return new class ($result) implements AuthenticatorInterface {
            public function __construct(
                private readonly AuthenticationResult $result,
            ) {}

            public function authenticate(ServerRequestInterface $request, Realm $realm): AuthenticationResult
            {
                return $this->result;
            }
        };
    }
}
