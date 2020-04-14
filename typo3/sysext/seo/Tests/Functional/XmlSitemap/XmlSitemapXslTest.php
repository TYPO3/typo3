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

use TYPO3\CMS\Frontend\Tests\Functional\SiteHandling\AbstractTestCase;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;

class XmlSitemapXslTest extends AbstractTestCase
{
    /**
     * @var string[]
     */
    protected $coreExtensionsToLoad = [
        'core', 'frontend', 'seo'
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importDataSet('EXT:seo/Tests/Functional/Fixtures/pages-sitemap.xml');
    }

    /**
     * @test
     * @dataProvider getXslFilePaths
     */
    public function checkIfDefaultSitemapReturnsDefaultXsl($typoscriptSetupFiles, $sitemap, $xslFilePath): void
    {
        $this->setUpFrontendRootPage(
            1,
            [
                'constants' => ['EXT:seo/Configuration/TypoScript/XmlSitemap/constants.typoscript'],
                'setup' => $typoscriptSetupFiles
            ]
        );

        $this->writeSiteConfiguration(
            'website-local',
            $this->buildSiteConfiguration(1, 'http://localhost/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/')
            ]
        );

        $config = [
            'id' => 1,
            'type' => 1533906435
        ];

        if (!empty($sitemap)) {
            $config['sitemap'] = $sitemap;
        }

        $response = $this->executeFrontendRequest(
            (new InternalRequest('http://localhost/'))->withQueryParameters($config)
        );

        self::assertRegExp('/<\?xml-stylesheet type="text\/xsl" href="' . $xslFilePath . '"\?>/', (string)$response->getBody());
    }

    public function getXslFilePaths()
    {
        return [
            [
                [
                    'EXT:seo/Configuration/TypoScript/XmlSitemap/setup.typoscript'
                ],
                '',
                '\/typo3\/sysext\/seo\/Resources\/Public\/CSS\/Sitemap.xsl'
            ],
            [
                [
                    'EXT:seo/Configuration/TypoScript/XmlSitemap/setup.typoscript',
                    'EXT:seo/Tests/Functional/Fixtures/sitemap-xsl1.typoscript'
                ],
                '',
                '\/typo3\/sysext\/seo\/Tests\/Functional\/Fixtures\/XslFile1.xsl'
            ],
            [
                [
                    'EXT:seo/Configuration/TypoScript/XmlSitemap/setup.typoscript',
                    'EXT:seo/Tests/Functional/Fixtures/sitemap-xsl2.typoscript'
                ],
                '',
                '\/typo3\/sysext\/seo\/Tests\/Functional\/Fixtures\/XslFile2.xsl'
            ],
            [
                [
                    'EXT:seo/Configuration/TypoScript/XmlSitemap/setup.typoscript',
                    'EXT:seo/Tests/Functional/Fixtures/records.typoscript',
                    'EXT:seo/Tests/Functional/Fixtures/sitemap-xsl3.typoscript'
                ],
                '',
                '\/typo3\/sysext\/seo\/Tests\/Functional\/Fixtures\/XslFile1.xsl'
            ],
            [
                [
                    'EXT:seo/Configuration/TypoScript/XmlSitemap/setup.typoscript',
                    'EXT:seo/Tests/Functional/Fixtures/records.typoscript',
                    'EXT:seo/Tests/Functional/Fixtures/sitemap-xsl3.typoscript'
                ],
                'records',
                '\/typo3\/sysext\/seo\/Tests\/Functional\/Fixtures\/XslFile3.xsl'
            ],
            [
                [
                    'EXT:seo/Configuration/TypoScript/XmlSitemap/setup.typoscript',
                    'EXT:seo/Tests/Functional/Fixtures/records.typoscript',
                    'EXT:seo/Tests/Functional/Fixtures/sitemap-xsl3.typoscript'
                ],
                'pages',
                '\/typo3\/sysext\/seo\/Tests\/Functional\/Fixtures\/XslFile1.xsl'
            ],
        ];
    }
}
