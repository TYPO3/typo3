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

namespace TYPO3\CMS\Frontend\Tests\Functional\Middleware;

use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\NullLogger;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Routing\PageArguments;
use TYPO3\CMS\Frontend\Middleware\PageArgumentValidator;
use TYPO3\CMS\Frontend\Page\CacheHashCalculator;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class PageArgumentValidatorTest extends FunctionalTestCase
{
    private RequestHandlerInterface $responseOutputHandler;

    protected function setUp(): void
    {
        parent::setUp();
        // A request handler which only runs through
        $this->responseOutputHandler = new class () implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return new Response();
            }
        };
    }

    #[Test]
    public function givenCacheHashWithoutRequiredParametersTriggersRedirect(): void
    {
        $incomingUrl = 'https://example.com/lotus-flower/en/mr-magpie/bloom/?cHash=XYZ';
        $expectedResult = 'https://example.com/lotus-flower/en/mr-magpie/bloom/';
        $pageArguments = new PageArguments(13, '1', ['cHash' => 'XYZ'], ['parameter-from' => 'path']);
        $request = new ServerRequest($incomingUrl, 'GET');
        $request = $request->withAttribute('routing', $pageArguments);
        $subject = new PageArgumentValidator(new CacheHashCalculator());
        $subject->setLogger(new NullLogger());
        $response = $subject->process($request, $this->responseOutputHandler);
        self::assertEquals(308, $response->getStatusCode());
        self::assertEquals($expectedResult, $response->getHeader('Location')[0]);
    }

    #[Test]
    public function givenCacheHashNotMatchingCalculatedCacheHashTriggers404(): void
    {
        $incomingUrl = 'https://example.com/lotus-flower/en/mr-magpie/bloom/?cHash=YAZ';
        $pageArguments = new PageArguments(13, '1', ['cHash' => 'XYZ', 'dynamic' => 'argument'], ['parameter-from' => 'path']);
        $request = new ServerRequest($incomingUrl, 'GET');
        $request = $request->withAttribute('routing', $pageArguments);
        $subject = new PageArgumentValidator(new CacheHashCalculator());
        $response = $subject->process($request, $this->responseOutputHandler);
        self::assertEquals(404, $response->getStatusCode());
    }

    #[Test]
    public function noPageArgumentsReturnsErrorResponse(): void
    {
        $incomingUrl = 'https://king.com/lotus-flower/en/mr-magpie/bloom/';
        $request = new ServerRequest($incomingUrl, 'GET');
        $subject = new PageArgumentValidator(new CacheHashCalculator());
        $response = $subject->process($request, $this->responseOutputHandler);
        self::assertEquals(404, $response->getStatusCode());
    }

    #[Test]
    public function staticPageArgumentsSkipProcessingAndReturnsSuccess(): void
    {
        $incomingUrl = 'https://example.com/lotus-flower/en/mr-magpie/bloom/';
        $pageArguments = new PageArguments(13, '1', [], ['parameter-from' => 'path']);
        $request = new ServerRequest($incomingUrl, 'GET');
        $request = $request->withAttribute('routing', $pageArguments);
        $subject = new PageArgumentValidator(new CacheHashCalculator());
        $response = $subject->process($request, $this->responseOutputHandler);
        self::assertEquals(200, $response->getStatusCode());
    }

    #[Test]
    public function invalidCacheHashWithDynamicArgumentsTriggers404(): void
    {
        $incomingUrl = 'https://example.com/lotus-flower/en/mr-magpie/bloom/';
        $pageArguments = new PageArguments(13, '1', ['cHash' => 'coolio', 'download' => true], ['parameter-from' => 'path']);
        $request = new ServerRequest($incomingUrl, 'GET');
        $request = $request->withAttribute('routing', $pageArguments);
        $subject = new PageArgumentValidator(new CacheHashCalculator());
        $response = $subject->process($request, $this->responseOutputHandler);
        self::assertEquals(404, $response->getStatusCode());
    }
}
