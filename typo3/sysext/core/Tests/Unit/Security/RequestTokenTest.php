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
use TYPO3\CMS\Core\Security\RequestToken;
use TYPO3\CMS\Core\Security\RequestTokenException;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class RequestTokenTest extends UnitTestCase
{
    /**
     * @test
     */
    public function isCreated(): void
    {
        $scope = $this->createRandomString();
        $token = RequestToken::create($scope);
        $now = $this->createCurrentTime();
        self::assertSame($scope, $token->scope);
        self::assertEquals($now, $token->time);
        self::assertSame([], $token->params);
    }

    /**
     * @test
     */
    public function isCreatedWithProperties(): void
    {
        $scope = $this->createRandomString();
        $time = $this->createRandomTime();
        $params = ['value' => bin2hex(random_bytes(4))];
        $token = new RequestToken($scope, $time, $params);
        self::assertSame($scope, $token->scope);
        self::assertEquals($time, $token->time);
        self::assertSame($params, $token->params);
    }

    /**
     * @test
     */
    public function paramsAreOverriddenInNewInstance(): void
    {
        $scope = $this->createRandomString();
        $params = ['nested' => ['value' => bin2hex(random_bytes(4))]];
        $token = RequestToken::create($scope)->withParams(['nested' => ['original' => true]]);
        $modifiedToken = $token->withParams($params);
        self::assertNotSame($token, $modifiedToken);
        self::assertSame($params, $modifiedToken->params);
    }

    /**
     * @test
     */
    public function paramsAreMergedInNewInstance(): void
    {
        $scope = $this->createRandomString();
        $params = ['nested' => ['value' => bin2hex(random_bytes(4))]];
        $token = RequestToken::create($scope)->withParams(['nested' => ['original' => true]]);
        $modifiedToken = $token->withMergedParams($params);
        self::assertNotSame($token, $modifiedToken);
        self::assertSame(array_merge_recursive($token->params, $params), $modifiedToken->params);
    }

    /**
     * @test
     */
    public function isEncodedAndDecoded(): void
    {
        $scope = $this->createRandomString();
        $time = $this->createRandomTime();
        $params = ['value' => bin2hex(random_bytes(4))];
        $token = new RequestToken($scope, $time, $params);

        $nonce = Nonce::create();
        $recodedToken = RequestToken::fromHashSignedJwt($token->toHashSignedJwt($nonce), $nonce);
        self::assertSame($recodedToken->scope, $token->scope);
        self::assertEquals($recodedToken->time, $token->time);
        self::assertSame($recodedToken->params, $token->params);
        self::assertSame('nonce', $recodedToken->getSigningSecretIdentifier()->type);
        self::assertEquals($nonce->getSigningIdentifier(), $recodedToken->getSigningSecretIdentifier());
    }

    /**
     * @test
     */
    public function invalidJwtThrowsException(): void
    {
        $nonce = Nonce::create();
        $this->expectException(RequestTokenException::class);
        $this->expectExceptionCode(1651771352);
        RequestToken::fromHashSignedJwt('no-jwt-at-all', $nonce);
    }

    private function createRandomString(): string
    {
        return bin2hex(random_bytes(4));
    }

    private function createRandomTime(): \DateTimeImmutable
    {
        $now = $this->createCurrentTime();
        $delta = random_int(-7200, 7200);
        $interval = new \DateInterval(sprintf('PT%dS', abs($delta)));
        return $delta < 0 ? $now->sub($interval) : $now->add($interval);
    }

    private function createCurrentTime(): \DateTimeImmutable
    {
        // drop microtime, second is the minimum date-interval
        return \DateTimeImmutable::createFromFormat(
            \DateTimeImmutable::RFC3339,
            (new \DateTimeImmutable())->format(\DateTimeImmutable::RFC3339)
        );
    }
}
