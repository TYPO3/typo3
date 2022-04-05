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

namespace TYPO3\CMS\Core\Tests\Unit\Security;

use TYPO3\CMS\Core\Security\Nonce;
use TYPO3\CMS\Core\Security\NonceException;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class NonceTest extends UnitTestCase
{
    public function nonceIsCreatedDataProvider(): \Generator
    {
        yield [0, 40];
        yield [20, 40];
        yield [40, 40];
        yield [60, 60];
    }

    /**
     * @test
     * @dataProvider nonceIsCreatedDataProvider
     */
    public function isCreated(int $length, int $expectedLength): void
    {
        $nonce = Nonce::create($length);
        self::assertSame($expectedLength, strlen($nonce->binary));
        self::assertSame($nonce->b64, StringUtility::base64urlEncode($nonce->binary));
    }

    /**
     * @test
     */
    public function isCreatedWithProperties(): void
    {
        $binary = random_bytes(40);
        $time = $this->createRandomTime();
        $nonce = new Nonce($binary, $time);
        self::assertSame($binary, $nonce->binary);
        self::assertEquals($time, $nonce->time);
    }

    /**
     * @test
     */
    public function isEncodedAndDecoded(): void
    {
        $nonce = Nonce::create();
        $recodedNonce = Nonce::fromHashSignedJwt($nonce->toHashSignedJwt());
        self::assertEquals($recodedNonce, $nonce);
    }

    /**
     * @test
     */
    public function invalidJwtThrowsException(): void
    {
        $this->expectException(NonceException::class);
        $this->expectExceptionCode(1651771351);
        Nonce::fromHashSignedJwt('no-jwt-at-all');
    }

    private function createRandomTime(): \DateTimeImmutable
    {
        // drop microtime, second is the minimum date-interval here
        $now = \DateTimeImmutable::createFromFormat(
            \DateTimeImmutable::RFC3339,
            (new \DateTimeImmutable())->format(\DateTimeImmutable::RFC3339)
        );
        $delta = random_int(-7200, 7200);
        $interval = new \DateInterval(sprintf('PT%dS', abs($delta)));
        return $delta < 0 ? $now->sub($interval) : $now->add($interval);
    }
}
