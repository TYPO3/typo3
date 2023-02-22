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

namespace TYPO3\CMS\Core\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\HttpFoundation\Cookie;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\SecurityAspect;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Security\Nonce;
use TYPO3\CMS\Core\Security\NonceException;
use TYPO3\CMS\Core\Security\NoncePool;
use TYPO3\CMS\Core\Security\RequestToken;
use TYPO3\CMS\Core\Security\RequestTokenException;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * @internal
 */
class RequestTokenMiddleware implements MiddlewareInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    protected const COOKIE_PREFIX = 'typo3nonce_';
    protected const SECURE_PREFIX = '__Secure-';

    protected const ALLOWED_METHODS = ['POST', 'PUT', 'PATCH'];

    protected SecurityAspect $securityAspect;
    protected NoncePool $noncePool;

    public function __construct(Context $context)
    {
        $this->securityAspect = SecurityAspect::provideIn($context);
        $this->noncePool = $this->securityAspect->getNoncePool();
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // @todo some™ route handling mechanism might verify request-tokens (-> e.g. backend-routes, unsure for frontend)
        $this->noncePool->merge($this->resolveNoncePool($request))->purge();

        try {
            $this->securityAspect->setReceivedRequestToken($this->resolveReceivedRequestToken($request));
        } catch (RequestTokenException $exception) {
            // request token was given, but could not be verified
            $this->securityAspect->setReceivedRequestToken(false);
            $this->logger->debug('Could not resolve request token', ['exception' => $exception]);
        }

        $response = $handler->handle($request);
        return $this->enrichResponseWithCookie($request, $response);
    }

    protected function resolveNoncePool(ServerRequestInterface $request): NoncePool
    {
        $secure = $this->isHttps($request);
        // resolves cookie name dependent on whether TLS is used in request and uses `__Secure-` prefix,
        // see https://developer.mozilla.org/en-US/docs/Web/HTTP/Cookies#cookie_prefixes
        $securePrefix = $secure ? self::SECURE_PREFIX : '';
        $cookiePrefix = $securePrefix . self::COOKIE_PREFIX;
        $cookiePrefixLength = strlen($cookiePrefix);
        $cookies = array_filter(
            $request->getCookieParams(),
            static fn ($name) => is_string($name) && str_starts_with($name, $cookiePrefix),
            ARRAY_FILTER_USE_KEY
        );
        $items = [];
        foreach ($cookies as $name => $value) {
            $name = substr($name, $cookiePrefixLength);
            try {
                $items[$name] = Nonce::fromHashSignedJwt($value);
            } catch (NonceException $exception) {
                $this->logger->debug('Could not resolve received nonce', ['exception' => $exception]);
                $items[$name] = null;
            }
        }
        // @todo pool `$options` should be configurable via `$TYPO3_CONF_VARS`
        return GeneralUtility::makeInstance(NoncePool::class, $items);
    }

    /**
     * @throws RequestTokenException
     */
    protected function resolveReceivedRequestToken(ServerRequestInterface $request): ?RequestToken
    {
        $headerValue = $request->getHeaderLine(RequestToken::HEADER_NAME);
        $paramValue = (string)($request->getParsedBody()[RequestToken::PARAM_NAME] ?? '');
        if ($headerValue !== '') {
            $tokenValue = $headerValue;
        } elseif (in_array($request->getMethod(), self::ALLOWED_METHODS, true)) {
            $tokenValue = $paramValue;
        } else {
            $tokenValue = '';
        }
        if ($tokenValue === '') {
            return null;
        }
        return RequestToken::fromHashSignedJwt($tokenValue, $this->securityAspect->getSigningSecretResolver());
    }

    protected function enrichResponseWithCookie(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $secure = $this->isHttps($request);
        $normalizedParams = $request->getAttribute('normalizedParams');
        $path = $normalizedParams->getSitePath();
        $securePrefix = $secure ? self::SECURE_PREFIX : '';
        $cookiePrefix = $securePrefix . self::COOKIE_PREFIX;

        $createCookie = static fn (string $name, string $value, int $expire): Cookie => new Cookie(
            $name,
            $value,
            $expire,
            $path,
            null,
            $secure,
            true,
            false,
            Cookie::SAMESITE_STRICT
        );

        $cookies = [];
        // emit new nonce cookies
        foreach ($this->noncePool->getEmittableNonces() as $name => $nonce) {
            $cookies[] = $createCookie($cookiePrefix . $name, $nonce->toHashSignedJwt(), 0);
        }
        // revoke nonce cookies (exceeded pool size, expired or explicitly revoked)
        foreach ($this->noncePool->getRevocableNames() as $name) {
            $cookies[] = $createCookie($cookiePrefix . $name, '', -1);
        }
        // finally apply to response
        foreach ($cookies as $cookie) {
            $response = $response->withAddedHeader('Set-Cookie', (string)$cookie);
        }
        return $response;
    }

    protected function isHttps(ServerRequestInterface $request): bool
    {
        $normalizedParams = $request->getAttribute('normalizedParams');
        return $normalizedParams instanceof NormalizedParams && $normalizedParams->isHttps();
    }
}
