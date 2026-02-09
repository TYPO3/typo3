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

namespace TYPO3\CMS\Frontend\Tests\Functional\Controller;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Frontend\Controller\ErrorController;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class ErrorControllerTest extends FunctionalTestCase
{
    #[Test]
    public function pageNotFoundHandlingReturns404ResponseIfNotConfigured(): void
    {
        $request = new ServerRequest();
        $request = (new ServerRequest())->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request));
        $subject = $this->get(ErrorController::class);
        $response = $subject->pageNotFoundAction($request, 'This test page was not found!');
        self::assertSame(404, $response->getStatusCode());
        self::assertStringContainsString('This test page was not found!', $response->getBody()->getContents());
    }

    #[Test]
    public function unavailableHandlingReturns503ResponseIfNotConfigured(): void
    {
        $request = new ServerRequest();
        $request = (new ServerRequest())->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request));
        $subject = $this->get(ErrorController::class);
        $response = $subject->unavailableAction($request, 'This page is temporarily unavailable.');
        self::assertSame(503, $response->getStatusCode());
        self::assertStringContainsString('This page is temporarily unavailable.', $response->getBody()->getContents());
    }

    #[Test]
    public function unavailableHandlingDoesNotTriggerDueToDevIpMask(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['devIPmask'] = '*';
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $this->expectExceptionMessage('All your system are belong to us!');
        $this->expectExceptionCode(1518472181);
        $request = new ServerRequest();
        $request = (new ServerRequest())->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request));
        $subject = $this->get(ErrorController::class);
        $subject->unavailableAction($request, 'All your system are belong to us!');
    }

    #[Test]
    public function internalErrorHandlingReturns500ResponseIfNotConfigured(): void
    {
        $request = new ServerRequest();
        $request = (new ServerRequest())->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request));
        $subject = $this->get(ErrorController::class);
        $response = $subject->internalErrorAction($request, 'All your system are belong to us!');
        self::assertSame(500, $response->getStatusCode());
        self::assertStringContainsString('All your system are belong to us!', $response->getBody()->getContents());
    }

    #[Test]
    public function internalErrorHandlingDoesNotTriggerDueToDevIpMask(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['devIPmask'] = '*';
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $this->expectExceptionMessage('All your system are belong to us!');
        $this->expectExceptionCode(1607585445);
        $request = new ServerRequest();
        $request = (new ServerRequest())->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request));
        $subject = $this->get(ErrorController::class);
        $subject->internalErrorAction($request, 'All your system are belong to us!');
    }

    #[Test]
    public function defaultErrorHandlerWithHtmlResponseIsChosenWhenNoSiteConfiguredForPageNotFoundAction(): void
    {
        $request = new ServerRequest();
        $request = (new ServerRequest())->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request));
        $subject = $this->get(ErrorController::class);
        $response = $subject->pageNotFoundAction($request, 'Error handler is not configured.');
        self::assertSame(404, $response->getStatusCode());
        self::assertSame('text/html; charset=utf-8', $response->getHeaderLine('Content-Type'));
        self::assertStringContainsString('Error handler is not configured.', $response->getBody()->getContents());
    }

    #[Test]
    public function defaultErrorHandlerWithJsonResponseIsChosenWhenNoSiteConfiguredForPageNotFoundAction(): void
    {
        $request = new ServerRequest();
        $request = (new ServerRequest())->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request));
        $subject = $this->get(ErrorController::class);
        $response = $subject->pageNotFoundAction($request->withAddedHeader('Accept', 'application/json'), 'Error handler is not configured.');
        $responseContent = \json_decode($response->getBody()->getContents(), true);
        self::assertSame(404, $response->getStatusCode());
        self::assertSame('application/json; charset=utf-8', $response->getHeaderLine('Content-Type'));
        self::assertEquals(['reason' => 'Error handler is not configured.'], $responseContent);
    }

    #[Test]
    public function defaultErrorHandlerWithHtmlResponseIsChosenWhenNoSiteConfiguredForUnavailableAction(): void
    {
        $request = new ServerRequest();
        $request = (new ServerRequest())->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request));
        $subject = $this->get(ErrorController::class);
        $response = $subject->unavailableAction($request, 'Error handler is not configured.');
        self::assertSame(503, $response->getStatusCode());
        self::assertSame('text/html; charset=utf-8', $response->getHeaderLine('Content-Type'));
        self::assertStringContainsString('Error handler is not configured.', $response->getBody()->getContents());
    }

    #[Test]
    public function defaultErrorHandlerWithJsonResponseIsChosenWhenNoSiteConfiguredForUnavailableAction(): void
    {
        $request = new ServerRequest();
        $request = (new ServerRequest())->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request));
        $subject = $this->get(ErrorController::class);
        $response = $subject->unavailableAction($request->withAddedHeader('Accept', 'application/json'), 'Error handler is not configured.');
        $responseContent = \json_decode($response->getBody()->getContents(), true);
        self::assertSame(503, $response->getStatusCode());
        self::assertSame('application/json; charset=utf-8', $response->getHeaderLine('Content-Type'));
        self::assertEquals(['reason' => 'Error handler is not configured.'], $responseContent);
    }

    #[Test]
    public function defaultErrorHandlerWithHtmlResponseIsChosenWhenNoSiteConfiguredForInternalErrorAction(): void
    {
        $request = new ServerRequest();
        $request = (new ServerRequest())->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request));
        $subject = $this->get(ErrorController::class);
        $response = $subject->internalErrorAction($request, 'Error handler is not configured.');
        self::assertSame(500, $response->getStatusCode());
        self::assertSame('text/html; charset=utf-8', $response->getHeaderLine('Content-Type'));
        self::assertStringContainsString('Error handler is not configured.', $response->getBody()->getContents());
    }

    #[Test]
    public function defaultErrorHandlerWithJsonResponseIsChosenWhenNoSiteConfiguredForInternalErrorAction(): void
    {
        $request = new ServerRequest();
        $request = (new ServerRequest())->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request));
        $subject = $this->get(ErrorController::class);
        $response = $subject->internalErrorAction($request->withAddedHeader('Accept', 'application/json'), 'Error handler is not configured.');
        $responseContent = \json_decode($response->getBody()->getContents(), true);
        self::assertSame(500, $response->getStatusCode());
        self::assertSame('application/json; charset=utf-8', $response->getHeaderLine('Content-Type'));
        self::assertEquals(['reason' => 'Error handler is not configured.'], $responseContent);
    }

    #[Test]
    public function defaultErrorHandlerWithHtmlResponseIsChosenWhenNoSiteConfiguredForAccessDeniedAction(): void
    {
        $request = new ServerRequest();
        $request = (new ServerRequest())->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request));
        $subject = $this->get(ErrorController::class);
        $response = $subject->accessDeniedAction($request, 'Error handler is not configured.');
        self::assertSame(403, $response->getStatusCode());
        self::assertSame('text/html; charset=utf-8', $response->getHeaderLine('Content-Type'));
        self::assertStringContainsString('Error handler is not configured.', $response->getBody()->getContents());
    }

    #[Test]
    public function defaultErrorHandlerWithJsonResponseIsChosenWhenNoSiteConfiguredForAccessDeniedAction(): void
    {
        $request = new ServerRequest();
        $request = (new ServerRequest())->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request));
        $subject = $this->get(ErrorController::class);
        $response = $subject->accessDeniedAction($request->withAddedHeader('Accept', 'application/json'), 'Error handler is not configured.');
        $responseContent = \json_decode($response->getBody()->getContents(), true);
        self::assertSame(403, $response->getStatusCode());
        self::assertSame('application/json; charset=utf-8', $response->getHeaderLine('Content-Type'));
        self::assertEquals(['reason' => 'Error handler is not configured.'], $responseContent);
    }
}
