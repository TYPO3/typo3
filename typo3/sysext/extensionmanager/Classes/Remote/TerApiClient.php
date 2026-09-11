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
use TYPO3\CMS\Extensionmanager\Remote\Dto\TerExtension;
use TYPO3\CMS\Extensionmanager\Remote\Dto\TerExtensionListResult;
use TYPO3\CMS\Extensionmanager\Remote\Dto\TerExtensionVersion;

/**
 * Connector to the TER REST API at https://extensions.typo3.org/api/v1.
 *
 * Reads the catalogue live, without the local mirror of extensions.xml.gz.
 * Downloading is not its business: an extension is fetched from the archive
 * TER stores for it, which {@see TerExtensionRemote} already does.
 *
 * Responses are cached for a short period in the "extensionmanager_ter_api"
 * cache, so paginating and navigating back and forth does not hammer TER, and
 * a rate limit is not spent on repeating the same question. A remote that
 * could not answer is remembered for a few minutes as well, so an offline
 * installation does not wait for the timeout on every question it asks.
 *
 * @internal This class is a specific implementation and is not part of the Public TYPO3 API.
 */
class TerApiClient
{
    private const BASE_URL = 'https://extensions.typo3.org/api/v1';
    private const UNAVAILABLE_CACHE_IDENTIFIER = 'ter_api_unavailable';
    private const UNAVAILABLE_LIFETIME = 300;

    public function __construct(
        protected readonly RequestFactory $requestFactory,
        protected readonly FrontendInterface $cache,
        protected readonly Typo3Version $typo3Version,
    ) {}

    public function query(
        string $search,
        int $typo3MajorVersion,
        int $page = 1,
        int $perPage = 20,
        ?string $category = null,
        ?string $username = null,
    ): TerExtensionListResult {
        $query = [
            'page' => max(1, $page),
            'per_page' => max(1, min(50, $perPage)),
        ];
        $filter = [];
        // A major version <= 0 means "do not restrict by TYPO3 version".
        if ($typo3MajorVersion > 0) {
            $filter['typo3_version'] = $typo3MajorVersion;
        }
        $search = trim($search);
        if ($search !== '') {
            $filter['search'] = $search;
        }
        if ($category !== null && $category !== '') {
            $filter['category'] = $category;
        }
        if ($username !== null && $username !== '') {
            $filter['username'] = $username;
        }
        if ($filter !== []) {
            $query['filter'] = $filter;
        }

        $data = $this->requestJson('/extension', $query);
        if ($data === null) {
            return new TerExtensionListResult(0);
        }
        return TerExtensionListResult::fromArray($data);
    }

    public function getExtension(string $extensionKey): ?TerExtension
    {
        $extensionKey = strtolower($extensionKey);
        $data = $this->requestJson('/extension/' . rawurlencode($extensionKey));
        // The endpoint wraps the extension in an outer array: [{...}].
        $data = $this->unwrapEnvelope($data, 'key');
        return TerExtension::fromArray($data);
    }

    public function getVersions(string $extensionKey): array
    {
        $extensionKey = strtolower($extensionKey);
        $data = $this->requestJson('/extension/' . rawurlencode($extensionKey) . '/versions');
        if (!is_array($data)) {
            return [];
        }
        // The endpoint wraps the list of versions in an outer array: [[{...}, {...}]].
        if (count($data) === 1 && isset($data[0]) && is_array($data[0]) && !isset($data[0]['number'])) {
            $data = $data[0];
        }
        $versions = [];
        foreach ($data as $versionData) {
            $version = TerExtensionVersion::fromArray(is_array($versionData) ? $versionData : null);
            if ($version !== null) {
                $versions[] = $version;
            }
        }
        usort($versions, static fn(TerExtensionVersion $a, TerExtensionVersion $b): int => version_compare($b->number, $a->number));
        return $versions;
    }

    /**
     * @param array<string, mixed> $query
     * @return array<mixed>|null null when the remote does not know the resource
     * @throws ApiRequestFailedException when the remote could not answer at all
     */
    protected function requestJson(string $path, array $query = []): ?array
    {
        $url = self::BASE_URL . $path;
        if ($query !== []) {
            $url .= '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
        }

        $cacheIdentifier = 'ter_api_' . hash('xxh3', $url);
        $cached = $this->cache->get($cacheIdentifier);
        if (is_array($cached)) {
            return $cached;
        }
        if ($this->cache->has(self::UNAVAILABLE_CACHE_IDENTIFIER)) {
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
        $this->cache->set(self::UNAVAILABLE_CACHE_IDENTIFIER, true, [], self::UNAVAILABLE_LIFETIME);
        return $exception;
    }

    /**
     * Unwraps the single-element outer array the API uses to envelope single
     * resources, e.g. [{...}] becomes {...}. Recognized by the presence of the
     * given discriminator key on the inner element.
     */
    protected function unwrapEnvelope(?array $data, string $discriminatorKey): ?array
    {
        if (!is_array($data)) {
            return null;
        }
        if (isset($data[0]) && is_array($data[0]) && !isset($data[$discriminatorKey])) {
            return $data[0];
        }
        return $data;
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
