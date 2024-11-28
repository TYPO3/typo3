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

namespace TYPO3\CMS\Core\Tests\Unit\Context;

use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Context\SecurityAspect;
use TYPO3\CMS\Core\Security\NoncePool;
use TYPO3\CMS\Core\Security\RequestToken;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class SecurityAspectTest extends UnitTestCase
{
    #[Test]
    public function receivedRequestTokenIsFunctional(): void
    {
        $aspect = new SecurityAspect();
        self::assertNull($aspect->getReceivedRequestToken());
        $aspect->setReceivedRequestToken(false);
        self::assertFalse($aspect->getReceivedRequestToken());
        $token = RequestToken::create(self::class);
        $aspect->setReceivedRequestToken($token);
        self::assertSame($token, $aspect->getReceivedRequestToken());
        $aspect->setReceivedRequestToken(null);
        self::assertNull($aspect->getReceivedRequestToken());
    }

    #[Test]
    public function signingSecretResolverIsFunctional(): void
    {
        $aspect = new SecurityAspect();
        self::assertInstanceOf(NoncePool::class, $aspect->getSigningSecretResolver()->findByType('nonce'));
    }

    #[Test]
    #[DoesNotPerformAssertions]
    public function noncePoolIsFunctional(): void
    {
        $aspect = new SecurityAspect();
        $aspect->getNoncePool();
    }
}
