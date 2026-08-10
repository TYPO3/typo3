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

namespace TYPO3\CMS\Seo\Tests\Functional\XmlSitemap;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Frontend\Tests\Functional\SiteHandling\AbstractTestCase;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;

/**
 * Data providers shipped by extensions: Providers implementing XmlSitemapDataProviderInterface are
 * resolved as services, while providers based on the deprecated AbstractXmlSitemapDataProvider are
 * still instantiated with their runtime arguments.
 */
final class XmlSitemapExtensionDataProviderTest extends AbstractTestCase
{
    protected array $coreExtensionsToLoad = ['seo'];

    protected array $testExtensionsToLoad = [
        'typo3/sysext/seo/Tests/Functional/Fixtures/Extensions/test_sitemap_legacy_provider',
    ];

    protected array $configurationToUseInTestInstance = [
        'FE' => [
            'cacheHash' => [
                'enforceValidation' => false,
            ],
        ],
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/pages-sitemap.csv');
        $this->setUpFrontendRootPage(
            1,
            [
                'constants' => ['EXT:seo/Configuration/TypoScript/XmlSitemap/constants.typoscript'],
                'setup' => [
                    'EXT:seo/Configuration/TypoScript/XmlSitemap/setup.typoscript',
                    'EXT:seo/Tests/Functional/Fixtures/extension-providers.typoscript',
                ],
            ]
        );
        $this->writeSiteConfiguration(
            'website-local',
            $this->buildSiteConfiguration(1, 'http://localhost/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/'),
            ]
        );
    }

    #[Test]
    public function dataProviderRegisteredAsServiceBuildsSitemap(): void
    {
        $content = $this->fetchSitemap('modern');

        self::assertStringContainsString('<loc>http://localhost/?tx_test%5Bitem%5D=first&amp;tx_test%5Blanguage%5D=0', $content);
        self::assertStringContainsString('<loc>http://localhost/?tx_test%5Bitem%5D=second&amp;tx_test%5Blanguage%5D=0', $content);
        self::assertStringNotContainsString('tx_test%5Bitem%5D=third', $content);
    }

    #[Test]
    public function dataProviderRegisteredAsServiceRespectsRequestedPage(): void
    {
        $content = $this->fetchSitemap('modern', 1);

        self::assertStringContainsString('tx_test%5Bitem%5D=third', $content);
        self::assertStringNotContainsString('tx_test%5Bitem%5D=first', $content);
    }

    public static function deprecatedDataProviderDataProvider(): \Generator
    {
        yield 'extending the deprecated abstract' => ['legacy'];
        yield 'extending the deprecated abstract and declaring the interface' => ['legacy_declaring_interface'];
    }

    /**
     * @todo Remove together with AbstractXmlSitemapDataProvider in TYPO3 v16.0
     */
    #[DataProvider('deprecatedDataProviderDataProvider')]
    #[IgnoreDeprecations]
    #[Test]
    public function deprecatedDataProviderBuildsSitemap(string $sitemap): void
    {
        $content = $this->fetchSitemap($sitemap);

        self::assertStringContainsString('<loc>http://localhost/?tx_test%5Bitem%5D=first', $content);
        self::assertStringContainsString('<loc>http://localhost/?tx_test%5Bitem%5D=second', $content);
        self::assertStringNotContainsString('tx_test%5Bitem%5D=third', $content);
    }

    /**
     * @todo Adapt to non-deprecated providers only, once AbstractXmlSitemapDataProvider is removed in TYPO3 v16.0
     */
    #[IgnoreDeprecations]
    #[Test]
    public function sitemapIndexContainsAllPagesOfExtensionDataProviders(): void
    {
        $response = $this->executeFrontendSubRequest(
            new InternalRequest('http://localhost/')->withQueryParameters(['type' => 1533906435])
        );
        self::assertSame(200, $response->getStatusCode());
        $content = (string)$response->getBody();

        foreach (['legacy', 'legacy_declaring_interface', 'modern'] as $sitemap) {
            self::assertStringContainsString('tx_seo%5Bpage%5D=1&amp;tx_seo%5Bsitemap%5D=' . $sitemap . '&amp;', $content);
        }
    }

    private function fetchSitemap(string $sitemap, int $page = 0): string
    {
        $queryParameters = [
            'type' => 1533906435,
            'tx_seo[sitemap]' => $sitemap,
        ];
        if ($page > 0) {
            $queryParameters['tx_seo[page]'] = $page;
        }
        $response = $this->executeFrontendSubRequest(
            new InternalRequest('http://localhost/')->withQueryParameters($queryParameters)
        );
        self::assertSame(200, $response->getStatusCode());
        return (string)$response->getBody();
    }
}
