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

namespace TYPO3\CMS\Core\Tests\Functional\Security;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\SecurityAspect;
use TYPO3\CMS\Core\Crypto\PasswordHashing\Argon2idPasswordHash;
use TYPO3\CMS\Core\Crypto\PasswordHashing\PasswordHashFactory;
use TYPO3\CMS\Core\Crypto\PasswordHashing\PhpassPasswordHash;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Security\Nonce;
use TYPO3\CMS\Core\Security\NoncePool;
use TYPO3\CMS\Core\Security\PasswordCredentialVerifier;
use TYPO3\CMS\Core\Security\PasswordVerification;
use TYPO3\CMS\Core\Security\RequestToken;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class PasswordCredentialVerifierTest extends FunctionalTestCase
{
    private const string PASSWORD = 'test-password-1234';

    #[Test]
    public function passwordMatchingDefaultHashIsVerified(): void
    {
        $passwordHash = $this->get(PasswordHashFactory::class)->getDefaultHashInstance('BE')->getHashedPassword(self::PASSWORD);

        $result = $this->get(PasswordCredentialVerifier::class)->verify(new BackendUserAuthentication(), $passwordHash, self::PASSWORD);

        self::assertSame(PasswordVerification::Verified, $result);
    }

    #[Test]
    public function passwordMatchingHashOfOtherMethodIsVerifiedWithOutdatedHash(): void
    {
        $passwordHash = new PhpassPasswordHash()->getHashedPassword(self::PASSWORD);

        $result = $this->get(PasswordCredentialVerifier::class)->verify(new BackendUserAuthentication(), $passwordHash, self::PASSWORD);

        self::assertSame(PasswordVerification::VerifiedWithOutdatedHash, $result);
    }

    #[Test]
    public function passwordMatchingHashWithOutdatedOptionsIsVerifiedWithOutdatedHash(): void
    {
        $passwordHash = new Argon2idPasswordHash(['time_cost' => 4])->getHashedPassword(self::PASSWORD);

        $result = $this->get(PasswordCredentialVerifier::class)->verify(new BackendUserAuthentication(), $passwordHash, self::PASSWORD);

        self::assertSame(PasswordVerification::VerifiedWithOutdatedHash, $result);
    }

    #[Test]
    public function wrongPasswordIsInvalid(): void
    {
        $passwordHash = new PhpassPasswordHash()->getHashedPassword(self::PASSWORD);

        $result = $this->get(PasswordCredentialVerifier::class)->verify(new BackendUserAuthentication(), $passwordHash, 'wrong-password');

        self::assertSame(PasswordVerification::Invalid, $result);
    }

    #[Test]
    public function unknownHashFormatHasNoSuitableHashMethod(): void
    {
        $result = $this->get(PasswordCredentialVerifier::class)->verify(new BackendUserAuthentication(), 'not-a-password-hash', self::PASSWORD);

        self::assertSame(PasswordVerification::NoSuitableHashMethod, $result);
    }

    #[Test]
    public function hashedPasswordUsesDefaultHashMethodOfLoginType(): void
    {
        $passwordHashFactory = $this->get(PasswordHashFactory::class);
        $subject = $this->get(PasswordCredentialVerifier::class);

        $backendHash = $subject->hashPassword(new BackendUserAuthentication(), self::PASSWORD);
        $frontendHash = $subject->hashPassword(new FrontendUserAuthentication(), self::PASSWORD);

        self::assertFalse($passwordHashFactory->getDefaultHashInstance('BE')->isHashUpdateNeeded($backendHash));
        self::assertTrue($passwordHashFactory->getDefaultHashInstance('BE')->checkPassword(self::PASSWORD, $backendHash));
        self::assertFalse($passwordHashFactory->getDefaultHashInstance('FE')->isHashUpdateNeeded($frontendHash));
        self::assertTrue($passwordHashFactory->getDefaultHashInstance('FE')->checkPassword(self::PASSWORD, $frontendHash));
        self::assertSame(PasswordVerification::Verified, $subject->verify(new FrontendUserAuthentication(), $frontendHash, self::PASSWORD));
    }

    #[Test]
    public function requestTokenOfAnotherScopeDoesNotMatch(): void
    {
        SecurityAspect::provideIn($this->get(Context::class))->setReceivedRequestToken(RequestToken::create('core/user-auth/fe'));

        $result = $this->get(PasswordCredentialVerifier::class)->requestTokenMatches(
            new BackendUserAuthentication(),
            new ServerRequest('https://example.com/typo3/login', 'POST'),
        );

        self::assertFalse($result);
    }

    #[Test]
    public function requestTokenOfTheScopeMatches(): void
    {
        SecurityAspect::provideIn($this->get(Context::class))->setReceivedRequestToken(RequestToken::create('core/user-auth/be'));

        $result = $this->get(PasswordCredentialVerifier::class)->requestTokenMatches(
            new BackendUserAuthentication(),
            new ServerRequest('https://example.com/typo3/login', 'POST'),
        );

        self::assertTrue($result);
    }

    #[Test]
    public function requestTokenOfFrontendScopeMatchesFrontendUser(): void
    {
        SecurityAspect::provideIn($this->get(Context::class))->setReceivedRequestToken(RequestToken::create('core/user-auth/fe'));

        $result = $this->get(PasswordCredentialVerifier::class)->requestTokenMatches(
            new FrontendUserAuthentication(),
            new ServerRequest('https://example.com/login', 'POST'),
        );

        self::assertTrue($result);
    }

    #[Test]
    public function signingSecretOfMatchingRequestTokenIsRevoked(): void
    {
        $securityAspect = SecurityAspect::provideIn($this->get(Context::class));
        $nonce = Nonce::create();
        $securityAspect->getNoncePool()->merge(new NoncePool([$nonce->getSigningIdentifier()->name => $nonce]));
        $requestToken = RequestToken::fromHashSignedJwt(
            RequestToken::create('core/user-auth/be')->toHashSignedJwt($nonce),
            $securityAspect->getSigningSecretResolver()
        );
        $securityAspect->setReceivedRequestToken($requestToken);

        $result = $this->get(PasswordCredentialVerifier::class)->requestTokenMatches(
            new BackendUserAuthentication(),
            new ServerRequest('https://example.com/typo3/login', 'POST'),
        );

        self::assertTrue($result);
        self::assertContains($nonce->getSigningIdentifier()->name, $securityAspect->getNoncePool()->getRevocableNames());
    }
}
