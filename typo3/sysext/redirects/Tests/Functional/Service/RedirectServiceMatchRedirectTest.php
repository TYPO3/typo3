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

namespace TYPO3\CMS\Redirects\Tests\Functional\Service;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Redirects\Service\RedirectCacheService;
use TYPO3\CMS\Redirects\Service\RedirectService;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class RedirectServiceMatchRedirectTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['redirects'];

    protected array $configurationToUseInTestInstance = [
        'SYS' => [
            'caching' => [
                'cacheConfigurations' => [
                    'pages' => [
                        'backend' => Typo3DatabaseBackend::class,
                    ],
                ],
            ],
        ],
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/RedirectService_matchRedirect.csv');
    }

    public static function matchRedirectReturnsExpectedRedirectDataProvider(): \Generator
    {
        yield 'flat match' => ['acme.com', '/flat', '', 1];
        yield 'flat match ignores trailing slash of request' => ['acme.com', '/flat/', '', 1];
        yield 'flat match ignores trailing slash of source path' => ['acme.com', '/flat-with-slash', '', 2];
        yield 'flat match ignores multiple trailing slashes of source path' => ['acme.com', '/flat-multi-slash', '', 3];
        yield 'flat match ignores multiple trailing slashes of source path with slash request' => ['acme.com', '/flat-multi-slash/', '', 3];
        yield 'flat match is case-sensitive' => ['acme.com', '/case-sensitive', '', null];
        yield 'flat match with exact case' => ['acme.com', '/Case-Sensitive', '', 4];
        yield 'flat match with url encoded special characters' => ['acme.com', rawurlencode('/äöü-应用'), '', 24];
        yield 'flat match on root path' => ['acme.com', '/', '', 25];
        yield 'flat match on empty path' => ['acme.com', '', '', 25];
        yield 'flat match does not treat request path as LIKE pattern' => ['acme.com', '/f_o', '', null];
        yield 'flat match ignores regular expression redirects' => ['acme.com', '/regexp-ignored-as-flat', '', null];
        yield 'flat match lowest uid wins for same normalized path' => ['acme.com', '/first-uid-wins', '', 17];
        yield 'flat match lowest uid wins with multiple trailing slashes' => ['acme.com', '/mixed', '', 31];
        yield 'flat match lowest uid wins without multiple trailing slashes' => ['acme.com', '/mixed-reverse', '', 33];
        yield 'query parameter match is preferred over flat match' => ['acme.com', '/query', 'a=1', 5];
        yield 'query parameter mismatch falls back to flat match' => ['acme.com', '/query', 'a=2', 6];
        yield 'request without query uses flat match' => ['acme.com', '/query', '', 6];
        yield 'query parameter match with slash variant' => ['acme.com', '/query-slash', 'a=1', 7];
        yield 'query parameter match with slash variant and slash request' => ['acme.com', '/query-slash/', 'a=1', 7];
        yield 'query parameter match with leading question mark' => ['acme.com', '/query', '?a=1', 5];
        yield 'query parameter match requires all query parameters' => ['acme.com', '/partial', 'a=1&b=2', 37];
        yield 'partial query parameter match is not matched' => ['acme.com', '/partial', 'a=1', null];
        yield 'query parameter match with additional query parameter is not matched' => ['acme.com', '/partial', 'a=1&b=2&c=3', null];
        yield 'inactive exact query parameter match shadows slash variant' => ['acme.com', '/query-exact-inactive', 'a=1', null];
        yield 'query parameter match with literal brackets in source path' => ['acme.com', '/brackets', 'tx_myext%5Baction%5D=list', 35];
        yield 'query parameter match with encoded brackets in source path' => ['acme.com', '/encoded-brackets', 'tx_myext%5Baction%5D=list', 36];
        yield 'wildcard host query parameter match' => ['acme.com', '/wildcard-query', 'a=1', 27];
        yield 'wildcard host query parameter match for other host' => ['other.com', '/query', 'a=1', 8];
        yield 'host specific flat match is preferred over wildcard query parameter match' => ['acme.com', '/host-beats-wildcard', 'a=1', 9];
        yield 'empty source host is treated as wildcard' => ['acme.com', '/empty-host', '', 11];
        yield 'redirect of other host is not matched' => ['acme.com', '/only-other', '', null];
        yield 'redirect of other host is matched for that host' => ['other.com', '/only-other', '', 26];
        yield 'disabled, deleted, not yet started and expired redirects are skipped' => ['acme.com', '/inactive', '', 16];
        yield 'flat match is preferred over regular expression match' => ['acme.com', '/pattern-flat', '', 20];
        yield 'regular expression match' => ['acme.com', '/pattern-other', '', 19];
        yield 'regular expression with query parameters is preferred' => ['acme.com', '/rx-query', 'a=1', 22];
        yield 'regular expression without query parameters' => ['acme.com', '/rx-query', '', 21];
        yield 'no match' => ['acme.com', '/does-not-exist', '', null];
    }

    #[DataProvider('matchRedirectReturnsExpectedRedirectDataProvider')]
    #[Test]
    public function matchRedirectReturnsExpectedRedirect(string $domain, string $path, string $query, ?int $expectedUid): void
    {
        $result = $this->get(RedirectService::class)->matchRedirect($domain, $path, $query);
        self::assertSame($expectedUid, $result === null ? null : (int)$result['uid']);
    }

    public static function noRedirectQueryIsExecutedIfNoRedirectMatchesDataProvider(): \Generator
    {
        yield 'unknown path' => ['/does-not-exist', ''];
        yield 'unknown path with query' => ['/does-not-exist', 'a=1'];
        yield 'path of a redirect of another host' => ['/only-other', ''];
        yield 'differently cased path' => ['/case-sensitive', ''];
    }

    #[DataProvider('noRedirectQueryIsExecutedIfNoRedirectMatchesDataProvider')]
    #[Test]
    public function noRedirectQueryIsExecutedIfNoRedirectMatches(string $path, string $query): void
    {
        $subject = $this->get(RedirectService::class);
        // Warm up the redirect caches of both hosts
        $subject->matchRedirect('acme.com', '/does-not-exist');
        $schemaManager = $this->get(ConnectionPool::class)->getConnectionForTable('sys_redirect')->createSchemaManager();
        // Any query on sys_redirect fails while the table is renamed
        $schemaManager->renameTable('sys_redirect', 'sys_redirect_unavailable');
        try {
            $result = $subject->matchRedirect('acme.com', $path, $query);
        } finally {
            $schemaManager->renameTable('sys_redirect_unavailable', 'sys_redirect');
        }
        self::assertNull($result);
    }

    #[Test]
    public function matchedRedirectDoesNotContainDescription(): void
    {
        $result = $this->get(RedirectService::class)->matchRedirect('acme.com', '/flat');
        self::assertIsArray($result);
        self::assertArrayNotHasKey('description', $result);
    }

    #[Test]
    public function redirectCacheOnlyContainsRedirectsWhichCannotBeLookedUpByIndex(): void
    {
        $this->get(RedirectService::class)->matchRedirect('acme.com', '/flat');
        $redirects = $this->get(RedirectCacheService::class)->getRedirects('acme.com');
        self::assertEqualsCanonicalizing(['flat', 'regexp_query_parameters', 'regexp_flat', 'source_path_filter'], array_keys($redirects));
        self::assertSame(['/flat-multi-slash/', '/fxo/', '/mixed/', '/mixed-reverse/'], array_keys($redirects['flat']));
        self::assertSame(['#^/rx-query\?a=1$#'], array_keys($redirects['regexp_query_parameters']));
        self::assertSame(
            ['#^/pattern-.*$#', '#^/rx-query#', '/regexp-ignored-as-flat'],
            array_keys($redirects['regexp_flat'])
        );
    }

    public static function redirectCreatedAfterCacheWasBuiltIsMatchedDataProvider(): \Generator
    {
        yield 'host specific flat redirect' => ['acme.com', '/created', 0, '/created', ''];
        yield 'wildcard flat redirect' => ['*', '/created', 0, '/created', ''];
        yield 'host specific redirect respecting query parameters' => ['acme.com', '/created?a=1', 1, '/created', 'a=1'];
        yield 'wildcard redirect respecting query parameters' => ['*', '/created?a=1', 1, '/created', 'a=1'];
    }

    #[DataProvider('redirectCreatedAfterCacheWasBuiltIsMatchedDataProvider')]
    #[Test]
    public function redirectCreatedAfterCacheWasBuiltIsMatched(string $sourceHost, string $sourcePath, int $respectQueryParameters, string $path, string $query): void
    {
        $subject = $this->get(RedirectService::class);
        self::assertNull($subject->matchRedirect('acme.com', $path, $query));
        $dataHandler = $this->processDataMap([
            'sys_redirect' => [
                'NEW1' => [
                    'pid' => 0,
                    'source_host' => $sourceHost,
                    'source_path' => $sourcePath,
                    'respect_query_parameters' => $respectQueryParameters,
                    'target' => 'https://acme.com/target/created',
                ],
            ],
        ]);
        self::assertSame($dataHandler->substNEWwithIDs['NEW1'], (int)($subject->matchRedirect('acme.com', $path, $query)['uid'] ?? 0));
    }

    #[Test]
    public function redirectWithChangedSourcePathIsMatchedByNewSourcePath(): void
    {
        $subject = $this->get(RedirectService::class);
        self::assertNull($subject->matchRedirect('acme.com', '/flat-renamed'));
        $this->processDataMap(['sys_redirect' => [1 => ['source_path' => '/flat-renamed']]]);
        self::assertSame(1, (int)($subject->matchRedirect('acme.com', '/flat-renamed')['uid'] ?? 0));
        self::assertNull($subject->matchRedirect('acme.com', '/flat'));
    }

    #[Test]
    public function redirectWithChangedSourceHostIsMatchedForNewSourceHost(): void
    {
        $subject = $this->get(RedirectService::class);
        self::assertNull($subject->matchRedirect('acme.com', '/only-other'));
        $this->processDataMap(['sys_redirect' => [26 => ['source_host' => 'acme.com']]]);
        self::assertSame(26, (int)($subject->matchRedirect('acme.com', '/only-other')['uid'] ?? 0));
        self::assertNull($subject->matchRedirect('other.com', '/only-other'));
    }

    #[Test]
    public function deletedRedirectIsNotMatchedAnymore(): void
    {
        $subject = $this->get(RedirectService::class);
        self::assertSame(1, (int)($subject->matchRedirect('acme.com', '/flat')['uid'] ?? 0));
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');
        $this->setUpBackendUser(1);
        $dataHandler = $this->get(DataHandler::class);
        $dataHandler->start([], ['sys_redirect' => [1 => ['delete' => 1]]]);
        $dataHandler->process_cmdmap();
        self::assertSame([], $dataHandler->errorLog);
        self::assertNull($subject->matchRedirect('acme.com', '/flat'));
    }

    #[Test]
    public function rebuildingCacheForEmptySourceHostUpdatesWildcardRedirects(): void
    {
        $subject = $this->get(RedirectService::class);
        self::assertNull($subject->matchRedirect('acme.com', '/created-empty-host'));
        $this->get(ConnectionPool::class)->getConnectionForTable('sys_redirect')->insert('sys_redirect', [
            'uid' => 100,
            'source_host' => '',
            'source_path' => '/created-empty-host',
            'target' => 'https://acme.com/target/100',
        ]);
        $this->get(RedirectCacheService::class)->rebuildForHost('');
        self::assertSame(100, (int)($subject->matchRedirect('acme.com', '/created-empty-host')['uid'] ?? 0));
    }

    private function processDataMap(array $dataMap): DataHandler
    {
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');
        $this->setUpBackendUser(1);
        $dataHandler = $this->get(DataHandler::class);
        $dataHandler->start($dataMap, []);
        $dataHandler->process_datamap();
        self::assertSame([], $dataHandler->errorLog);
        return $dataHandler;
    }
}
