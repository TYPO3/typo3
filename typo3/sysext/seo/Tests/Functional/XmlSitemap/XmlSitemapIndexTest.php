<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Frontend\Tests\Functional\XmlSitemap;

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

/**
 * Contains functional tests for the XmlSitemap Index
 */
class XmlSitemapIndexTest extends AbstractTestCase
{
    /**
     * @var string[]
     */
    protected $coreExtensionsToLoad = [
        'core', 'frontend', 'seo'
    ];

    protected function setUp()
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
    }

    /**
     * @test
     */
    public function checkIfSiteMapIndexContainsPagesSitemap(): void
    {
        $this->writeSiteConfiguration(
            'website-local',
            $this->buildSiteConfiguration(1, 'http://localhost/')
        );

        $response = $this->executeFrontendRequest(
            (new InternalRequest())->withQueryParameters([
                'id' => 1,
                'type' => 1533906435
            ])
        );

        $expectedHeaders = [
            'Content-Length' => [0 => '462']
        ];
        $expectedBody = '#<loc>http://localhost/\?id=1&amp;type=1533906435&amp;sitemap=pages&amp;page=0</loc>#';
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($expectedHeaders, $response->getHeaders());
        $this->assertRegExp($expectedBody, (string)$response->getBody());
    }
}
