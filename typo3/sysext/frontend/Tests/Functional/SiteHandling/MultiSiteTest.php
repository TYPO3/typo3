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

namespace TYPO3\CMS\Frontend\Tests\Functional\SiteHandling;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Test scenarios with multiple sites. Verify caches do not spill
 * over from one site to another and similar.
 */
final class MultiSiteTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8'],
    ];

    protected array $testExtensionsToLoad = [
        'typo3/sysext/frontend/Tests/Functional/Fixtures/Extensions/test_site_sets',
    ];

    #[Test]
    public function twoSysTemplateBasedSitesCalculateCorrectSiteSettingsInTypoScript(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/MultiSiteTestPageAndSysTemplateImport.csv');
        $this->writeSiteConfiguration(
            'acme-brand-1',
            [
                'rootPageId' => 1,
                'base' => 'https://acme.com/brand',
                'settings' => [
                    'settingFromSite' => 'BrandFoo',
                ],
            ],
            [
                $this->buildDefaultLanguageConfiguration('EN', '/'),
            ]
        );
        $this->writeSiteConfiguration(
            'acme-tech-1',
            [
                'rootPageId' => 2,
                'base' => 'https://acme.com/tech',
                'settings' => [
                    'settingFromSite' => 'TechBar',
                ],
            ],
            [
                $this->buildDefaultLanguageConfiguration('EN', '/'),
            ]
        );
        $response = $this->executeFrontendSubRequest((new InternalRequest('https://acme.com/brand')));
        // Site settings set this string, it is used in TypoScript of this page as constant.
        self::assertStringContainsString('BrandFoo', (string)$response->getBody());
        $response = $this->executeFrontendSubRequest((new InternalRequest('https://acme.com/tech')));
        // Site settings set this string, it is used in TypoScript of this page as constant.
        // The test verifies the correct constant value is calculated when first calling one site
        // and then the other one, when both have the same site setting with different values.
        self::assertStringContainsString('TechBar', (string)$response->getBody());
    }

    #[Test]
    public function twoSetBasedSitesCalculateCorrectSiteSettingsInTypoScript(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/MultiSiteTestPageImport.csv');
        $this->writeSiteConfiguration(
            'acme-brand-2',
            [
                'rootPageId' => 1,
                'base' => 'https://acme.com/brand',
                'dependencies' => [
                    'typo3tests/multi-site',
                ],
                'settings' => [
                    'settingFromSite' => 'BrandFoo',
                ],
            ],
            [
                $this->buildDefaultLanguageConfiguration('EN', '/'),
            ]
        );
        $this->writeSiteConfiguration(
            'acme-tech-2',
            [
                'rootPageId' => 2,
                'base' => 'https://acme.com/tech',
                'dependencies' => [
                    'typo3tests/multi-site',
                ],
                'settings' => [
                    'settingFromSite' => 'TechBar',
                ],
            ],
            [
                $this->buildDefaultLanguageConfiguration('EN', '/'),
            ]
        );
        $response = $this->executeFrontendSubRequest((new InternalRequest('https://acme.com/brand')));
        // Site settings set this string, it is used in TypoScript of the set `typo3tests/multi-site` as a constant.
        self::assertStringContainsString('BrandFoo', (string)$response->getBody());
        $response = $this->executeFrontendSubRequest((new InternalRequest('https://acme.com/tech')));
        // Site settings set this string, it is used in TypoScript of the set `typo3tests/multi-site` as a constant.
        // The test verifies the correct constant value is calculated when first calling one site
        // and then the other one, when both have the same site setting with different values.
        self::assertStringContainsString('TechBar', (string)$response->getBody());
    }

    #[Test]
    public function twoSetBasedSitesCalculateCorrectConditionsInTypoScript(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/MultiSiteTestPageImport.csv');
        $this->writeSiteConfiguration(
            'acme-brand-3',
            [
                'rootPageId' => 1,
                'base' => 'https://acme.com/brand',
                'dependencies' => [
                    'typo3tests/site1',
                ],
            ],
            [
                $this->buildDefaultLanguageConfiguration('EN', '/'),
            ]
        );
        $this->writeSiteConfiguration(
            'acme-tech-3',
            [
                'rootPageId' => 2,
                'base' => 'https://acme.com/tech',
                'dependencies' => [
                    'typo3tests/site2',
                ],
            ],
            [
                $this->buildDefaultLanguageConfiguration('EN', '/'),
            ]
        );
        $response = $this->executeFrontendSubRequest((new InternalRequest('https://acme.com/brand')));
        self::assertStringContainsString('brand-root', (string)$response->getBody());
        $response = $this->executeFrontendSubRequest((new InternalRequest('https://acme.com/brand/brand-sub')));
        self::assertStringContainsString('brand-sub', (string)$response->getBody());
        $response = $this->executeFrontendSubRequest((new InternalRequest('https://acme.com/tech')));
        self::assertStringContainsString('tech-root', (string)$response->getBody());
        $response = $this->executeFrontendSubRequest((new InternalRequest('https://acme.com/tech/tech-sub')));
        self::assertStringContainsString('tech-sub', (string)$response->getBody());
    }
}
