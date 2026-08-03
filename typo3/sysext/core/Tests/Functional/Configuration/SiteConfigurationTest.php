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

namespace TYPO3\CMS\Core\Tests\Functional\Configuration;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Configuration\SiteConfiguration;
use TYPO3\CMS\Core\Configuration\SiteWriter;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class SiteConfigurationTest extends FunctionalTestCase
{
    protected function tearDown(): void
    {
        putenv('SITE_CACHE_TEST');
        parent::tearDown();
    }

    #[Test]
    public function resolvedSiteDataIsServedFromCoreCache(): void
    {
        $this->get(SiteWriter::class)->write('cached-site', [
            'rootPageId' => 42,
            'base' => 'https://example.com/',
        ]);
        $siteConfiguration = $this->get(SiteConfiguration::class);
        $sites = $siteConfiguration->getAllExistingSites();
        self::assertArrayHasKey('cached-site', $sites);

        // Remove the configuration file behind the back of SiteWriter and simulate
        // a new request: the site is still resolved from the core cache.
        unlink($this->instancePath . '/typo3conf/sites/cached-site/config.yaml');
        $this->get(CacheManager::class)->getCache('runtime')->flush();
        $sites = $siteConfiguration->getAllExistingSites();
        self::assertArrayHasKey('cached-site', $sites);
        self::assertSame(42, $sites['cached-site']->getRootPageId());
    }

    #[Test]
    public function baseVariantsAreEvaluatedPerResolutionEvenWithWarmCoreCache(): void
    {
        $this->get(SiteWriter::class)->write('variant-site', [
            'rootPageId' => 42,
            'base' => 'https://prod.example.com/',
            'baseVariants' => [
                [
                    'base' => 'https://dev.example.com/',
                    'condition' => 'getenv("SITE_CACHE_TEST") == "1"',
                ],
            ],
        ]);
        $siteConfiguration = $this->get(SiteConfiguration::class);
        $sites = $siteConfiguration->getAllExistingSites();
        self::assertSame('https://prod.example.com/', (string)$sites['variant-site']->getBase());

        // Simulate a new request with changed environment: runtime cache is empty,
        // the core cache is warm, the base variant condition matches now.
        $this->get(CacheManager::class)->getCache('runtime')->flush();
        putenv('SITE_CACHE_TEST=1');
        $sites = $siteConfiguration->getAllExistingSites();
        self::assertSame('https://dev.example.com/', (string)$sites['variant-site']->getBase());
    }
}
