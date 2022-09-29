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

namespace TYPO3\CMS\Core\Context;

use TYPO3\CMS\Core\Security\Nonce;
use TYPO3\CMS\Core\Security\NoncePool;
use TYPO3\CMS\Core\Security\RequestToken;
use TYPO3\CMS\Core\Security\SigningSecretResolver;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * @internal
 */
class SecurityAspect implements AspectInterface
{
    /**
     * `null` in case no request token was received
     * `false` in case a request token was received, which was invalid
     */
    protected RequestToken|false|null $receivedRequestToken = null;

    protected SigningSecretResolver $signingSecretResolver;

    protected NoncePool $noncePool;

    public static function provideIn(Context $context): self
    {
        if ($context->hasAspect('security')) {
            $securityAspect = $context->getAspect('security');
        }
        if (!isset($securityAspect) || !$securityAspect instanceof SecurityAspect) {
            $securityAspect = GeneralUtility::makeInstance(SecurityAspect::class);
            $context->setAspect('security', $securityAspect);
        }
        return $securityAspect;
    }

    public function __construct()
    {
        $this->noncePool = GeneralUtility::makeInstance(NoncePool::class);
        $this->signingSecretResolver = GeneralUtility::makeInstance(
            SigningSecretResolver::class,
            [
                'nonce' => $this->noncePool,
                // @todo enrich in separate step with `*FormProtection`
            ]
        );
    }

    public function get(string $name): null|bool|Nonce|RequestToken
    {
        return match ($name) {
            'receivedRequestToken' => $this->receivedRequestToken,
            'signingSecretResolver' => $this->signingSecretResolver,
            'noncePool' => $this->noncePool,
            default => null,
        };
    }

    public function getReceivedRequestToken(): RequestToken|false|null
    {
        return $this->receivedRequestToken;
    }

    public function setReceivedRequestToken(RequestToken|false|null $receivedRequestToken): void
    {
        $this->receivedRequestToken = $receivedRequestToken;
    }

    /**
     * Resolves corresponding signing secret providers (such as `NoncePool`).
     * Example: `...->getSigningSecretResolver->findByType('nonce')` resolves `NoncePool`
     */
    public function getSigningSecretResolver(): SigningSecretResolver
    {
        return $this->signingSecretResolver;
    }

    public function getNoncePool(): NoncePool
    {
        return $this->noncePool;
    }

    /**
     * Shortcut function to `NoncePool`, providing a `SigningSecret`
     * @todo this is a "comfort function", might be dropped
     */
    public function provideNonce(): Nonce
    {
        return $this->noncePool->provideSigningSecret();
    }
}
