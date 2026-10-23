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

namespace TYPO3\CMS\Extensionmanager\Remote;

use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Information\Typo3Version;

/**
 * What the clients of TER's JSON interfaces share: one GET for a JSON
 * document, cached in the "extensionmanager_ter_api" cache so repeating a
 * question does not cost a request - that includes the answer that an
 * extension is unknown, which is what TER says about most of the extensions
 * installed locally - and a short memory of the remote not
 * answering, so an offline installation does not wait for the timeout on
 * every question it asks.
 *
 * @internal This class is a specific implementation and is not part of the Public TYPO3 API.
 */
abstract class AbstractJsonApiClient
{
    protected const UNAVAILABLE_CACHE_IDENTIFIER = 'remote_api_unavailable';
    protected const UNAVAILABLE_LIFETIME = 300;
    private const NOT_FOUND = 'not_found';
    private const NOT_FOUND_LIFETIME = 300;

    public function __construct(
        protected readonly RequestFactory $requestFactory,
        protected readonly FrontendInterface $cache,
        protected readonly Typo3Version $typo3Version,
    ) {}

    /**
     * @return array<mixed>|null null when the remote does not know the resource
     * @throws ApiRequestFailedException when the remote could not answer at all
     */
    protected function requestJson(string $url): ?array
    {
        $cacheIdentifier = 'remote_api_' . hash('xxh3', $url);
        $cached = $this->cache->get($cacheIdentifier);
        if (is_array($cached)) {
            return $cached;
        }
        if ($cached === self::NOT_FOUND) {
            return null;
        }
        if ($this->cache->has(static::UNAVAILABLE_CACHE_IDENTIFIER)) {
            throw new ApiRequestFailedException('"' . $url . '" is not asked, the remote could not answer a moment ago', 1791628922);
        }

        try {
            $response = $this->requestFactory->request($url, 'GET', $this->getRequestOptions());
        } catch (\Throwable $e) {
            throw $this->rememberFailure(new ApiRequestFailedException('Could not reach "' . $url . '": ' . $e->getMessage(), 1789035174, $e));
        }
        // "Unknown to this remote" is an answer, not a failure, and the callers
        // turn it into null or an empty list themselves.
        if ($response->getStatusCode() === 404) {
            $this->cache->set($cacheIdentifier, self::NOT_FOUND, [], self::NOT_FOUND_LIFETIME);
            return null;
        }
        if (!$this->isSuccessfulResponse($response)) {
            throw $this->rememberFailure(new ApiRequestFailedException('"' . $url . '" answered with status ' . $response->getStatusCode(), 1789035175));
        }
        $decoded = json_decode($response->getBody()->getContents(), true);
        if (!is_array($decoded)) {
            throw $this->rememberFailure(new ApiRequestFailedException('"' . $url . '" did not answer with a JSON structure', 1789035176));
        }
        $this->cache->set($cacheIdentifier, $decoded);
        return $decoded;
    }

    private function rememberFailure(ApiRequestFailedException $exception): ApiRequestFailedException
    {
        $this->cache->set(static::UNAVAILABLE_CACHE_IDENTIFIER, true, [], static::UNAVAILABLE_LIFETIME);
        return $exception;
    }

    protected function isSuccessfulResponse(ResponseInterface $response): bool
    {
        $statusCode = $response->getStatusCode();
        return $statusCode >= 200 && $statusCode < 300;
    }

    /**
     * Error statuses are inspected here rather than thrown by the HTTP client,
     * since a 404 is an answer. The timeouts are deliberately short: the imported
     * list answers when TER is slow, which beats leaving the module hanging.
     *
     * @return array<string, mixed>
     */
    protected function getRequestOptions(): array
    {
        return [
            'headers' => [
                'Accept' => 'application/json',
                'User-Agent' => 'TYPO3/' . $this->typo3Version->getVersion(),
            ],
            'http_errors' => false,
            'connect_timeout' => 5,
            'timeout' => 10,
        ];
    }
}
