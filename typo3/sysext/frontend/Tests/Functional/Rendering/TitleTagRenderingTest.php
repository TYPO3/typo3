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

namespace TYPO3\CMS\Frontend\Tests\Functional\Rendering;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class TitleTagRenderingTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;

    private const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'websiteTitle' => 'Site EN'],
    ];

    protected array $coreExtensionsToLoad = ['seo'];

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
        $this->importCsvDataSet(__DIR__ . '/../Fixtures/pages-title-tag.csv');
        $this->setUpFrontendRootPage(
            1,
            ['EXT:frontend/Tests/Functional/Rendering/Fixtures/TitleTagRenderingTest.typoscript']
        );
        $this->writeSiteConfiguration(
            'testing',
            $this->buildSiteConfiguration(1, '/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/'),
            ]
        );
    }

    public static function titleTagDataProvider(): array
    {
        return [
            [
                [
                    'pageId' => 1000,
                ],
                [
                    'assertRegExp' => '#<title>Site EN: Root 1000</title>#',
                    'assertNotRegExp' => '',
                ],
            ],
            [
                [
                    'pageId' => 1001,
                ],
                [
                    'assertRegExp' => '#<title>Site EN: SEO Root 1001</title>#',
                    'assertNotRegExp' => '',
                ],
            ],
            [
                [
                    'pageId' => 1000,
                    'noPageTitle' => 1,
                ],
                [
                    'assertRegExp' => '#<title>Site EN</title>#',
                    'assertNotRegExp' => '',
                ],
            ],
            [
                [
                    'pageId' => 1001,
                    'noPageTitle' => 1,
                ],
                [
                    'assertRegExp' => '#<title>Site EN</title>#',
                    'assertNotRegExp' => '',
                ],
            ],
            [
                [
                    'pageId' => 1000,
                    'noPageTitle' => 1,
                    'showWebsiteTitle' => 0,
                ],
                [
                    'assertRegExp' => '',
                    'assertNotRegExp' => '#<title>.*</title>#',
                ],
            ],
            [
                [
                    'pageId' => 1001,
                    'noPageTitle' => 1,
                    'showWebsiteTitle' => 0,
                ],
                [
                    'assertRegExp' => '',
                    'assertNotRegExp' => '#<title>.*</title>#',
                ],
            ],
            [
                [
                    'pageId' => 1000,
                    'noPageTitle' => 2,
                ],
                [
                    'assertRegExp' => '',
                    'assertNotRegExp' => '#<title>.*</title>#',
                ],
            ],
            [
                [
                    'pageId' => 1001,
                    'noPageTitle' => 2,
                ],
                [
                    'assertRegExp' => '',
                    'assertNotRegExp' => '#<title>.*</title>#',
                ],
            ],
            [
                [
                    'pageId' => 1000,
                    'showWebsiteTitle' => 0,
                ],
                [
                    'assertRegExp' => '#<title>Root 1000</title>#',
                    'assertNotRegExp' => '',
                ],
            ],
            [
                [
                    'pageId' => 1001,
                    'showWebsiteTitle' => 0,
                ],
                [
                    'assertRegExp' => '#<title>SEO Root 1001</title>#',
                    'assertNotRegExp' => '',
                ],
            ],
            [
                [
                    'pageId' => 1000,
                    'noPageTitle' => 2,
                    'headerData' => 1,
                ],
                [
                    'assertRegExp' => '#<title>Header Data Title</title>#',
                    'assertNotRegExp' => '',
                ],
            ],
            [
                [
                    'pageId' => 1001,
                    'noPageTitle' => 2,
                    'headerData' => 1,
                ],
                [
                    'assertRegExp' => '#<title>Header Data Title</title>#',
                    'assertNotRegExp' => '',
                ],
            ],
            [
                [
                    'pageId' => 1000,
                    'pageTitleTS' => 1,
                ],
                [
                    'assertRegExp' => '#<title>SITE EN: ROOT 1000</title>#',
                    'assertNotRegExp' => '',
                ],
            ],
            [
                [
                    'pageId' => 1000,
                    'pageTitleFirst' => 1,
                ],
                [
                    'assertRegExp' => '#<title>Root 1000: Site EN</title>#',
                    'assertNotRegExp' => '',
                ],
            ],
            [
                [
                    'pageId' => 1000,
                    'pageTitleFirst' => 1,
                    'showWebsiteTitle' => 0,
                ],
                [
                    'assertRegExp' => '#<title>Root 1000</title>#',
                    'assertNotRegExp' => '',
                ],
            ],
            [
                [
                    'pageId' => 1000,
                    'pageTitleSeparator' => 'typoscriptText',
                ],
                [
                    'assertRegExp' => '#<title>Site EN| Root 1000</title>#',
                    'assertNotRegExp' => '',
                ],
            ],
            [
                [
                    'pageId' => 1000,
                    'pageTitleSeparator' => 'typoscriptStdwrap',
                ],
                [
                    'assertRegExp' => '#<title>Site EN - Root 1000</title>#',
                    'assertNotRegExp' => '',
                ],
            ],
        ];
    }

    #[DataProvider('titleTagDataProvider')]
    #[Test]
    public function checkIfCorrectTitleTagIsRendered(array $pageConfig, array $expectations): void
    {
        $response = $this->executeFrontendSubRequest(
            (new InternalRequest())->withQueryParameters([
                'id' => (int)$pageConfig['pageId'],
                'noPageTitle' => (int)($pageConfig['noPageTitle'] ?? 0),
                'headerData' => (int)($pageConfig['headerData'] ?? 0),
                'pageTitleTS' => (int)($pageConfig['pageTitleTS'] ?? 0),
                'pageTitleFirst' => (int)($pageConfig['pageTitleFirst'] ?? 0),
                'pageTitleSeparator' => $pageConfig['pageTitleSeparator'] ?? '',
                'showWebsiteTitle' => (int)($pageConfig['showWebsiteTitle'] ?? 1),
            ])
        );
        $content = (string)$response->getBody();
        if ($expectations['assertRegExp']) {
            self::assertMatchesRegularExpression($expectations['assertRegExp'], $content);
        }
        if ($expectations['assertNotRegExp']) {
            self::assertDoesNotMatchRegularExpression($expectations['assertNotRegExp'], $content);
        }
    }
}
