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

use Symfony\Component\Yaml\Yaml;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Test case
 */
class TitleTagRenderingTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['seo'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCsvDataSet(__DIR__ . '/../Fixtures/pages-title-tag.csv');
        $this->setUpFrontendRootPage(
            1,
            ['EXT:frontend/Tests/Functional/Rendering/Fixtures/TitleTagRenderingTest.typoscript']
        );
        $this->setUpFrontendSite(1);
    }

    /**
     * Create a simple site config for the tests that
     * call a frontend page.
     *
     * @param int $pageId
     */
    protected function setUpFrontendSite(int $pageId): void
    {
        $configuration = [
            'rootPageId' => $pageId,
            'base' => '/',
            'websiteTitle' => '',
            'languages' => [
                [
                    'title' => 'English',
                    'enabled' => true,
                    'languageId' => '0',
                    'base' => '/',
                    'typo3Language' => 'default',
                    'locale' => 'en_US.UTF-8',
                    'iso-639-1' => 'en',
                    'websiteTitle' => 'Site EN',
                    'navigationTitle' => '',
                    'hreflang' => '',
                    'direction' => '',
                    'flag' => 'us',
                ],
            ],
            'errorHandling' => [],
            'routes' => [],
        ];
        GeneralUtility::mkdir_deep($this->instancePath . '/typo3conf/sites/testing/');
        $yamlFileContents = Yaml::dump($configuration, 99, 2);
        $fileName = $this->instancePath . '/typo3conf/sites/testing/config.yaml';
        GeneralUtility::writeFile($fileName, $yamlFileContents);
    }

    public function titleTagDataProvider(): array
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

    /**
     * @test
     * @dataProvider titleTagDataProvider
     */
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
