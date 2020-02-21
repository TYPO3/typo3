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

use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\TestingFramework\Core\Functional\Framework\DataHandling\Scenario\DataHandlerFactory;
use TYPO3\TestingFramework\Core\Functional\Framework\DataHandling\Scenario\DataHandlerWriter;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequestContext;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\ResponseContent;

/**
 * Test case for frontend requests showing menus / links and resolving URLs with MountPoints
 *
 * The global website defines
 * - A products area with the current products
 * - Within the products area we want to show the archived products (mountpoint to archived products)
 * - An archive page shows all archived information (mountpoint to archive)
 *
 * The canadian website mirrors the main products area (mountpoint to products), the news
 * and shows a link to the main site.
 *
 * Same goes to the US website with an other language setup and some minor other pages, but also
 * pages with the same slug.
 *
 * The archive contains archived products and archived news.
 *
 * Since links underneath mountpoints can only be reached by default the test suite is validating
 * URLS of menu generation, but also resolving the links to the pages within a Mountpoint.
 *
 * So we end up with this:
 * - Global site has two MountPoint Pages
 *   * Archived products
 *     - Speciality: The slug of the archived page is called "/products" - same as the mount point
 *   * All archived content
 *     - Speciality: The mounted page only contains a "/" as its a rootpage
 * - Canadian site has two MountPoint Pages
 *   * Products
 *     - Speciality: Nested mountpoints: Products ("Global") -> Archived Products ("Global Archive")
 *   * News
 *     - News of Canada - MountPoint within the same site
 *     - Archived news - MountPoint to another site
 *
 * @todo add Tests for having a PageFolder as mounted page (e.g. id 10000)
 * @todo Add tests for multilingual setups
 * @todo Add tests with various TypoScript settings activated
 */
class MountPointTest extends AbstractTestCase
{
    /**
     * @var string
     */
    private $siteTitle = 'A Company that Manufactures Board Games and Everything Inc';

    /**
     * @var InternalRequestContext
     */
    private $internalRequestContext;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        static::initializeDatabaseSnapshot();
    }

    public static function tearDownAfterClass(): void
    {
        static::destroyDatabaseSnapshot();
        parent::tearDownAfterClass();
    }

    protected function setUp(): void
    {
        parent::setUp();

        // these settings are forwarded to the frontend sub-request as well
        $this->internalRequestContext = (new InternalRequestContext())
            ->withGlobalSettings(['TYPO3_CONF_VARS' => static::TYPO3_CONF_VARS]);

        $this->writeSiteConfiguration(
            'main',
            $this->buildSiteConfiguration(1000, 'https://acme.com/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/'),
                $this->buildLanguageConfiguration('FR', '/fr/', ['EN']),
                $this->buildLanguageConfiguration('FR-CA', '/fr-ca/', ['FR', 'EN']),
            ]
        );
        $this->writeSiteConfiguration(
            'acme-canada',
            $this->buildSiteConfiguration(2000, 'https://acme.ca/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/'),
                $this->buildLanguageConfiguration('FR', '/fr/', ['EN']),
                $this->buildLanguageConfiguration('FR-CA', '/fr-ca/', ['FR', 'EN']),
            ]
        );
        $this->writeSiteConfiguration(
            'acme-us',
            $this->buildSiteConfiguration(3000, 'https://acme.us/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/'),
                $this->buildLanguageConfiguration('ES', '/es/', ['ES']),
            ]
        );
        $this->writeSiteConfiguration(
            'archive-acme-com',
            $this->buildSiteConfiguration(10000, 'https://archive.acme.com/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/'),
                $this->buildLanguageConfiguration('FR', '/fr/', ['EN']),
                $this->buildLanguageConfiguration('FR-CA', '/fr-ca/', ['FR', 'EN'])
            ]
        );
        $this->withDatabaseSnapshot(function () {
            $this->setUpDatabase();
        });
    }

    protected function setUpDatabase()
    {
        $backendUser = $this->setUpBackendUserFromFixture(1);
        Bootstrap::initializeLanguageObject();

        $scenarioFile = __DIR__ . '/Fixtures/MountPointScenario.yaml';
        $factory = DataHandlerFactory::fromYamlFile($scenarioFile);
        $writer = DataHandlerWriter::withBackendUser($backendUser);
        $writer->invokeFactory($factory);
        static::failIfArrayIsNotEmpty(
            $writer->getErrors()
        );

        $this->setUpFrontendRootPage(
            1000,
            [
                'typo3/sysext/frontend/Tests/Functional/SiteHandling/Fixtures/LinkGenerator.typoscript',
            ],
            [
                'title' => 'ACME Global',
                'sitetitle' => $this->siteTitle,
            ]
        );
        $this->setUpFrontendRootPage(
            2000,
            [
                'typo3/sysext/frontend/Tests/Functional/SiteHandling/Fixtures/LinkGenerator.typoscript',
            ],
            [
                'title' => 'ACME Canada',
                'sitetitle' => $this->siteTitle,
            ]
        );
        $this->setUpFrontendRootPage(
            3000,
            [
                'typo3/sysext/frontend/Tests/Functional/SiteHandling/Fixtures/LinkGenerator.typoscript',
            ],
            [
                'title' => 'ACME US',
                'sitetitle' => $this->siteTitle,
            ]
        );
        $this->setUpFrontendRootPage(
            10000,
            [
                'typo3/sysext/frontend/Tests/Functional/SiteHandling/Fixtures/LinkGenerator.typoscript',
            ],
            [
                'title' => 'ACME Archive',
                'sitetitle' => $this->siteTitle,
            ]
        );
    }

    protected function tearDown(): void
    {
        unset($this->internalRequestContext);
        parent::tearDown();
    }

    /**
     * @return array
     */
    public function hierarchicalMenuIsGeneratedDataProvider(): array
    {
        $siteMapOfMainPage = [
            ['title' => 'EN: Welcome', 'link' => '/welcome'],
            [
                'title' => 'EN: Features',
                'link' => '/features',
                'children' => [
                    [
                        'title' => 'EN: Frontend Editing',
                        'link' => '/features/frontend-editing',
                    ],
                ],
            ],
            [
                'title' => 'EN: Products',
                'link' => '/products',
                'children' => [
                    [
                        'title' => 'EN: Toys',
                        'link' => '/products/toys',
                    ],
                    [
                        'title' => 'EN: Card Games',
                        'link' => '/products/card-games',
                    ],
                    [
                        'title' => 'EN: Board Games',
                        'link' => '/products/board-games',
                        'children' => [
                            [
                                'title' => 'EN: Monopoly',
                                'link' => '/products/monopoly',
                            ],
                            [
                                'title' => 'EN: Catan',
                                'link' => '/products/board-games/catan',
                            ],
                            [
                                'title' => 'EN: Risk',
                                'link' => '/risk',
                            ],
                        ]
                    ],
                    [
                        'title' => 'Archived Products',
                        'link' => '/products/archive',
                        'children' => [
                            [
                                'title' => 'EN: Games of the 1980s',
                                'link' => '/products/archive/games-of-the-1980s',
                            ],
                            [
                                'title' => 'EN: Games of the 1990s',
                                'link' => '/products/archive/games-of-the-1990s',
                            ],
                            [
                                'title' => 'Uno - The famous classic',
                                'link' => '/products/archive/uno',
                            ],
                        ],
                    ],
                ],
            ],
            [
                'title' => 'See our Archives',
                'link' => '/archive',
                'children' => [
                    [
                        'title' => 'EN: Archived Products',
                        'link' => '/archive/products',
                        'children' => [
                            [
                                'title' => 'EN: Games of the 1980s',
                                'link' => '/archive/products/games-of-the-1980s',
                            ],
                            [
                                'title' => 'EN: Games of the 1990s',
                                'link' => '/archive/products/games-of-the-1990s',
                            ],
                            [
                                'title' => 'Uno - The famous classic',
                                'link' => '/archive/uno',
                            ],
                        ],
                    ],
                    [
                        'title' => 'EN: Archived News',
                        'link' => '/archive/news',
                        'children' => [
                            [
                                'title' => 'EN: Whats new in 2020',
                                'link' => '/archive/news/latest-releases-2020',
                            ],
                            [
                                'title' => 'EN: Whats new in 2019',
                                'link' => '/archive/news/latest-releases-2019',
                            ],
                        ]
                    ]
                ],
            ],
            ['title' => 'About us', 'link' => '/about'],
            ['title' => 'Page not found', 'link' => '/404'],
            // Link gets resolved to other site + shortcut to first page
            ['title' => 'ACME in Canada', 'link' => 'https://acme.ca/news/'],
        ];
        $siteMapOfCanadianPage = [
            [
                'title' => 'News of Canada',
                'link' => '/news/',
                'children' => [
                    [
                        'title' => 'New Games in Canada 2020',
                        'link' => '/news/games-in-canada-2020',
                    ],
                    [
                        'title' => 'New Games in Canada 2019',
                        'link' => '/slug-with-a-mistake-which-never-changed/games-in-canada-2019',
                    ],
                ]
            ],
            [
                'title' => 'Products',
                'link' => '/products',
                'children' => [
                    [
                        'title' => 'EN: Toys',
                        'link' => '/products/toys',
                    ],
                    [
                        'title' => 'EN: Card Games',
                        'link' => '/products/card-games',
                    ],
                    [
                        'title' => 'EN: Board Games',
                        'link' => '/products/board-games',
                        'children' => [
                            [
                                'title' => 'EN: Monopoly',
                                'link' => '/products/monopoly',
                            ],
                            [
                                'title' => 'EN: Catan',
                                'link' => '/products/board-games/catan',
                            ],
                            [
                                'title' => 'EN: Risk',
                                'link' => '/products/risk',
                            ],
                        ]
                    ],
                    [
                        'title' => 'Archived Products',
                        'link' => '/products/archive',
                        'children' => [
                            [
                                'title' => 'EN: Games of the 1980s',
                                'link' => '/products/archive/games-of-the-1980s',
                            ],
                            [
                                'title' => 'EN: Games of the 1990s',
                                'link' => '/products/archive/games-of-the-1990s',
                            ],
                            [
                                'title' => 'Uno - The famous classic',
                                // Perfect example that the first two slugs are the slugs from the MountPoint Pages
                                'link' => '/products/archive/uno',
                            ],
                        ],
                    ],
                ],
            ],
            [
                'title' => 'All News',
                'link' => '/all-news',
                'children' => [
                    [
                        'title' => 'News of Canada',
                        // slash is added because ID 2100 has a trailing slash
                        'link' => '/all-news/canada/',
                        'children' => [
                            [
                                'title' => 'New Games in Canada 2020',
                                // Example where the slug of the MountPoint page is added
                                // And the "common" prefix of the mounted page - "/news" is removed
                                // Frmo the slug of the main page
                                'link' => '/all-news/canada/games-in-canada-2020',
                            ],
                            [
                                'title' => 'New Games in Canada 2019',
                                // no common prefix, but the original slug was added
                                'link' => '/all-news/canada/slug-with-a-mistake-which-never-changed/games-in-canada-2019',
                            ],
                        ]
                    ],
                    [
                        'title' => 'Archived News',
                        'link' => '/all-news/archive',
                        'children' => [
                            [
                                'title' => 'EN: Whats new in 2020',
                                'link' => '/all-news/archive/latest-releases-2020',
                            ],
                            [
                                'title' => 'EN: Whats new in 2019',
                                'link' => '/all-news/archive/latest-releases-2019',
                            ],
                        ]
                    ],
                ]
            ],
            ['title' => 'Link To Our Worldwide Site', 'link' => 'https://acme.com/welcome'],
        ];
        return [
            'ACME Global' => [
                'https://acme.com/welcome',
                $siteMapOfMainPage
            ],
            'ACME Canada First Subpage in EN' => [
                'https://acme.ca/all-news',
                $siteMapOfCanadianPage
            ],
            'ACME Canada Subpage of mounted Products page' => [
                'https://acme.ca/products/risk',
                $siteMapOfCanadianPage
            ]
        ];
    }

    /**
     * @param string $accessedUrl
     * @param array $expectation
     *
     * @test
     * @dataProvider hierarchicalMenuIsGeneratedDataProvider
     */
    public function hierarchicalMenuIsGenerated(string $accessedUrl, array $expectation)
    {
        $response = $this->executeFrontendRequest(
            (new InternalRequest($accessedUrl))
                ->withInstructions([
                    $this->createHierarchicalMenuProcessorInstruction([
                        'levels' => 3,
                        'entryLevel' => 0,
                        'expandAll' => 1,
                        'includeSpacer' => 1,
                        'titleField' => 'title',
                    ])
                ]),
            $this->internalRequestContext
        );

        $json = json_decode((string)$response->getBody(), true);
        $json = $this->filterMenu($json);

        self::assertSame($expectation, $json);
    }

    public function requestsResolvePageIdAndMountPointParameterDataProvider()
    {
        return [
            'regular page on global site' => [
                'https://acme.com/welcome',
                1000,
                1100,
                null
            ],
            'mountpoint to a different site with same slug on global site' => [
                'https://acme.com/products/archive',
                1000,
                1340,
                '10100-1340'
            ],
            'subpage of mountpoint to a different site with same slug on global site' => [
                'https://acme.com/products/archive/uno',
                1000,
                10130,
                '10100-1340'
            ],
            'subpage of mountpoint to a different site on global site' => [
                'https://acme.com/archive/products/games-of-the-1980s',
                1000,
                10110,
                '10000-1400'
            ],
        ];
    }

    /**
     * @param string $uri
     * @param int $rootPageId
     * @param int $expectedPageId
     * @param string|null $expectedMountPointParameter
     * @test
     * @dataProvider requestsResolvePageIdAndMountPointParameterDataProvider
     */
    public function requestsResolvePageIdAndMountPointParameter(string $uri, int $rootPageId, int $expectedPageId, ?string $expectedMountPointParameter)
    {
        $this->setUpFrontendRootPage(
            $rootPageId,
            [
                'typo3/sysext/frontend/Tests/Functional/SiteHandling/Fixtures/LinkRequest.typoscript',
            ],
            [
                'title' => 'ACME Root',
                'sitetitle' => $this->siteTitle,
            ]
        );
        $response = $this->executeFrontendRequest(
            (new InternalRequest($uri)),
            $this->internalRequestContext
        );
        $responseData = json_decode((string)$response->getBody(), true);
        self::assertSame(200, $response->getStatusCode());
        self::assertSame($expectedPageId, $responseData['pageId']);
        self::assertSame($expectedMountPointParameter, $responseData['dynamicArguments']['MP'] ?? null);
    }

    /**
     * @return array
     */
    public function mountPointPagesShowContentAsConfiguredDataProvider()
    {
        return [
            'Show content of MountPoint Page' => [
                'https://acme.ca/all-news/archive',
                'Content of MountPoint Page'
            ],
            'Show content of Mounted Page' => [
                'https://acme.ca/all-news/canada',
                'See a list of all games distributed in canada'
            ],
            'Show content of Mounted Page for second site' => [
                'https://acme.us/all-news/us',
                'See a list of all games distributed in the US'
            ],
        ];
    }

    /**
     * This test checks for "mount_pid_ol=0", whereas mount_pid_ol=1 should trigger a redirect currently.
     * @todo: revisit the "mount_pid_ol=1" redirect, there is some truth to it, but still would
     * remove the context, which does not make sense. Should be revisited. See test above as well.
     *
     * @param string $uri
     * @param string $expected
     * @dataProvider mountPointPagesShowContentAsConfiguredDataProvider
     * @test
     * @group not-postgres
     * Does not work on postgres currently due to setUpFrontendRootPage which does not work with the database snapshotting
     */
    public function mountPointPagesShowContentAsConfigured(string $uri, string $expected)
    {
        $this->setUpFrontendRootPage(
            2000,
            [
                'typo3/sysext/core/Tests/Functional/Fixtures/Frontend/JsonRenderer.typoscript',
                'typo3/sysext/frontend/Tests/Functional/SiteHandling/Fixtures/JsonRenderer.typoscript',
            ],
            [
                'title' => 'ACME Root',
                'sitetitle' => $this->siteTitle,
            ]
        );
        $this->setUpFrontendRootPage(
            3000,
            [
                'typo3/sysext/core/Tests/Functional/Fixtures/Frontend/JsonRenderer.typoscript',
                'typo3/sysext/frontend/Tests/Functional/SiteHandling/Fixtures/JsonRenderer.typoscript',
            ],
            [
                'title' => 'ACME Root',
                'sitetitle' => $this->siteTitle,
            ]
        );
        $response = $this->executeFrontendRequest(
            (new InternalRequest($uri)),
            $this->internalRequestContext
        );
        self::assertSame(200, $response->getStatusCode());
        $responseStructure = ResponseContent::fromString(
            (string)$response->getBody()
        );
        $responseRecords = $responseStructure->getSection('Default')->getRecords();
        $firstContent = null;
        // Find tt_content element
        foreach ($responseRecords as $identifier => $record) {
            if (strpos($identifier, 'tt_content:') === 0) {
                $firstContent = $record;
                break;
            }
        }
        if ($expected) {
            self::assertStringContainsString($expected, $firstContent['header'] ?? '');
        } else {
            self::assertEmpty($firstContent);
        }
    }
}
