<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Seo\Tests\Functional\XmlSitemap;

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

use TYPO3\CMS\Frontend\Tests\Functional\SiteHandling\AbstractTestCase;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalResponse;

/**
 * Contains functional tests for the XmlSitemap Index
 */
class XmlSitemapPagesTest extends AbstractTestCase
{

    /**
     * @var array
     */
    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
        'FR' => ['id' => 1, 'title' => 'French', 'locale' => 'fr_FR.UTF8', 'iso' => 'fr', 'hrefLang' => 'fr-FR', 'direction' => ''],
        'DE' => ['id' => 2, 'title' => 'German', 'locale' => 'de_DE.UTF8', 'iso' => 'de', 'hrefLang' => 'de-DE', 'direction' => ''],
    ];

    /**
     * @var string[]
     */
    protected $coreExtensionsToLoad = [
        'core', 'frontend', 'seo'
    ];

    /**
     * @var string
     */
    protected $body;

    protected function setUp(): void
    {
        parent::setUp();
        $this->importDataSet('EXT:seo/Tests/Functional/Fixtures/pages-sitemap.xml');
        $this->setUpFrontendRootPage(
            1,
            [
                'constants' => ['EXT:seo/Configuration/TypoScript/XmlSitemap/constants.typoscript'],
                'setup' => ['EXT:seo/Configuration/TypoScript/XmlSitemap/setup.typoscript']
            ]
        );

        $this->writeSiteConfiguration(
            'website-local',
            $this->buildSiteConfiguration(1, 'http://localhost/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/'),
                $this->buildLanguageConfiguration('FR', '/fr/'),
                $this->buildLanguageConfiguration('DE', '/de/', ['FR'])
            ]
        );
    }

    /**
     * @param string $urlPattern
     * @test
     * @dataProvider pagesToCheckDataProvider
     */
    public function checkIfPagesSiteMapContainsExpectedEntries($urlPattern): void
    {
        $response = $this->getResponse();
        self::assertEquals(200, $response->getStatusCode());
        self::assertArrayHasKey('Content-Length', $response->getHeaders());
        self::assertGreaterThan(0, $response->getHeader('Content-Length')[0]);
        self::assertArrayHasKey('Content-Type', $response->getHeaders());
        self::assertEquals('application/xml;charset=utf-8', $response->getHeader('Content-Type')[0]);
        self::assertArrayHasKey('X-Robots-Tag', $response->getHeaders());
        self::assertEquals('noindex', $response->getHeader('X-Robots-Tag')[0]);

        self::assertRegExp($urlPattern, (string)$response->getBody());
    }

    /**
     * @test
     */
    public function pagesSitemapDoesNotContainUrlWithCanonicalSet(): void
    {
        self::assertStringNotContainsString(
            '<loc>http://localhost/canonicalized-page</loc>',
            (string)$this->getResponse()->getBody()
        );
    }

    /**
     * @return array
     */
    public function pagesToCheckDataProvider(): array //18-03-2019 21:24:07
    {
        return [
            'complete-entry' => ['#<url>\s+<loc>http://localhost/complete\-entry</loc>\s+<lastmod>\d+-\d+-\d+T\d+:\d+:\d+\+\d+:\d+</lastmod>\s+<changefreq>daily</changefreq>\s+<priority>0\.7</priority>\s+</url>#'],
            'only-changefreq' => ['#<url>\s+<loc>http://localhost/only\-changefreq</loc>\s+<lastmod>\d+-\d+-\d+T\d+:\d+:\d+\+\d+:\d+</lastmod>\s+<changefreq>weekly</changefreq>\s+<priority>0\.5</priority>\s+</url>#'],
            'clean' => ['#<url>\s+<loc>http://localhost/clean</loc>\s+<lastmod>\d+-\d+-\d+T\d+:\d+:\d+\+\d+:\d+</lastmod>\s+<priority>0\.5</priority>\s+</url>#'],
        ];
    }

    /**
     * @test
     */
    public function pagesSitemapContainsTranslatedPages(): void
    {
        $xml = new \SimpleXMLElement((string)$this->getResponse('http://localhost/fr/')->getBody());
        self::assertEquals(3, $xml->count());
    }

    /**
     * @test
     */
    public function pagesSitemapDoesNotContainsUntranslatedPages(): void
    {
        self::assertStringNotContainsString(
            '<loc>http://localhost/dummy-1-4</loc>',
            (string)$this->getResponse('http://localhost/fr/')->getBody()
        );
    }

    /**
     * @test
     */
    public function pagesSitemapRespectFallbackStrategy(): void
    {
        $xml = new \SimpleXMLElement((string)$this->getResponse('http://localhost/de/')->getBody());
        self::assertEquals(4, $xml->count());
    }

    protected function getResponse(string $uri = 'http://localhost/'): InternalResponse
    {
        return $this->executeFrontendRequest(
            (new InternalRequest($uri))->withQueryParameters([
                'id' => 1,
                'type' => 1533906435,
                'sitemap' => 'pages'
            ])
        );
    }
}
