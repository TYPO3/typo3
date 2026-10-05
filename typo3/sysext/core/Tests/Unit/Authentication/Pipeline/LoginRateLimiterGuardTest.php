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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Psr\Log\NullLogger;
use Symfony\Component\RateLimiter\LimiterInterface;
use Symfony\Component\RateLimiter\RateLimit;
use TYPO3\CMS\Core\Authentication\AbstractUserAuthentication;
use TYPO3\CMS\Core\Authentication\Pipeline\LoginRateLimiterGuard;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\RateLimiter\RateLimiterFactoryInterface;
use TYPO3\CMS\Core\RateLimiter\RequestRateLimitedException;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class LoginRateLimiterGuardTest extends UnitTestCase
{
    public static function exceededLimitDataProvider(): array
    {
        return [
            'backend' => ['BE', 1616175867],
            'frontend' => ['FE', 1616175847],
        ];
    }

    #[DataProvider('exceededLimitDataProvider')]
    #[Test]
    public function exceededLimitIsRefusedWithTheExceptionCodeOfTheLoginType(string $loginType, int $expectedCode): void
    {
        $request = new ServerRequest('https://example.com/', 'POST');
        $request = $request->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request));
        $user = self::createStub(AbstractUserAuthentication::class);
        $user->loginType = $loginType;
        $user->method('isActiveLogin')->willReturn(true);
        $limiter = self::createStub(LimiterInterface::class);
        $limiter->method('consume')->willReturn(new RateLimit(0, new \DateTimeImmutable('+15 minutes'), false, 5));
        $rateLimiterFactory = $this->createMock(RateLimiterFactoryInterface::class);
        $rateLimiterFactory->expects($this->once())->method('createLoginRateLimiter')->with($request, $loginType)->willReturn($limiter);

        $this->expectException(RequestRateLimitedException::class);
        $this->expectExceptionCode($expectedCode);
        new LoginRateLimiterGuard($rateLimiterFactory, new NullLogger())->ensureLoginRateLimit($user, $request);
    }

    #[Test]
    public function requestWithoutActiveLoginIsNotLimited(): void
    {
        $user = self::createStub(AbstractUserAuthentication::class);
        $user->method('isActiveLogin')->willReturn(false);
        $rateLimiterFactory = $this->createMock(RateLimiterFactoryInterface::class);
        $rateLimiterFactory->expects($this->never())->method('createLoginRateLimiter');

        $result = new LoginRateLimiterGuard($rateLimiterFactory, new NullLogger())
            ->ensureLoginRateLimit($user, new ServerRequest('https://example.com/'));

        self::assertNull($result);
    }
}
