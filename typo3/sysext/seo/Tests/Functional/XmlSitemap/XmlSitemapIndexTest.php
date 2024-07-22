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

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Frontend\Tests\Functional\SiteHandling\AbstractTestCase;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;

/**
 * Contains functional tests for the XmlSitemap Index
 */
final class XmlSitemapIndexTest extends AbstractTestCase
{
    protected array $coreExtensionsToLoad = ['seo'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/pages-sitemap.csv');
        $this->setUpFrontendRootPage(
            1,
            [
                'constants' => ['EXT:seo/Configuration/TypoScript/XmlSitemap/constants.typoscript'],
                'setup' => ['EXT:seo/Configuration/TypoScript/XmlSitemap/setup.typoscript'],
            ]
        );
    }

    #[Test]
    public function checkIfSiteMapIndexContainsPagesSitemap(): void
    {
        $this->writeSiteConfiguration(
            'website-local',
            $this->buildSiteConfiguration(1, 'http://localhost/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/'),
            ]
        );

        $response = $this->executeFrontendSubRequest(
            (new InternalRequest('http://localhost/'))->withQueryParameters([
                'id' => 1,
                'type' => 1533906435,
            ])
        );

        self::assertEquals(200, $response->getStatusCode());
        self::assertArrayHasKey('Content-Length', $response->getHeaders());
        self::assertGreaterThan(0, $response->getHeader('Content-Length')[0]);
        self::assertArrayHasKey('Content-Type', $response->getHeaders());
        self::assertEquals('application/xml;charset=utf-8', $response->getHeader('Content-Type')[0]);
        self::assertArrayHasKey('X-Robots-Tag', $response->getHeaders());
        self::assertEquals('noindex', $response->getHeader('X-Robots-Tag')[0]);
        self::assertMatchesRegularExpression('/<loc>http:\/\/localhost\/\?tx_seo%5Bsitemap%5D=pages&amp;type=1533906435&amp;cHash=[^<]+<\/loc>/', (string)$response->getBody());
    }
}
