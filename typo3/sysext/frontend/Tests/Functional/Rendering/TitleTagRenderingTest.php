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
    /**
     * @var string[]
     */
    protected $coreExtensionsToLoad = [
        'core', 'frontend', 'seo'
    ];

    /**
     * @var string[]
     */
    protected $pathsToLinkInTestInstance = [
        'typo3/sysext/core/Tests/Functional/Fixtures/Frontend/AdditionalConfiguration.php' => 'typo3conf/AdditionalConfiguration.php',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importDataSet('EXT:frontend/Tests/Functional/Fixtures/pages-title-tag.xml');
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
    protected function setUpFrontendSite(int $pageId)
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
                    'websiteTitle' => '',
                    'navigationTitle' => '',
                    'hreflang' => '',
                    'direction' => '',
                    'flag' => 'us',
                ]
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
                    'assertRegExp' => '#<title>Root 1000</title>#',
                    'assertNotRegExp' => '',
                ]
            ],
            [
                [
                    'pageId' => 1001,
                ],
                [
                    'assertRegExp' => '#<title>SEO Root 1001</title>#',
                    'assertNotRegExp' => '',
                ]
            ],
            [
                [
                    'pageId' => 1000,
                    'noPageTitle' => 1,
                ],
                [
                    'assertRegExp' => '',
                    'assertNotRegExp' => '#<title>Site Title</title>#',
                ]
            ],
            [
                [
                    'pageId' => 1001,
                    'noPageTitle' => 1,
                ],
                [
                    'assertRegExp' => '',
                    'assertNotRegExp' => '#<title>Site Title</title>#',
                ]
            ],
            [
                [
                    'pageId' => 1000,
                    'noPageTitle' => 2,
                ],
                [
                    'assertRegExp' => '',
                    'assertNotRegExp' => '#<title>.*</title>#',
                ]
            ],
            [
                [
                    'pageId' => 1001,
                    'noPageTitle' => 2,
                ],
                [
                    'assertRegExp' => '',
                    'assertNotRegExp' => '#<title>.*</title>#',
                ]
            ],
            [
                [
                    'pageId' => 1000,
                    'noPageTitle' => 2,
                    'headerData' => 1
                ],
                [
                    'assertRegExp' => '#<title>Header Data Title</title>#',
                    'assertNotRegExp' => '',
                ]
            ],
            [
                [
                    'pageId' => 1001,
                    'noPageTitle' => 2,
                    'headerData' => 1
                ],
                [
                    'assertRegExp' => '#<title>Header Data Title</title>#',
                    'assertNotRegExp' => '',
                ]
            ],
            [
                [
                    'pageId' => 1000,
                    'pageTitleTS' => 1
                ],
                [
                    'assertRegExp' => '#<title>ROOT 1000</title>#',
                    'assertNotRegExp' => '',
                ]
            ],
        ];
    }

    /**
     * @param $pageConfig
     * @param $expectations
     * @test
     * @dataProvider titleTagDataProvider
     */
    public function checkIfCorrectTitleTagIsRendered($pageConfig, $expectations): void
    {
        $response = $this->executeFrontendSubRequest(
            (new InternalRequest())->withQueryParameters([
                'id' => (int)$pageConfig['pageId'],
                'noPageTitle' => (int)$pageConfig['noPageTitle'],
                'headerData' => (int)$pageConfig['headerData'],
                'pageTitleTS' => (int)$pageConfig['pageTitleTS']
            ])
        );
        $content = (string)$response->getBody();
        if ($expectations['assertRegExp']) {
            self::assertRegExp($expectations['assertRegExp'], $content);
        }
        if ($expectations['assertNotRegExp']) {
            self::assertNotRegExp($expectations['assertNotRegExp'], $content);
        }
    }
}
