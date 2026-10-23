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
 * Responses are cached for a short period, so paginating and navigating back
 * and forth does not hammer TER, and a rate limit is not spent on repeating
 * the same question; see {@see AbstractJsonApiClient}.
 *
 * @internal This class is a specific implementation and is not part of the Public TYPO3 API.
 */
class TerApiClient extends AbstractJsonApiClient
{
    private const BASE_URL = 'https://extensions.typo3.org/api/v1';
    protected const UNAVAILABLE_CACHE_IDENTIFIER = 'ter_api_unavailable';

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

        $data = $this->request('/extension', $query);
        if ($data === null) {
            return new TerExtensionListResult(0);
        }
        return TerExtensionListResult::fromArray($data);
    }

    public function getExtension(string $extensionKey): ?TerExtension
    {
        $extensionKey = strtolower($extensionKey);
        $data = $this->request('/extension/' . rawurlencode($extensionKey));
        // The endpoint wraps the extension in an outer array: [{...}].
        $data = $this->unwrapEnvelope($data, 'key');
        return TerExtension::fromArray($data);
    }

    public function getVersions(string $extensionKey): array
    {
        $extensionKey = strtolower($extensionKey);
        $data = $this->request('/extension/' . rawurlencode($extensionKey) . '/versions');
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
    protected function request(string $path, array $query = []): ?array
    {
        $url = self::BASE_URL . $path;
        if ($query !== []) {
            $url .= '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
        }
        return $this->requestJson($url);
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
}
