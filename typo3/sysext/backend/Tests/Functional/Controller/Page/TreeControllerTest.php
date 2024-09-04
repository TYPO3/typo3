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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\DependencyInjection\Container;
use TYPO3\CMS\Backend\Controller\Event\AfterPageTreeItemsPreparedEvent;
use TYPO3\CMS\Backend\Controller\Page\TreeController;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Tests\Functional\Tree\Repository\Fixtures\Tree\NormalizeTreeTrait;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\WorkspaceAspect;
use TYPO3\CMS\Core\EventDispatcher\ListenerProvider;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use TYPO3\TestingFramework\Core\Functional\Framework\DataHandling\Scenario\DataHandlerFactory;
use TYPO3\TestingFramework\Core\Functional\Framework\DataHandling\Scenario\DataHandlerWriter;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class TreeControllerTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;
    use NormalizeTreeTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8'],
    ];

    protected array $coreExtensionsToLoad = ['workspaces'];

    private BackendUserAuthentication $backendUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withDatabaseSnapshot(function () {
            $this->importCSVDataSet(__DIR__ . '/Fixtures/be_users.csv');
            // Admin user for importing dataset
            $this->backendUser = $this->setUpBackendUser(1);
            $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($this->backendUser);
            $scenarioFile = __DIR__ . '/Fixtures/PagesWithBEPermissions.yaml';
            $factory = DataHandlerFactory::fromYamlFile($scenarioFile);
            $writer = DataHandlerWriter::withBackendUser($this->backendUser);
            $writer->invokeFactory($factory);
            self::failIfArrayIsNotEmpty($writer->getErrors());
        }, function () {
            $this->backendUser = $this->setUpBackendUser(1);
            $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($this->backendUser);
        });

        // Regular editor, non admin
        $this->backendUser = $this->setUpBackendUser(9);
    }

    #[Test]
    public function getAllEntryPointPageTrees(): void
    {
        $subject = $this->getAccessibleMock(
            TreeController::class,
            null,
            [
                $this->get(IconFactory::class),
                $this->get(UriBuilder::class),
                $this->get(EventDispatcherInterface::class),
                $this->get(SiteFinder::class),
            ]
        );
        $actual = $subject->_call('getAllEntryPointPageTrees');
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

    #[Test]
    public function getAllEntryPointPageTreesWithRootPageAsMountPoint(): void
    {
        $this->backendUser->setWebMounts([0, 7000]);
        $subject = $this->getAccessibleMock(
            TreeController::class,
            null,
            [
                $this->get(IconFactory::class),
                $this->get(UriBuilder::class),
                $this->get(EventDispatcherInterface::class),
                $this->get(SiteFinder::class),
            ]
        );
        $actual = $subject->_call('getAllEntryPointPageTrees');
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

    #[Test]
    public function getAllEntryPointPageTreesWithSearch(): void
    {
        $subject = $this->getAccessibleMock(
            TreeController::class,
            null,
            [
                $this->get(IconFactory::class),
                $this->get(UriBuilder::class),
                $this->get(EventDispatcherInterface::class),
                $this->get(SiteFinder::class),
            ]
        );
        $actual = $subject->_call('getAllEntryPointPageTrees', 0, 'Groups');
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

    #[Test]
    public function getSubtreeForAccessiblePage(): void
    {
        $subject = $this->getAccessibleMock(
            TreeController::class,
            null,
            [
                $this->get(IconFactory::class),
                $this->get(UriBuilder::class),
                $this->get(EventDispatcherInterface::class),
                $this->get(SiteFinder::class),
            ]
        );
        $actual = $subject->_call('getAllEntryPointPageTrees', 1200);
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

    #[Test]
    public function getSubtreeForNonAccessiblePage(): void
    {
        $subject = $this->getAccessibleMock(
            TreeController::class,
            null,
            [
                $this->get(IconFactory::class),
                $this->get(UriBuilder::class),
                $this->get(EventDispatcherInterface::class),
                $this->get(SiteFinder::class),
            ]
        );
        $actual = $subject->_call('getAllEntryPointPageTrees', 1510);
        $keepProperties = array_flip(['uid', 'title', '_children']);
        $actual = $this->sortTreeArray($actual);
        $actual = $this->normalizeTreeArray($actual, $keepProperties);

        $expected = [];
        self::assertEquals($expected, $actual);
    }

    #[Test]
    public function getSubtreeForPageOutsideMountPoint(): void
    {
        $subject = $this->getAccessibleMock(
            TreeController::class,
            null,
            [
                $this->get(IconFactory::class),
                $this->get(UriBuilder::class),
                $this->get(EventDispatcherInterface::class),
                $this->get(SiteFinder::class),
            ]
        );
        $actual = $subject->_call('getAllEntryPointPageTrees', 7000);
        $keepProperties = array_flip(['uid', 'title', '_children']);
        $actual = $this->sortTreeArray($actual);
        $actual = $this->normalizeTreeArray($actual, $keepProperties);

        $expected = [];
        self::assertEquals($expected, $actual);
    }

    #[Test]
    public function getAllEntryPointPageTreesWithMountPointPreservesOrdering(): void
    {
        $this->backendUser->setWebmounts([1210, 1100]);
        $subject = $this->getAccessibleMock(
            TreeController::class,
            null,
            [
                $this->get(IconFactory::class),
                $this->get(UriBuilder::class),
                $this->get(EventDispatcherInterface::class),
                $this->get(SiteFinder::class),
            ]
        );
        $actual = $subject->_call('getAllEntryPointPageTrees');
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

    #[Test]
    public function getAllEntryPointPageTreesInWorkspace(): void
    {
        $this->backendUser->workspace = 1;
        $context = $this->get(Context::class);
        $context->setAspect('workspace', new WorkspaceAspect(1));
        $subject = $this->getAccessibleMock(
            TreeController::class,
            null,
            [
                $this->get(IconFactory::class),
                $this->get(UriBuilder::class),
                $this->get(EventDispatcherInterface::class),
                $this->get(SiteFinder::class),
            ]
        );
        $actual = $subject->_call('getAllEntryPointPageTrees');
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

    public static function getAllEntryPointPageTreesInWorkspaceWithSearchDataProvider(): array
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

    #[DataProvider('getAllEntryPointPageTreesInWorkspaceWithSearchDataProvider')]
    #[Test]
    public function getAllEntryPointPageTreesInWorkspaceWithSearch(string $search, array $expectedChildren): void
    {
        $this->backendUser->workspace = 1;
        $context = $this->get(Context::class);
        $context->setAspect('workspace', new WorkspaceAspect(1));
        // the record was changed from live "Groups" to "Teams modified" in a workspace
        $subject = $this->getAccessibleMock(
            TreeController::class,
            null,
            [
                $this->get(IconFactory::class),
                $this->get(UriBuilder::class),
                $this->get(EventDispatcherInterface::class),
                $this->get(SiteFinder::class),
            ]
        );
        $actual = $subject->_call('getAllEntryPointPageTrees', 0, $search);
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

    #[Test]
    public function getSubtreeForAccessiblePageInWorkspace(): void
    {
        $this->backendUser->workspace = 1;
        $context = $this->get(Context::class);
        $context->setAspect('workspace', new WorkspaceAspect(1));
        $subject = $this->getAccessibleMock(
            TreeController::class,
            null,
            [
                $this->get(IconFactory::class),
                $this->get(UriBuilder::class),
                $this->get(EventDispatcherInterface::class),
                $this->get(SiteFinder::class),
            ]
        );
        $actual = $subject->_call('getAllEntryPointPageTrees', 1200);
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

    #[Test]
    public function pageTreeItemsModificationEventIsTriggered(): void
    {
        $afterPageTreeItemsPreparedEvent = null;

        /** @var Container $container */
        $container = $this->get('service_container');
        $container->set(
            'after-page-tree-items-prepared-listener',
            static function (AfterPageTreeItemsPreparedEvent $event) use (&$afterPageTreeItemsPreparedEvent) {
                $afterPageTreeItemsPreparedEvent = $event;
            }
        );

        $eventListener = $container->get(ListenerProvider::class);
        $eventListener->addListener(AfterPageTreeItemsPreparedEvent::class, 'after-page-tree-items-prepared-listener');

        $request = new ServerRequest(new Uri('https://example.com'));

        $this->get(TreeController::class)->fetchDataAction($request);

        self::assertInstanceOf(AfterPageTreeItemsPreparedEvent::class, $afterPageTreeItemsPreparedEvent);
        self::assertEquals($request, $afterPageTreeItemsPreparedEvent->getRequest());
        self::assertCount(12, $afterPageTreeItemsPreparedEvent->getItems());
        self::assertEquals('1000', $afterPageTreeItemsPreparedEvent->getItems()[1]['identifier']);
        self::assertEquals('ACME Inc', $afterPageTreeItemsPreparedEvent->getItems()[1]['name']);
    }
}
