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

namespace TYPO3\CMS\Backend\Tests\Functional\Controller\Page;

use TYPO3\CMS\Backend\Controller\Page\TreeController;
use TYPO3\CMS\Backend\Tests\Functional\Tree\Repository\Fixtures\Tree\NormalizeTreeTrait;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\WorkspaceAspect;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\AccessibleObjectInterface;
use TYPO3\TestingFramework\Core\Functional\Framework\DataHandling\Scenario\DataHandlerFactory;
use TYPO3\TestingFramework\Core\Functional\Framework\DataHandling\Scenario\DataHandlerWriter;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Test case for TYPO3\CMS\Backend\Controller\Page\TreeController
 */
class TreeControllerTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;
    use NormalizeTreeTrait;

    protected const LANGUAGE_PRESETS = [];

    protected $coreExtensionsToLoad = ['workspaces'];

    /**
     * @var TreeController|AccessibleObjectInterface
     */
    private $subject;

    /**
     * @var BackendUserAuthentication
     */
    private BackendUserAuthentication $backendUser;

    /**
     * @var Context
     */
    private $context;

    /**
     * The fixture which is used when initializing a backend user
     *
     * @var string
     */
    protected $backendUserFixture = 'EXT:core/Tests/Functional/Fixtures/be_users.xml';

    protected function setUp(): void
    {
        parent::setUp();
        //admin user for importing dataset
        $this->backendUser = $this->setUpBackendUserFromFixture(1);
        $this->setUpDatabase();

        //regular editor, non admin
        $this->backendUser = $this->setUpBackendUser(9);
        $this->context = GeneralUtility::makeInstance(Context::class);
        $this->subject = $this->getAccessibleMock(TreeController::class, ['dummy']);
    }

    protected function tearDown(): void
    {
        unset($this->subject, $this->backendUser, $this->context);
        parent::tearDown();
    }

    protected function setUpDatabase(): void
    {
        Bootstrap::initializeLanguageObject();
        $scenarioFile = __DIR__ . '/Fixtures/PagesWithBEPermissions.yaml';
        $factory = DataHandlerFactory::fromYamlFile($scenarioFile);
        $writer = DataHandlerWriter::withBackendUser($this->backendUser);
        $writer->invokeFactory($factory);
        static::failIfArrayIsNotEmpty(
            $writer->getErrors()
        );
    }

    /**
     * @test
     */
    public function getAllEntryPointPageTrees(): void
    {
        $actual = $this->subject->_call('getAllEntryPointPageTrees');
        $keepProperties = array_flip(['uid', 'title', '_children']);
        $actual = $this->sortTreeArray($actual);
        $actual = $this->normalizeTreeArray($actual, $keepProperties);

        $expected = [
            [
                'uid' => 0,
                'title' => 'New TYPO3 site',
                '_children' => [
                    [
                        'uid' => 1000,
                        'title' => 'ACME Inc',
                        '_children' => [
                            [
                                'uid' => 1100,
                                'title' => 'EN: Welcome',
                                '_children' => [
                                ],
                            ],
                            [
                                'uid' => 1200,
                                'title' => 'EN: Features',
                                '_children' => [
                                    [
                                        'uid' => 1210,
                                        'title' => 'EN: Frontend Editing',
                                        '_children' => [
                                        ],
                                    ],
                                    [
                                        'uid' => 1230,
                                        'title' => 'EN: Managing content',
                                        '_children' => [
                                        ],
                                    ],
                                ],
                            ],
                            [
                                'uid' => 1400,
                                'title' => 'EN: ACME in your Region',
                                '_children' => [
                                    [
                                        'uid' => 1410,
                                        'title' => 'EN: Groups',
                                        '_children' => [
                                        ],
                                    ],
                                ],
                            ],
                            [
                                'uid' => 1500,
                                'title' => 'Internal',
                                '_children' => [
                                    [
                                        'uid' => 1520,
                                        'title' => 'Forecasts',
                                        '_children' => [],
                                    ],
                                    [
                                        'uid' => 1530,
                                        'title' => 'Reports',
                                        '_children' => [
                                        ],
                                    ],
                                ],
                            ],
                            [
                                'uid' => 1700,
                                'title' => 'Announcements & News',
                                '_children' => [
                                ],
                            ],
                            [
                                'uid' => 404,
                                'title' => 'Page not found',
                                '_children' => [
                                ],
                            ],
                            [
                                'uid' => 1930,
                                'title' => 'Our Blog',
                                '_children' => [
                                ],
                            ],
                            [
                                'uid' => 1990,
                                'title' => 'Storage',
                                '_children' => [
                                ],
                            ],
                        ],
                    ],
                    [
                        'uid' => 8110,
                        'title' => 'Europe',
                        '_children' => [
                            [
                                'uid' => 811000,
                                'title' => 'France',
                                '_children' => [],
                            ],
                        ],
                    ],
                ],
            ],
        ];
        self::assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function getAllEntryPointPageTreesWithRootPageAsMountPoint(): void
    {
        $this->backendUser->setWebMounts([0, 7000]);
        $actual = $this->subject->_call('getAllEntryPointPageTrees');
        $keepProperties = array_flip(['uid', 'title', '_children']);
        $actual = $this->sortTreeArray($actual);
        $actual = $this->normalizeTreeArray($actual, $keepProperties);

        $expected = [
            [
                'uid' => 0,
                'title' => 'New TYPO3 site',
                '_children' => [
                    [
                        'uid' => 1000,
                        'title' => 'ACME Inc',
                        '_children' => [
                            [
                                'uid' => 1100,
                                'title' => 'EN: Welcome',
                                '_children' => [
                                ],
                            ],
                            [
                                'uid' => 1200,
                                'title' => 'EN: Features',
                                '_children' => [
                                ],
                            ],
                            [
                                'uid' => 1400,
                                'title' => 'EN: ACME in your Region',
                                '_children' => [
                                ],
                            ],
                            [
                                'uid' => 1500,
                                'title' => 'Internal',
                                '_children' => [
                                ],
                            ],
                            [
                                'uid' => 1700,
                                'title' => 'Announcements & News',
                                '_children' => [
                                ],
                            ],
                            [
                                'uid' => 404,
                                'title' => 'Page not found',
                                '_children' => [
                                ],
                            ],
                            [
                                'uid' => 1930,
                                'title' => 'Our Blog',
                                '_children' => [
                                ],
                            ],
                            [
                                'uid' => 1990,
                                'title' => 'Storage',
                                '_children' => [
                                ],
                            ],
                        ],
                    ],
                    [
                        'uid' => 7000,
                        'title' => 'Common Collection',
                        '_children' => [
                            [
                                'uid' => 7100,
                                'title' => 'Announcements & News',
                                '_children' => [],
                            ],
                        ],
                    ],
                    [
                        // 9100 is shown due to `perms_everybody=15`
                        'uid' => 9100,
                        'title' => 'Page 9100',
                        '_children' => [],
                    ],
                    // 9200 is omitted due to `perms_everybody=0`
                    [
                        // 9300 is shown due to `perms_everybody=15`
                        'uid' => 9300,
                        'title' => 'Page 9300',
                        '_children' => [],
                    ],
                ],
            ],
            [
                'uid' => 7000,
                'title' => 'Common Collection',
                '_children' => [
                    [
                        'uid' => 7100,
                        'title' => 'Announcements & News',
                        '_children' => [
                            [
                                'uid' => 7110,
                                'title' => 'Markets',
                                '_children' => [],
                            ],
                            [
                                'uid' => 7120,
                                'title' => 'Products',
                                '_children' => [],
                            ],
                            [
                                'uid' => 7130,
                                'title' => 'Partners',
                                '_children' => [],
                            ],
                        ],
                    ],
                ],
            ],
        ];
        self::assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function getAllEntryPointPageTreesWithSearch(): void
    {
        $actual = $this->subject->_call('getAllEntryPointPageTrees', 0, 'Groups');
        $keepProperties = array_flip(['uid', 'title', '_children']);
        $actual = $this->sortTreeArray($actual);
        $actual = $this->normalizeTreeArray($actual, $keepProperties);

        $expected = [
            [
                'uid' => 0,
                'title' => 'New TYPO3 site',
                '_children' => [
                    [
                        'uid' => 1000,
                        'title' => 'ACME Inc',
                        '_children' => [
                            [
                                'uid' => 1400,
                                'title' => 'EN: ACME in your Region',
                                '_children' => [
                                    [
                                        'uid' => 1410,
                                        'title' => 'EN: Groups',
                                        '_children' => [
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    [
                        'uid' => 8110,
                        'title' => 'Europe',
                        '_children' => [
                        ],
                    ],
                ],
            ],
        ];
        self::assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function getSubtreeForAccessiblePage(): void
    {
        $actual = $this->subject->_call('getAllEntryPointPageTrees', 1200);
        $keepProperties = array_flip(['uid', 'title', '_children']);
        $actual = $this->sortTreeArray($actual);
        $actual = $this->normalizeTreeArray($actual, $keepProperties);

        $expected = [
            [
                'uid' => 1200,
                'title' => 'EN: Features',
                '_children' => [
                    [
                        'uid' => 1210,
                        'title' => 'EN: Frontend Editing',
                        '_children' => [
                        ],
                    ],
                    [
                        'uid' => 1230,
                        'title' => 'EN: Managing content',
                        '_children' => [
                        ],
                    ],
                ],
            ],
        ];
        self::assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function getSubtreeForNonAccessiblePage(): void
    {
        $actual = $this->subject->_call('getAllEntryPointPageTrees', 1510);
        $keepProperties = array_flip(['uid', 'title', '_children']);
        $actual = $this->sortTreeArray($actual);
        $actual = $this->normalizeTreeArray($actual, $keepProperties);

        $expected = [];
        self::assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function getSubtreeForPageOutsideMountPoint(): void
    {
        $actual = $this->subject->_call('getAllEntryPointPageTrees', 7000);
        $keepProperties = array_flip(['uid', 'title', '_children']);
        $actual = $this->sortTreeArray($actual);
        $actual = $this->normalizeTreeArray($actual, $keepProperties);

        $expected = [];
        self::assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function getAllEntryPointPageTreesWithMountPointPreservesOrdering(): void
    {
        $this->backendUser->setWebmounts([1210, 1100]);
        $actual = $this->subject->_call('getAllEntryPointPageTrees');
        $keepProperties = array_flip(['uid', 'title', '_children']);
        $actual = $this->sortTreeArray($actual);
        $actual = $this->normalizeTreeArray($actual, $keepProperties);

        $expected = [
            [
                'uid' => 0,
                'title' => 'New TYPO3 site',
                '_children' => [
                    [
                        'uid' => 1210,
                        'title' => 'EN: Frontend Editing',
                        '_children' => [
                        ],
                    ],
                    [
                        'uid' => 1100,
                        'title' => 'EN: Welcome',
                        '_children' => [
                        ],
                    ],
                ],
            ],
        ];
        self::assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function getAllEntryPointPageTreesInWorkspace(): void
    {
        $this->setWorkspace(1);
        $actual = $this->subject->_call('getAllEntryPointPageTrees');
        $keepProperties = array_flip(['uid', 'title', '_children']);
        $actual = $this->sortTreeArray($actual);
        $actual = $this->normalizeTreeArray($actual, $keepProperties);

        $expected = [
            [
                'uid' => 0,
                'title' => 'New TYPO3 site',
                '_children' => [
                    [
                        'uid' => 1000,
                        'title' => 'ACME Inc',
                        '_children' => [
                            [
                                'uid' => 1950,
                                'title' => 'EN: Goodbye',
                                '_children' => [
                                    [
                                        'uid' => 10015,
                                        'title' => 'EN: Really Goodbye',
                                        '_children' => [
                                        ],
                                    ],
                                ],
                            ],
                            [
                                'uid' => 1100,
                                'title' => 'EN: Welcome',
                                '_children' => [
                                ],
                            ],
                            [
                                'uid' => 1200,
                                'title' => 'EN: Features modified',
                                '_children' => [
                                    [
                                        'uid' => 1240,
                                        'title' => 'EN: Managing data',
                                        '_children' => [],
                                    ],
                                    [
                                        'uid' => 1230,
                                        'title' => 'EN: Managing content',
                                        '_children' => [
                                        ],
                                    ],
                                ],
                            ],
                            [
                                'uid' => 1500,
                                'title' => 'Internal',
                                '_children' => [
                                    [
                                        'uid' => 1520,
                                        'title' => 'Forecasts',
                                        '_children' => [],
                                    ],
                                    [
                                        'uid' => 1530,
                                        'title' => 'Reports',
                                        '_children' => [
                                        ],
                                    ],
                                ],
                            ],
                            [
                                'uid' => 1700,
                                'title' => 'Announcements & News',
                                '_children' => [
                                    [
                                        // page moved in workspace 1
                                        // from pid 8110 to pid 1700 (visible now)
                                        'uid' => 811000,
                                        'title' => 'France',
                                        '_children' => [],
                                    ],
                                    [
                                        // page with sub-pages moved in workspace 1
                                        // from pid 1510 (missing permissions) to pid 1700 (visible now)
                                        'uid' => 1511,
                                        'title' => 'Products',
                                        '_children' => [],
                                    ],
                                ],
                            ],
                            [
                                'uid' => 404,
                                'title' => 'Page not found',
                                '_children' => [
                                ],
                            ],
                            [
                                'uid' => 1930,
                                'title' => 'Our Blog',
                                '_children' => [
                                ],
                            ],
                            [
                                'uid' => 1990,
                                'title' => 'Storage',
                                '_children' => [
                                ],
                            ],
                        ],
                    ],
                    [
                        'uid' => 8110,
                        'title' => 'Europe',
                        '_children' => [
                        ],
                    ],
                ],
            ],
        ];
        self::assertEquals($expected, $actual);
    }

    public function getAllEntryPointPageTreesInWorkspaceWithSearchDataProvider(): array
    {
        return [
            'search for "ACME in your Region" (live value, but deleted in workspace)' => [
                'ACME in your Region',
                [],
            ],
            'search for non-existing value' => [
                sha1(random_bytes(10)),
                [],
            ],
            'search for "groups" (live value, but changed in workspace)' => [
                'Groups',
                [],
            ],
            'search for "teams" (workspace value)' => [
                'Teams',
                [
                    [
                        'uid' => 1400,
                        'title' => 'EN: ACME in your Region',
                        '_children' => [
                            [
                                'uid' => 1410,
                                'title' => 'EN: Teams modified',
                                '_children' => [],
                            ],
                        ],
                    ],
                ],
            ],
            // page with sub-pages moved in workspace 1
            // from pid 1510 (missing permissions) to pid 1700 (visible now)
            'search for "products" (moved from pid 1510 to pid 1700 in workspace)' => [
                'Product',
                [
                    [
                        'uid' => 1700,
                        'title' => 'Announcements & News',
                        '_children' => [
                            [
                                'uid' => 1511,
                                'title' => 'Products',
                                '_children' => [
                                    [
                                        'uid' => 151110,
                                        'title' => 'Product 1',
                                        '_children' => [],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @param string $search
     * @param array $expectedChildren
     *
     * @test
     * @dataProvider getAllEntryPointPageTreesInWorkspaceWithSearchDataProvider
     */
    public function getAllEntryPointPageTreesInWorkspaceWithSearch(string $search, array $expectedChildren): void
    {
        $this->setWorkspace(1);
        // the record was changed from live "Groups" to "Teams modified" in a workspace
        $actual = $this->subject->_call('getAllEntryPointPageTrees', 0, $search);
        $keepProperties = array_flip(['uid', 'title', '_children']);
        $actual = $this->sortTreeArray($actual);
        $actual = $this->normalizeTreeArray($actual, $keepProperties);

        $expected = [
            [
                'uid' => 0,
                'title' => 'New TYPO3 site',
                '_children' => [
                    [
                        'uid' => 1000,
                        'title' => 'ACME Inc',
                        '_children' => $expectedChildren,
                    ],
                    [
                        'uid' => 8110,
                        'title' => 'Europe',
                        '_children' => [
                        ],
                    ],
                ],
            ],
        ];
        self::assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function getSubtreeForAccessiblePageInWorkspace(): void
    {
        $this->setWorkspace(1);
        $actual = $this->subject->_call('getAllEntryPointPageTrees', 1200);
        $keepProperties = array_flip(['uid', 'title', '_children']);
        $actual = $this->sortTreeArray($actual);
        $actual = $this->normalizeTreeArray($actual, $keepProperties);

        $expected = [
            [
                'uid' => 1200,
                'title' => 'EN: Features modified',
                '_children' => [
                    [
                        'uid' => 1240,
                        'title' => 'EN: Managing data',
                        '_children' => [
                            [
                                'uid' => 124010,
                                'title' => 'EN: Managing complex data',
                                '_children' => [],
                            ],
                        ],
                    ],
                    [
                        'uid' => 1230,
                        'title' => 'EN: Managing content',
                        '_children' => [
                        ],
                    ],
                ],
            ],
        ];
        self::assertEquals($expected, $actual);
    }

    public static function fetchDataActionConsidersPermissionsDataProvider(): \Generator
    {
        yield 'admin user can see all root pages' => [
            'backendUser' => 1,
            'expectation' => ['0', '1000', '2000', '7000', '8000', '9100', '9200', '9300'],
        ];
        yield 'editor with DB mounts can only see accessible pages' => [
            'backendUser' => 9,
            'expectation' => ['0', '1000', '8110'],
        ];
        yield 'editor with DB mounts cannot see inaccessible pages' => [
            'backendUser' => 8,
            'expectation' => ['0'],
        ];
        yield 'editor without DB mounts cannot see any pages' => [
            'backendUser' => 7,
            'expectation' => ['0'],
        ];
    }

    /**
     * @test
     * @dataProvider fetchDataActionConsidersPermissionsDataProvider
     */
    public function fetchDataActionConsidersPermissions(int $backendUser, array $expectation): void
    {
        $this->backendUser = $this->setUpBackendUser($backendUser);
        $request = (new ServerRequest(new Uri('https://example.com')))->withQueryParams(['depth' => 1]);
        $response = (new TreeController())->fetchDataAction($request);
        $data = json_decode((string)$response->getBody(), true);
        $items = array_filter($data, static fn(array $page): bool => $page['depth'] <= 1);
        $items = array_map(static fn(array $page): string => $page['identifier'], $items);
        self::assertSame($expectation, array_values($items));
    }

    public static function filterDataActionResolvesNestedPagesDataProvider(): \Generator
    {
        yield 'searching "Forecasts"' => [
            'query' => 'Forecasts',
            'expectation' => [
                [
                    'identifier' => '0',
                    'depth' => 0,
                    'name' => 'New TYPO3 site',
                    'hasChildren' => true,
                ],
                [
                    'identifier' => '1000',
                    'depth' => 1,
                    'name' => 'ACME Inc',
                    'hasChildren' => true,
                ],
                [
                    'identifier' => '1500',
                    'depth' => 2,
                    'name' => 'Internal',
                    'hasChildren' => true,
                ],
                [
                    'identifier' => '1520',
                    'depth' => 3,
                    'name' => 'Forecasts',
                    // see https://forge.typo3.org/issues/105239
                    'hasChildren' => true,
                ],
                [
                    'identifier' => '8110',
                    'depth' => 1,
                    'name' => 'Europe',
                    'hasChildren' => true,
                ],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider filterDataActionResolvesNestedPagesDataProvider
     */
    public function filterDataActionResolvesNestedPages(string $query, array $expectation): void
    {
        $filterProperties = ['identifier', 'depth', 'name', 'hasChildren'];
        $request = (new ServerRequest(new Uri('https://example.com')))->withQueryParams(['q' => $query]);
        $response = (new TreeController())->filterDataAction($request);
        $data = json_decode((string)$response->getBody(), true);
        $items = array_map(
            static fn(array $page): array => array_filter(
                $page,
                static fn(string $property): bool => in_array($property, $filterProperties, true),
                ARRAY_FILTER_USE_KEY
            ),
            $data,
        );
        self::assertSame($expectation, $items);
    }

    /**
     * @param int $workspaceId
     */
    private function setWorkspace(int $workspaceId): void
    {
        $this->backendUser->workspace = $workspaceId;
        $this->context->setAspect('workspace', new WorkspaceAspect($workspaceId));
    }
}
