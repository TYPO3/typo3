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

namespace TYPO3\CMS\Extensionmanager\Tests\Functional\Fixtures;

use GuzzleHttp\Promise\Create;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use TYPO3\CMS\Core\Cache\Backend\TransientMemoryBackend;
use TYPO3\CMS\Core\Cache\Frontend\VariableFrontend;

/**
 * Answers the requests to the TER REST API from a list of extensions, as a
 * Guzzle handler middleware, so no test talks to extensions.typo3.org.
 *
 * Extensions are given as key => list of versions, each version in the shape
 * of the "version" schema of the API. Every other request is answered with a
 * 404, and with $unavailable set, every request fails with a 503.
 * $composerNames maps an extension key to its composer name, which the
 * search filter matches, as it does the extension key.
 */
final class TerApiFake
{
    private const BASE_PATH = '/api/v1/extension';

    /**
     * @param array<string, list<array<string, mixed>>> $extensions
     * @param array<string, string> $composerNames
     */
    public static function register(array $extensions, bool $unavailable = false, array $composerNames = []): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['HTTP']['handler']['ter_api_fake'] = static fn(): \Closure => static function (RequestInterface $request) use ($extensions, $unavailable, $composerNames) {
            return Create::promiseFor(self::answer($request, $extensions, $unavailable, $composerNames));
        };
    }

    /**
     * Cache configuration for the test instance, so responses of one test are
     * not answered from the cache in the next one.
     */
    public static function cacheConfiguration(): array
    {
        return [
            'SYS' => [
                'caching' => [
                    'cacheConfigurations' => [
                        'extensionmanager_ter_api' => [
                            'frontend' => VariableFrontend::class,
                            'backend' => TransientMemoryBackend::class,
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @param array<string, list<array<string, mixed>>> $extensions
     * @param array<string, string> $composerNames
     */
    private static function answer(RequestInterface $request, array $extensions, bool $unavailable, array $composerNames): Response
    {
        if ($unavailable) {
            return new Response(503);
        }
        $path = $request->getUri()->getPath();
        if ($path === self::BASE_PATH) {
            parse_str($request->getUri()->getQuery(), $query);
            $search = (string)($query['filter']['search'] ?? '');
            $found = [];
            foreach ($extensions as $extensionKey => $versions) {
                $composerName = $composerNames[$extensionKey] ?? '';
                if ($search !== '' && (str_contains($extensionKey, $search) || str_contains($composerName, $search))) {
                    $found[] = self::extension($extensionKey, self::completeVersions($versions), $composerName);
                }
            }
            return self::json(['results' => count($found), 'extensions' => $found]);
        }
        if (!preg_match('#^' . self::BASE_PATH . '/([^/]+)(/versions)?$#', $path, $matches)) {
            return new Response(404);
        }
        $versions = $extensions[rawurldecode($matches[1])] ?? null;
        if ($versions === null) {
            return new Response(404);
        }
        $versions = self::completeVersions($versions);
        if (($matches[2] ?? '') === '/versions') {
            return self::json([$versions]);
        }
        $extensionKey = rawurldecode($matches[1]);
        return self::json([self::extension($extensionKey, $versions, $composerNames[$extensionKey] ?? '')]);
    }

    /**
     * @param list<array<string, mixed>> $versions
     * @return list<array<string, mixed>>
     */
    private static function completeVersions(array $versions): array
    {
        return array_map(
            static fn(array $version): array => $version + ['review_state' => 0, 'state' => 'stable', 'category' => 'misc'],
            $versions
        );
    }

    /**
     * @param list<array<string, mixed>> $versions
     */
    private static function extension(string $extensionKey, array $versions, string $composerName): array
    {
        usort($versions, static fn(array $a, array $b): int => version_compare($b['number'], $a['number']));
        return [
            'key' => $extensionKey,
            'owner' => 'some-user',
            'meta' => ['composer_name' => $composerName],
            'current_version' => $versions[0],
        ];
    }

    private static function json(array $data): Response
    {
        return new Response(200, ['Content-Type' => 'application/json'], json_encode($data, JSON_THROW_ON_ERROR));
    }
}
