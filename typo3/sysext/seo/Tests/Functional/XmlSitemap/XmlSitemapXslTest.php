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
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Frontend\Tests\Functional\SiteHandling\AbstractTestCase;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;

final class XmlSitemapXslTest extends AbstractTestCase
{
    protected array $coreExtensionsToLoad = ['seo'];

    protected array $configurationToUseInTestInstance = [
        'SYS' => [
            'encryptionKey' => '4408d27a916d51e624b69af3554f516dbab61037a9f7b9fd6f81b4d3bedeccb6',
        ],
        'FE' => [
            'cacheHash' => [
                'requireCacheHashPresenceParameters' => ['value', 'testing[value]', 'tx_testing_link[value]'],
                'excludedParameters' => ['tx_testing_link[excludedValue]'],
                'enforceValidation' => false,
            ],
            'debug' => false,
        ],
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/pages-sitemap.csv');
    }

    #[DataProvider('getXslFilePathsDataProvider')]
    #[Test]
    public function checkIfDefaultSitemapReturnsDefaultXsl(array $typoscriptSetupFiles, string $sitemap, string $xslFilePath): void
    {
        $this->setUpFrontendRootPage(
            1,
            [
                'constants' => ['EXT:seo/Configuration/TypoScript/XmlSitemap/constants.typoscript'],
                'setup' => $typoscriptSetupFiles,
            ]
        );

        $this->writeSiteConfiguration(
            'website-local',
            $this->buildSiteConfiguration(1, 'http://localhost/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/'),
            ]
        );

        $config = [
            'id' => 1,
            'type' => 1533906435,
        ];

        if (!empty($sitemap)) {
            $config['tx_seo[sitemap]'] = $sitemap;
        }

        $response = $this->executeFrontendSubRequest(
            (new InternalRequest('http://localhost/'))->withQueryParameters($config)
        );

        self::assertMatchesRegularExpression('/<\?xml-stylesheet type="text\/xsl" href="' . $xslFilePath . '"\?>/', (string)$response->getBody());
    }

    public static function getXslFilePathsDataProvider(): array
    {
        return [
            [
                [
                    'EXT:seo/Configuration/TypoScript/XmlSitemap/setup.typoscript',
                ],
                '',
                preg_quote('/typo3/sysext/seo/Resources/Public/CSS/Sitemap.xsl', '/') . '\?[0-9]+',
            ],
            [
                [
                    'EXT:seo/Configuration/TypoScript/XmlSitemap/setup.typoscript',
                    'EXT:seo/Tests/Functional/Fixtures/sitemap-xsl1.typoscript',
                ],
                '',
                preg_quote('/typo3/sysext/seo/Resources/Public/Tests/Functional/Fixtures/XslFile1.xsl', '/'),
            ],
            [
                [
                    'EXT:seo/Configuration/TypoScript/XmlSitemap/setup.typoscript',
                    'EXT:seo/Tests/Functional/Fixtures/sitemap-xsl2.typoscript',
                ],
                '',
                preg_quote('/typo3/sysext/seo/Resources/Public/Tests/Functional/Fixtures/XslFile2.xsl', '/'),
            ],
            [
                [
                    'EXT:seo/Configuration/TypoScript/XmlSitemap/setup.typoscript',
                    'EXT:seo/Tests/Functional/Fixtures/records.typoscript',
                    'EXT:seo/Tests/Functional/Fixtures/sitemap-xsl3.typoscript',
                ],
                '',
                preg_quote('/typo3/sysext/seo/Resources/Public/Tests/Functional/Fixtures/XslFile1.xsl', '/'),
            ],
            [
                [
                    'EXT:seo/Configuration/TypoScript/XmlSitemap/setup.typoscript',
                    'EXT:seo/Tests/Functional/Fixtures/records.typoscript',
                    'EXT:seo/Tests/Functional/Fixtures/sitemap-xsl3.typoscript',
                ],
                'records',
                preg_quote('/typo3/sysext/seo/Resources/Public/Tests/Functional/Fixtures/XslFile3.xsl', '/'),
            ],
            [
                [
                    'EXT:seo/Configuration/TypoScript/XmlSitemap/setup.typoscript',
                    'EXT:seo/Tests/Functional/Fixtures/records.typoscript',
                    'EXT:seo/Tests/Functional/Fixtures/sitemap-xsl3.typoscript',
                ],
                'pages',
                preg_quote('/typo3/sysext/seo/Resources/Public/Tests/Functional/Fixtures/XslFile1.xsl', '/'),
            ],
        ];
    }

    public static function emptyXslFileDisablesStylesheetDataProvider(): array
    {
        return [
            'index, disabled globally' => [
                [
                    'EXT:seo/Configuration/TypoScript/XmlSitemap/setup.typoscript',
                    'EXT:seo/Tests/Functional/Fixtures/sitemap-xsl-disabled.typoscript',
                ],
                '',
            ],
            'pages sitemap, disabled globally' => [
                [
                    'EXT:seo/Configuration/TypoScript/XmlSitemap/setup.typoscript',
                    'EXT:seo/Tests/Functional/Fixtures/sitemap-xsl-disabled.typoscript',
                ],
                'pages',
            ],
            'records sitemap, disabled for this sitemap only' => [
                [
                    'EXT:seo/Configuration/TypoScript/XmlSitemap/setup.typoscript',
                    'EXT:seo/Tests/Functional/Fixtures/records.typoscript',
                    'EXT:seo/Tests/Functional/Fixtures/sitemap-xsl-disabled-records.typoscript',
                ],
                'records',
            ],
        ];
    }

    #[DataProvider('emptyXslFileDisablesStylesheetDataProvider')]
    #[Test]
    public function emptyXslFileDisablesStylesheet(array $typoscriptSetupFiles, string $sitemap): void
    {
        $this->setUpFrontendRootPage(
            1,
            [
                'constants' => ['EXT:seo/Configuration/TypoScript/XmlSitemap/constants.typoscript'],
                'setup' => $typoscriptSetupFiles,
            ]
        );
        $this->writeSiteConfiguration(
            'website-local',
            $this->buildSiteConfiguration(1, 'http://localhost/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/'),
            ]
        );
        $config = [
            'id' => 1,
            'type' => 1533906435,
        ];
        if ($sitemap !== '') {
            $config['tx_seo[sitemap]'] = $sitemap;
        }

        $response = $this->executeFrontendSubRequest(
            (new InternalRequest('http://localhost/'))->withQueryParameters($config)
        );
        $body = (string)$response->getBody();

        self::assertSame(200, $response->getStatusCode());
        self::assertStringStartsWith('<?xml version="1.0" encoding="UTF-8"?>', $body);
        self::assertStringNotContainsString('xml-stylesheet', $body);
        self::assertMatchesRegularExpression('/<(sitemapindex|urlset) /', $body);
    }

    #[Test]
    public function emptyXslFileForOneSitemapKeepsStylesheetOfOtherSitemaps(): void
    {
        $this->setUpFrontendRootPage(
            1,
            [
                'constants' => ['EXT:seo/Configuration/TypoScript/XmlSitemap/constants.typoscript'],
                'setup' => [
                    'EXT:seo/Configuration/TypoScript/XmlSitemap/setup.typoscript',
                    'EXT:seo/Tests/Functional/Fixtures/records.typoscript',
                    'EXT:seo/Tests/Functional/Fixtures/sitemap-xsl-disabled-records.typoscript',
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

        $response = $this->executeFrontendSubRequest(
            (new InternalRequest('http://localhost/'))->withQueryParameters([
                'id' => 1,
                'type' => 1533906435,
                'tx_seo[sitemap]' => 'pages',
            ])
        );

        self::assertStringContainsString('XslFile1.xsl', (string)$response->getBody());
    }
}
