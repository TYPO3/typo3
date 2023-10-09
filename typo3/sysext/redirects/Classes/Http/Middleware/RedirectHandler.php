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

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Utility\HttpUtility;
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
    protected EventDispatcherInterface $eventDispatcher;
    protected ResponseFactoryInterface $responseFactory;
    protected LoggerInterface $logger;

    public function __construct(
        RedirectService $redirectService,
        EventDispatcherInterface $eventDispatcher,
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
            $request->getUri()->getQuery()
        );

        // If the matched redirect is found, resolve it, and check further
        if (!is_array($matchedRedirect)) {
            return $handler->handle($request);
        }
        $url = $this->redirectService->getTargetUrl($matchedRedirect, $request);
        if ($url === null) {
            return $handler->handle($request);
        }
        if ($this->redirectUriWillRedirectToCurrentUri($request, $url)) {
            if ($this->isEmptyRedirectUri($url)) {
                // Empty uri leads to a redirect loop in Firefox, whereas Chrome would stop it but not displaying anything.
                // @see https://forge.typo3.org/issues/100791
                $this->logger->error('Empty redirect points to itself! Aborting.', ['record' => $matchedRedirect, 'uri' => (string)$url]);
            } elseif ($url->getFragment()) {
                // Enrich error message for unsharp check with target url fragment.
                $this->logger->error('Redirect ' . $url->getPath() . ' eventually points to itself! Target with fragment can not be checked and we take the safe check to avoid redirect loops. Aborting.', ['record' => $matchedRedirect, 'uri' => (string)$url]);
            } else {
                $this->logger->error('Redirect ' . $url->getPath() . ' points to itself! Aborting.', ['record' => $matchedRedirect, 'uri' => (string)$url]);
            }
            return $handler->handle($request);
        }
        $this->logger->debug('Redirecting', ['record' => $matchedRedirect, 'uri' => (string)$url]);
        $response = $this->buildRedirectResponse($url, $matchedRedirect);
        // Dispatch event, allowing listeners to execute further tasks and to adjust the PSR-7 response
        return $this->eventDispatcher->dispatch(
            new RedirectWasHitEvent($request, $response, $matchedRedirect, $url)
        )->getResponse();
    }

    protected function buildRedirectResponse(UriInterface $uri, array $redirectRecord): ResponseInterface
    {
        return $this->responseFactory
            ->createResponse((int)$redirectRecord['target_statuscode'])
            ->withHeader('location', (string)$uri)
            ->withHeader('X-Redirect-By', 'TYPO3 Redirect ' . $redirectRecord['uid']);
    }

    /**
     * Checks if redirect uri matches current request uri.
     */
    protected function redirectUriWillRedirectToCurrentUri(ServerRequestInterface $request, UriInterface $redirectUri): bool
    {
        if ($this->isEmptyRedirectUri($redirectUri)) {
            return true;
        }
        $requestUri = $request->getUri();
        $redirectIsAbsolute = $redirectUri->getHost() && $redirectUri->getScheme();
        $requestUri = $this->sanitizeUriForComparison($requestUri, !$redirectIsAbsolute);
        $redirectUri = $this->sanitizeUriForComparison($redirectUri, !$redirectIsAbsolute);
        return (string)$requestUri === (string)$redirectUri;
    }

    /**
     * Strip down uri to be suitable to make valid comparison in 'redirectUriWillRedirectToCurrentUri()'
     * if uri is pointing to itself and redirect should be processed.
     */
    protected function sanitizeUriForComparison(UriInterface $uri, bool $relativeCheck): UriInterface
    {
        // Remove schema, host and port if we need to sanitize for relative check.
        if ($relativeCheck) {
            $uri = $uri->withScheme('')->withHost('')->withPort(null);
        }

        // Remove default port by schema, as they are superfluous and not meaningful enough, and even not
        // set in a request uri as this depends a lot on the used webserver setup and infrastructure.
        $portDefaultSchemaMap = [
            // we only need web ports here, as web request could not be done over another
            // schema at all, ex. ftp or mailto.
            80 => 'http',
            443 => 'https',
        ];
        if (
            !$relativeCheck
            && $uri->getScheme()
            && isset($portDefaultSchemaMap[$uri->getPort()])
            && $uri->getScheme() === $portDefaultSchemaMap[$uri->getPort()]
        ) {
            $uri = $uri->withPort(null);
        }

        // Remove userinfo, as request would not hold it and so comparing would lead to a false-positive result
        if ($uri->getUserInfo()) {
            $uri = $uri->withUserInfo('');
        }

        // Browser should and do not hand over the fragment part in a request as this is defined to be handled
        // by clients only in the protocol, thus we remove the fragment to be safe and do not end in redirect loop
        // for targets with fragments because we do not get it in the request. Still not optimal but the best we
        // can do in this case.
        if ($uri->getFragment()) {
            $uri = $uri->withFragment('');
        }

        // Query arguments do not have to be in the same order to be the same outcome, thus sorting them will
        // give us a valid comparison, and we can correctly determine if we would have a redirect to the same uri.
        // Arguments with empty values are kept, because removing them might lead to false-positives in some cases.
        if ($uri->getQuery()) {
            $parts = [];
            parse_str($uri->getQuery(), $parts);
            ksort($parts);
            $uri = $uri->withQuery(HttpUtility::buildQueryString($parts));
        }

        return $uri;
    }

    /**
     * Empty uri leads to a redirect loop in Firefox, whereas Chrome would stop it but not displaying anything.
     * @see https://forge.typo3.org/issues/100791
     */
    private function isEmptyRedirectUri(UriInterface $uri): bool
    {
        return (string)$uri === '';
    }
}
