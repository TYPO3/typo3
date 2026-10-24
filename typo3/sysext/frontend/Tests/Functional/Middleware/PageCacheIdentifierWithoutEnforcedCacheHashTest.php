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

namespace TYPO3\CMS\Frontend\Tests\Functional\Middleware;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend;
use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Frontend\Page\CacheHashCalculator;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class PageCacheIdentifierWithoutEnforcedCacheHashTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;

    protected const array LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8'],
    ];

    protected array $configurationToUseInTestInstance = [
        'FE' => [
            'cacheHash' => [
                'enforceValidation' => false,
            ],
        ],
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
        $this->importCSVDataSet(__DIR__ . '/Fixtures/typoScriptFrontendInitializationCases.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/typoScriptFrontendInitializationSysTemplate.csv');
        $this->writeSiteConfiguration(
            'acme-com',
            $this->buildSiteConfiguration(1, 'https://acme.com/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/'),
            ]
        );
    }

    public static function unvalidatedCacheHashDoesNotCreatePageCacheEntriesDataProvider(): array
    {
        return [
            'array cHash' => ['cHash%5B%5D=1', 'cHash%5B%5D=2'],
            'array cHash with argument' => ['cHash%5B%5D=&foo=1', 'cHash%5B%5D=&foo=2'],
            'array cHash with key' => ['cHash%5Bkey%5D=1', 'cHash%5Bkey%5D=2'],
            'empty cHash with argument' => ['cHash=&foo=1', 'cHash=&foo=2'],
        ];
    }

    #[DataProvider('unvalidatedCacheHashDoesNotCreatePageCacheEntriesDataProvider')]
    #[Test]
    public function unvalidatedCacheHashDoesNotCreatePageCacheEntries(string $firstQuery, string $secondQuery): void
    {
        $this->executeFrontendSubRequest(new InternalRequest('https://acme.com/?id=9'));
        $first = $this->executeFrontendSubRequest(new InternalRequest('https://acme.com/?id=9&' . $firstQuery));
        $second = $this->executeFrontendSubRequest(new InternalRequest('https://acme.com/?id=9&' . $secondQuery));

        self::assertSame(200, $first->getStatusCode());
        self::assertSame(200, $second->getStatusCode());
        self::assertSame(1, $this->countPageCacheEntries());
    }

    #[Test]
    public function validCacheHashCreatesPageCacheEntry(): void
    {
        $cHash = $this->get(CacheHashCalculator::class)->generateForParameters('&id=9&foo=1');

        $this->executeFrontendSubRequest(new InternalRequest('https://acme.com/?id=9'));
        $response = $this->executeFrontendSubRequest(new InternalRequest('https://acme.com/?id=9&foo=1&cHash=' . $cHash));

        self::assertSame(200, $response->getStatusCode());
        self::assertSame(2, $this->countPageCacheEntries());
    }

    private function countPageCacheEntries(): int
    {
        return $this->getConnectionPool()->getConnectionForTable('cache_pages')->count('*', 'cache_pages', []);
    }
}
