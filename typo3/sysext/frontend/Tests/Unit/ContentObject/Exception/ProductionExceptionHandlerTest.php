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

use Psr\Log\NullLogger;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Http\ImmediateResponseException;
use TYPO3\CMS\Frontend\ContentObject\Exception\ProductionExceptionHandler;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class ProductionExceptionHandlerTest extends UnitTestCase
{
    /**
     * @var ProductionExceptionHandler
     */
    protected $subject;

    /**
     * Sets up this test case.
     */
    protected function setUp(): void
    {
        $this->subject = new ProductionExceptionHandler();
        $this->subject->setLogger(new NullLogger());
    }
    /**
     * @test
     */
    public function relayImmediateResponseException()
    {
        $response = $this->getMockBuilder(HtmlResponse::class)
            ->disableOriginalConstructor()
            ->getMock();
        $exception = new ImmediateResponseException($response, 1533939251);

        $this->expectException(ImmediateResponseException::class);
        $this->subject->handle($exception);
    }
}
