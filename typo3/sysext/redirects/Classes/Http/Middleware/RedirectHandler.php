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

namespace TYPO3\CMS\Redirects\Http\Middleware;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Redirects\Event\RedirectWasHitEvent;
use TYPO3\CMS\Redirects\Service\RedirectService;

/**
 * Hooks into the frontend request, and checks if a redirect should apply,
 * If so, a redirect response is triggered.
 *
 * @internal
 */
class RedirectHandler implements MiddlewareInterface
{
    protected RedirectService $redirectService;
    protected EventDispatcher $eventDispatcher;
    protected ResponseFactoryInterface $responseFactory;
    protected LoggerInterface $logger;

    public function __construct(
        RedirectService $redirectService,
        EventDispatcher $eventDispatcher,
        ResponseFactoryInterface $responseFactory,
        LoggerInterface $logger
    ) {
        $this->redirectService = $redirectService;
        $this->eventDispatcher = $eventDispatcher;
        $this->responseFactory = $responseFactory;
        $this->logger = $logger;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $port = $request->getUri()->getPort();
        $matchedRedirect = $this->redirectService->matchRedirect(
            $request->getUri()->getHost() . ($port ? ':' . $port : ''),
            $request->getUri()->getPath(),
            $request->getUri()->getQuery() ?? ''
        );

        // If the matched redirect is found, resolve it, and check further
        if (is_array($matchedRedirect)) {
            $url = $this->redirectService->getTargetUrl($matchedRedirect, $request);
            if ($url instanceof UriInterface) {
                $this->logger->debug('Redirecting', ['record' => $matchedRedirect, 'uri' => (string)$url]);
                $response = $this->buildRedirectResponse($url, $matchedRedirect);
                // Dispatch event, allowing listeners to execute further tasks and to adjust the PSR-7 response
                return $this->eventDispatcher->dispatch(
                    new RedirectWasHitEvent($request, $response, $matchedRedirect, $url)
                )->getResponse();
            }
        }

        return $handler->handle($request);
    }

    protected function buildRedirectResponse(UriInterface $uri, array $redirectRecord): ResponseInterface
    {
        return $this->responseFactory
            ->createResponse((int)$redirectRecord['target_statuscode'])
            ->withHeader('location', (string)$uri)
            ->withHeader('X-Redirect-By', 'TYPO3 Redirect ' . $redirectRecord['uid']);
    }
}
