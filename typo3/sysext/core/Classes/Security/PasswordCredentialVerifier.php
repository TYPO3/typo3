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

namespace TYPO3\CMS\Core\Security;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Authentication\AbstractUserAuthentication;
use TYPO3\CMS\Core\Authentication\Event\BeforeRequestTokenProcessedEvent;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\SecurityAspect;
use TYPO3\CMS\Core\Crypto\PasswordHashing\InvalidPasswordHashException;
use TYPO3\CMS\Core\Crypto\PasswordHashing\PasswordHashFactory;

/**
 * The parts of a password login that the native backend and frontend
 * authenticators share: the request token check, the password verification
 * against a stored hash, the creation of a replacement hash and the timing
 * mitigation for unknown users. The hash method and the request token scope
 * are derived from the login type of the user object.
 *
 * Nothing is persisted here: where a user record and its hash are stored is
 * the business of the identity provider the authenticator looked the user up
 * with.
 *
 * @internal not part of TYPO3 Core API yet, still experimental as part of the
 *           authentication pipeline groundwork
 */
final readonly class PasswordCredentialVerifier
{
    public function __construct(
        private PasswordHashFactory $passwordHashFactory,
        private Context $context,
        private EventDispatcherInterface $eventDispatcher,
    ) {}

    /**
     * Validates the submitted request token (nonce) for the authentication
     * scope of the user ("core/user-auth/be" or "core/user-auth/fe") and
     * revokes its signing secret. Listeners of BeforeRequestTokenProcessedEvent
     * may adjust the token, felogin also derives the storage page restriction
     * from it.
     */
    public function requestTokenMatches(AbstractUserAuthentication $user, ServerRequestInterface $request): bool
    {
        $securityAspect = SecurityAspect::provideIn($this->context);
        $event = new BeforeRequestTokenProcessedEvent($user, $request, $securityAspect->getReceivedRequestToken());
        $this->eventDispatcher->dispatch($event);
        $requestToken = $event->getRequestToken();
        if (($requestToken->scope ?? null) !== 'core/user-auth/' . strtolower($user->loginType)) {
            return false;
        }
        if ($requestToken instanceof RequestToken && $requestToken->getSigningSecretIdentifier() !== null) {
            $securityAspect->getSigningSecretResolver()->revokeIdentifier($requestToken->getSigningSecretIdentifier());
        }
        return true;
    }

    /**
     * Verifies the password against the stored hash. A matching password whose
     * hash is outdated or not created with the default hash method of the login
     * type is reported as such, so the caller can store a replacement created by
     * hashPassword().
     */
    public function verify(AbstractUserAuthentication $user, string $passwordHash, string $password): PasswordVerification
    {
        try {
            $hashInstance = $this->passwordHashFactory->get($passwordHash, $user->loginType);
        } catch (InvalidPasswordHashException) {
            return PasswordVerification::NoSuitableHashMethod;
        }
        if (!$hashInstance->checkPassword($password, $passwordHash)) {
            return PasswordVerification::Invalid;
        }
        // Don't catch InvalidPasswordHashException here: Only install tool should handle those configuration failures
        $defaultHashInstance = $this->passwordHashFactory->getDefaultHashInstance($user->loginType);
        // Lax object comparison intended: Rehash if old and new salt objects are not
        // instances of the same class.
        if ($hashInstance->isHashUpdateNeeded($passwordHash) || $defaultHashInstance != $hashInstance) {
            return PasswordVerification::VerifiedWithOutdatedHash;
        }
        return PasswordVerification::Verified;
    }

    /**
     * Hashes the password with the default hash method of the login type of the user.
     */
    public function hashPassword(AbstractUserAuthentication $user, string $password): string
    {
        return $this->passwordHashFactory->getDefaultHashInstance($user->loginType)->getHashedPassword($password);
    }

    /**
     * Mimics password hashing for a login of an unknown user to mitigate
     * @link https://cwe.mitre.org/data/definitions/208.html: CWE-208: Observable Timing Discrepancy
     */
    public function mimicPasswordHashing(AbstractUserAuthentication $user): void
    {
        try {
            $this->hashPassword($user, random_bytes(10));
        } catch (\Exception) {
            // no further processing here
        }
    }
}
