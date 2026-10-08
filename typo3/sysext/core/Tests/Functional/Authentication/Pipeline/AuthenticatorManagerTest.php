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

namespace TYPO3\CMS\Core\Tests\Functional\Authentication\Pipeline;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Authentication\Pipeline\Authenticated;
use TYPO3\CMS\Core\Authentication\Pipeline\AuthenticatorManager;
use TYPO3\CMS\Core\Authentication\Pipeline\Failure;
use TYPO3\CMS\Core\Authentication\Pipeline\NotApplicable;
use TYPO3\CMS\Core\Authentication\Pipeline\Realm;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Tests\TestAuthenticator\HeaderAuthenticator;

final class AuthenticatorManagerTest extends FunctionalTestCase
{
    protected bool $initializeDatabase = false;

    protected array $testExtensionsToLoad = [
        'typo3/sysext/core/Tests/Functional/Fixtures/Extensions/test_authenticator',
    ];

    #[Test]
    public function authenticatorRegisteredForSeveralRealmsServesEachOfThem(): void
    {
        $request = new ServerRequest('https://example.com/')->withHeader('X-Test-User', 'editor');
        $subject = $this->get(AuthenticatorManager::class);

        foreach ([Realm::backend(), Realm::custom('api')] as $realm) {
            $result = $subject->authenticate($request, $realm);
            self::assertInstanceOf(Authenticated::class, $result);
            self::assertSame($realm->identifier, $result->principal->realmIdentifier);
            self::assertSame(HeaderAuthenticator::class, $result->getAttribute('authenticator'));
        }
    }

    #[Test]
    public function authenticatorIsNotAskedForRealmsItIsNotRegisteredFor(): void
    {
        $request = new ServerRequest('https://example.com/')->withHeader('X-Test-User', 'editor');

        self::assertInstanceOf(NotApplicable::class, $this->get(AuthenticatorManager::class)->authenticate($request, Realm::custom('other')));
    }

    #[Test]
    public function authenticatorWithHigherPriorityIsAskedFirst(): void
    {
        $request = new ServerRequest('https://example.com/')->withHeader('X-Test-User', 'blocked');
        $subject = $this->get(AuthenticatorManager::class);

        // HeaderAuthenticator is registered after BlockingAuthenticator, which
        // would refuse the user, and is only asked first for its priority
        $backendResult = $subject->authenticate($request, Realm::backend());
        self::assertInstanceOf(Authenticated::class, $backendResult);
        self::assertSame(HeaderAuthenticator::class, $backendResult->getAttribute('authenticator'));
    }

    #[Test]
    public function frontendAuthenticatorServesTheFrontendOfEverySite(): void
    {
        $request = new ServerRequest('https://example.com/');
        $subject = $this->get(AuthenticatorManager::class);

        foreach ([Realm::frontend(), Realm::frontend(new Site('main', 1, []))] as $realm) {
            $result = $subject->authenticate($request, $realm);
            self::assertInstanceOf(Failure::class, $result);
            self::assertSame('frontend', $result->reason);
        }
    }
}
