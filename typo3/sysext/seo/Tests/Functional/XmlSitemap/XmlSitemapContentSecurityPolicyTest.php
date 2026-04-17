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
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Directive;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\MutationMode;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\SourceKeyword;
use TYPO3\CMS\Frontend\Tests\Functional\SiteHandling\AbstractTestCase;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;

/**
 * The XSL stylesheet of a sitemap is referenced by URL and evaluated against the
 * `script-src` family, which must not be invalidated by `'strict-dynamic'`.
 */
final class XmlSitemapContentSecurityPolicyTest extends AbstractTestCase
{
    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8'],
    ];

    protected array $coreExtensionsToLoad = ['seo'];

    protected array $configurationToUseInTestInstance = [
        'SYS' => [
            'features' => [
                'security.frontend.enforceContentSecurityPolicy' => true,
            ],
        ],
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
        $this->setUpSitemapRootPage();
        // note: a dedicated site identifier is used, since site related state of other test
        // cases in this test suite would leak into this one otherwise
        $this->writeSiteConfiguration(
            'website-csp',
            $this->buildSiteConfiguration(1, 'http://localhost/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/'),
            ],
            [],
            [],
            [
                'enforce' => [
                    'inheritDefault' => true,
                    'mutations' => [
                        [
                            'mode' => MutationMode::Extend->value,
                            'directive' => Directive::ScriptSrc->value,
                            'sources' => ["'" . SourceKeyword::strictDynamic->value . "'"],
                        ],
                        [
                            'mode' => MutationMode::Extend->value,
                            'directive' => Directive::ScriptSrcElem->value,
                            'sources' => ["'" . SourceKeyword::strictDynamic->value . "'", 'https://cdn.example.org'],
                        ],
                    ],
                ],
            ],
        );
    }

    #[Test]
    public function cacheableSitemapAllowsLoadingXslStylesheetByUrl(): void
    {
        $csp = $this->getResponse()->getHeaderLine('Content-Security-Policy');
        self::assertMatchesRegularExpression('#(^|;)\s*script-src[^;]*\'self\'#', $csp);
        self::assertMatchesRegularExpression('#(^|;)\s*script-src-elem[^;]*\'self\'#', $csp);
        self::assertStringNotContainsString(SourceKeyword::strictDynamic->value, $csp);
    }

    #[Test]
    public function nonCacheableSitemapAllowsLoadingXslStylesheetByUrl(): void
    {
        // `AvoidContentSecurityPolicyNonceEventListener` keeps `'strict-dynamic'` for uncached requests
        $this->setUpSitemapRootPage(['EXT:seo/Tests/Functional/Fixtures/noCache.typoscript']);
        $csp = $this->getResponse()->getHeaderLine('Content-Security-Policy');
        self::assertMatchesRegularExpression('#(^|;)\s*script-src[^;]*\'self\'#', $csp);
        self::assertMatchesRegularExpression('#(^|;)\s*script-src-elem[^;]*\'self\'#', $csp);
        self::assertStringContainsString('https://cdn.example.org', $csp);
        self::assertStringNotContainsString(SourceKeyword::strictDynamic->value, $csp);
    }

    /**
     * @param list<string> $additionalSetup
     */
    private function setUpSitemapRootPage(array $additionalSetup = []): void
    {
        $this->setUpFrontendRootPage(
            1,
            [
                'constants' => ['EXT:seo/Configuration/TypoScript/XmlSitemap/constants.typoscript'],
                'setup' => [
                    'EXT:seo/Configuration/TypoScript/XmlSitemap/setup.typoscript',
                    ...$additionalSetup,
                ],
            ]
        );
    }

    private function getResponse(): ResponseInterface
    {
        return $this->executeFrontendSubRequest(
            (new InternalRequest('http://localhost/'))->withQueryParameters([
                'id' => 1,
                'type' => 1533906435,
                'tx_seo[sitemap]' => 'pages',
            ])
        );
    }
}
