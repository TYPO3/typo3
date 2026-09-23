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

namespace TYPO3\CMS\Frontend\Tests\Unit\Middleware;

use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\TypoScript\AST\Node\RootNode;
use TYPO3\CMS\Core\TypoScript\FrontendTypoScript;
use TYPO3\CMS\Frontend\Middleware\ContentLengthResponseHeader;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class ContentLengthResponseHeaderTest extends UnitTestCase
{
    #[Test]
    public function contentLengthHeaderIsAddedForKnownBodySize(): void
    {
        $response = new Response();
        $response->getBody()->write('some content');

        $result = $this->processResponse($response);

        self::assertSame('12', $result->getHeaderLine('Content-Length'));
    }

    #[Test]
    public function contentLengthHeaderIsNotAddedForUnknownBodySize(): void
    {
        $body = self::createStub(StreamInterface::class);
        $body->method('getSize')->willReturn(null);

        $result = $this->processResponse((new Response())->withBody($body));

        self::assertFalse($result->hasHeader('Content-Length'));
    }

    #[Test]
    public function contentLengthHeaderIsNotAddedIfDisabledByTypoScript(): void
    {
        $response = new Response();
        $response->getBody()->write('some content');

        $result = $this->processResponse($response, ['enableContentLengthHeader' => '0']);

        self::assertFalse($result->hasHeader('Content-Length'));
    }

    private function processResponse(ResponseInterface $response, array $configArray = []): ResponseInterface
    {
        $frontendTypoScript = new FrontendTypoScript(new RootNode(), [], [], []);
        $frontendTypoScript->setConfigTree(new RootNode());
        $frontendTypoScript->setConfigArray($configArray);
        $request = (new ServerRequest())->withAttribute('frontend.typoscript', $frontendTypoScript);
        $handler = new class ($response) implements RequestHandlerInterface {
            public function __construct(private readonly ResponseInterface $response) {}
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return $this->response;
            }
        };

        return (new ContentLengthResponseHeader(new Context()))->process($request, $handler);
    }
}
