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
     * @var string[]
     */
    protected $coreExtensionsToLoad = [
        'core', 'frontend', 'seo'
    ];

    /**
     * @var string
     */
    protected $body;

    /**
     * @var InternalResponse
     */
    protected $response;

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
                $this->buildDefaultLanguageConfiguration('EN', '/')
            ]
        );

        $this->response = $this->executeFrontendRequest(
            (new InternalRequest('http://localhost/'))->withQueryParameters([
                'id' => 1,
                'type' => 1533906435,
                'sitemap' => 'pages'
            ])
        );
    }

    /**
     * @param string $urlPattern
     * @test
     * @dataProvider pagesToCheckDataProvider
     */
    public function checkIfPagesSiteMapContainsExpectedEntries($urlPattern): void
    {
        self::assertEquals(200, $this->response->getStatusCode());
        self::assertArrayHasKey('Content-Length', $this->response->getHeaders());
        self::assertGreaterThan(0, $this->response->getHeader('Content-Length')[0]);

        self::assertRegExp($urlPattern, (string)$this->response->getBody());
    }

    /**
     * @test
     */
    public function pagesSitemapDoesNotContainUrlWithCanonicalSet(): void
    {
        self::assertStringNotContainsString(
            '<loc>http://localhost/canonicalized-page</loc>',
            (string)$this->response->getBody()
        );
    }

    /**
     * @return array
     */
    public function pagesToCheckDataProvider(): array //18-03-2019 21:24:07
    {
        return [
            'complete-entry' => ['/<url>\s+<loc>http:\/\/localhost\/complete\-entry<\/loc>\s+<lastmod>2017-04-10T08:00:00\+00:00<\/lastmod>\s+<changefreq>daily<\/changefreq>\s+<priority>0\.7<\/priority>\s+<\/url>/'],
            'only-changefreq' => ['/<url>\s+<loc>http:\/\/localhost\/only\-changefreq<\/loc>\s+<lastmod>2017-04-10T08:00:00\+00:00<\/lastmod>\s+<changefreq>weekly<\/changefreq>\s+<priority>0\.5<\/priority>\s+<\/url>/'],
            'clean' => ['/<url>\s+<loc>http:\/\/localhost\/clean<\/loc>\s+<lastmod>2017-04-10T08:00:00\+00:00<\/lastmod>\s+<priority>0\.5<\/priority>\s+<\/url>/'],
        ];
    }
}
