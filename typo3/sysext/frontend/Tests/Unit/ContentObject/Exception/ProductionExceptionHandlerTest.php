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

namespace TYPO3\CMS\Frontend\Tests\Unit\ContentObject\Exception;

use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Log\NullLogger;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\DateTimeAspect;
use TYPO3\CMS\Core\Crypto\Random;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Http\ImmediateResponseException;
use TYPO3\CMS\Core\Http\PropagateResponseException;
use TYPO3\CMS\Frontend\ContentObject\Exception\ProductionExceptionHandler;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class ProductionExceptionHandlerTest extends UnitTestCase
{
    use ProphecyTrait;

    protected $resetSingletonInstances = true;

    protected ProductionExceptionHandler $subject;

    /**
     * Sets up this test case.
     */
    protected function setUp(): void
    {
        $this->subject = new ProductionExceptionHandler(new Context(), new Random(), new NullLogger());
    }

    /**
     * @test
     */
    public function relayPropagateResponseException(): void
    {
        $response = $this->getMockBuilder(HtmlResponse::class)
            ->disableOriginalConstructor()
            ->getMock();
        $exception = new PropagateResponseException($response, 1607328584);

        $this->expectException(PropagateResponseException::class);
        $this->subject->handle($exception);
    }

    /**
     * @test
     */
    public function relayImmediateResponseException(): void
    {
        $response = $this->getMockBuilder(HtmlResponse::class)
            ->disableOriginalConstructor()
            ->getMock();
        $exception = new ImmediateResponseException($response, 1533939251);

        $this->expectException(ImmediateResponseException::class);
        $this->subject->handle($exception);
    }

    /**
     * @test
     */
    public function handleReturnsMessageWithResolvedErrorCode(): void
    {
        $currentTimestamp = 1629993829;
        $random = '029cca07';

        $randomProphecy = $this->prophesize(Random::class);
        $randomProphecy->generateRandomHexString(8)->willReturn($random);

        $exceptionHandler = new ProductionExceptionHandler(
            new Context(['date' => new DateTimeAspect(new \DateTimeImmutable('@' . $currentTimestamp))]),
            $randomProphecy->reveal(),
            new NullLogger()
        );

        self::assertEquals(
            'Oops, an error occurred! Code: ' . date('YmdHis', $currentTimestamp) . $random,
            $exceptionHandler->handle(new \Exception('Some exception', 1629996089))
        );
    }

    /**
     * @test
     */
    public function handleReturnsCustomErrorMessageWithResolvedErrorCode(): void
    {
        $currentTimestamp = 1629993829;
        $random = '029cca07';

        $randomProphecy = $this->prophesize(Random::class);
        $randomProphecy->generateRandomHexString(8)->willReturn($random);

        $exceptionHandler = new ProductionExceptionHandler(
            new Context(['date' => new DateTimeAspect(new \DateTimeImmutable('@' . $currentTimestamp))]),
            $randomProphecy->reveal(),
            new NullLogger()
        );
        $exceptionHandler->setConfiguration([
            'errorMessage' => 'Custom error message: {code}',
        ]);

        self::assertEquals(
            'Custom error message: ' . date('YmdHis', $currentTimestamp) . $random,
            $exceptionHandler->handle(new \Exception('Some exception', 1629996090))
        );
    }

    /**
     * @test
     */
    public function handleReturnsCustomErrorMessageWithResolvedErrorCodeForLegacyPlaceholder(): void
    {
        $currentTimestamp = 1629993829;
        $random = '029cca07';

        $randomProphecy = $this->prophesize(Random::class);
        $randomProphecy->generateRandomHexString(8)->willReturn($random);

        $exceptionHandler = new ProductionExceptionHandler(
            new Context(['date' => new DateTimeAspect(new \DateTimeImmutable('@' . $currentTimestamp))]),
            $randomProphecy->reveal(),
            new NullLogger()
        );
        $exceptionHandler->setConfiguration([
            'errorMessage' => 'Custom error message: %s',
        ]);

        self::assertEquals(
            'Custom error message: ' . date('YmdHis', $currentTimestamp) . $random,
            $exceptionHandler->handle(new \Exception('Some exception', 1629996091))
        );
    }
}
