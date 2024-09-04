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

namespace TYPO3\CMS\Backend\Tests\Functional\Tree\Repository;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\DependencyInjection\Container;
use TYPO3\CMS\Backend\Tests\Functional\Tree\Repository\Fixtures\Tree\NormalizeTreeTrait;
use TYPO3\CMS\Backend\Tree\Repository\AfterRawPageRowPreparedEvent;
use TYPO3\CMS\Backend\Tree\Repository\PageTreeRepository;
use TYPO3\CMS\Core\EventDispatcher\ListenerProvider;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class PageTreeRepositoryTest extends FunctionalTestCase
{
    use NormalizeTreeTrait;

    public function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/PageTree.csv');
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/be_users.csv');
        $this->setUpBackendUser(1);
    }

    public static function getTreeLevelsReturnsGroupedAndSortedPageTreeArrayDataProvider(): iterable
    {
        yield 'Single entry point with depth 2' => [
            'pageTree' => [
                'uid' => 0,
                'title' => 'Core',
            ],
            'depth' => 2,
            'entryPointIds' => [
                2,
            ],
            'expected' => [
                'uid' => 0,
                'title' => 'Core',
                '_children' => [
                    [
                        'uid' => 2,
                        'title' => 'Main Area',
                        '_children' => [
                            [
                                'uid' => 20,
                                'title' => 'Main Area Sub 1',
                                '_children' => [],
                            ],
                            [
                                'uid' => 21,
                                'title' => 'Main Area Sub 2',
                                '_children' => [
                                    [
                                        'uid' => 30,
                                        'title' => 'Sub Area 1',
                                        '_children' => [],
                                    ],
                                    [
                                        'uid' => 31,
                                        'title' => 'Sub Area 2',
                                        '_children' => [],
                                    ],
                                    [
                                        'uid' => 32,
                                        'title' => 'Sub Area 3',
                                        '_children' => [],
                                    ],
                                ],
                            ],
                            [
                                'uid' => 22,
                                'title' => 'Main Area Sub 3 (called 30,32)',
                                '_children' => [],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        yield 'Single entry point with depth 1' => [
            'pageTree' => [
                'uid' => 0,
                'title' => 'Core',
            ],
            'depth' => 1,
            'entryPointIds' => [
                2,
            ],
            'expected' => [
                'uid' => 0,
                'title' => 'Core',
                '_children' => [
                    [
                        'uid' => 2,
                        'title' => 'Main Area',
                        '_children' => [
                            [
                                'uid' => 20,
                                'title' => 'Main Area Sub 1',
                                '_children' => [],
                            ],
                            [
                                'uid' => 21,
                                'title' => 'Main Area Sub 2',
                                '_children' => [],
                            ],
                            [
                                'uid' => 22,
                                'title' => 'Main Area Sub 3 (called 30,32)',
                                '_children' => [],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        yield 'Two entry points parallel to each other' => [
            'pageTree' => [
                'uid' => 0,
                'title' => 'Core',
            ],
            'depth' => 2,
            'entryPointIds' => [
                2,
                3,
            ],
            'expected' => [
                'uid' => 0,
                'title' => 'Core',
                '_children' => [
                    [
                        'uid' => 2,
                        'title' => 'Main Area',
                        '_children' => [
                            [
                                'uid' => 20,
                                'title' => 'Main Area Sub 1',
                                '_children' => [],
                            ],
                            [
                                'uid' => 21,
                                'title' => 'Main Area Sub 2',
                                '_children' => [
                                    [
                                        'uid' => 30,
                                        'title' => 'Sub Area 1',
                                        '_children' => [],
                                    ],
                                    [
                                        'uid' => 31,
                                        'title' => 'Sub Area 2',
                                        '_children' => [],
                                    ],
                                    [
                                        'uid' => 32,
                                        'title' => 'Sub Area 3',
                                        '_children' => [],
                                    ],
                                ],
                            ],
                            [
                                'uid' => 22,
                                'title' => 'Main Area Sub 3 (called 30,32)',
                                '_children' => [],
                            ],
                        ],
                    ],
                    [
                        'uid' => 3,
                        'title' => 'Home 2',
                        '_children' => [],
                    ],
                ],
            ],
        ];

        yield 'Two entry points intersecting each other' => [
            'pageTree' => [
                'uid' => 0,
                'title' => 'Core',
            ],
            'depth' => 2,
            'entryPointIds' => [
                2,
                21,
            ],
            'expected' => [
                'uid' => 0,
                'title' => 'Core',
                '_children' => [
                    [
                        'uid' => 2,
                        'title' => 'Main Area',
                        '_children' => [
                            [
                                'uid' => 20,
                                'title' => 'Main Area Sub 1',
                                '_children' => [],
                            ],
                            [
                                'uid' => 21,
                                'title' => 'Main Area Sub 2',
                                '_children' => [
                                    [
                                        'uid' => 30,
                                        'title' => 'Sub Area 1',
                                        '_children' => [],
                                    ],
                                    [
                                        'uid' => 31,
                                        'title' => 'Sub Area 2',
                                        '_children' => [],
                                    ],
                                    [
                                        'uid' => 32,
                                        'title' => 'Sub Area 3',
                                        '_children' => [],
                                    ],
                                ],
                            ],
                            [
                                'uid' => 22,
                                'title' => 'Main Area Sub 3 (called 30,32)',
                                '_children' => [],
                            ],
                        ],
                    ],
                    [
                        'uid' => 21,
                        'title' => 'Main Area Sub 2',
                        '_children' => [
                            [
                                'uid' => 30,
                                'title' => 'Sub Area 1',
                                '_children' => [],
                            ],
                            [
                                'uid' => 31,
                                'title' => 'Sub Area 2',
                                '_children' => [],
                            ],
                            [
                                'uid' => 32,
                                'title' => 'Sub Area 3',
                                '_children' => [],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    #[DataProvider('getTreeLevelsReturnsGroupedAndSortedPageTreeArrayDataProvider')]
    #[Test]
    public function getTreeLevelsReturnsGroupedAndSortedPageTreeArray(array $pageTree, int $depth, array $entryPointIds, array $expected): void
    {
        $pageTreeRepository = new PageTreeRepository();
        $actual = $pageTreeRepository->getTreeLevels($pageTree, $depth, $entryPointIds);
        $actual = $this->sortTreeArray([$actual]);
        $keepProperties = array_flip(['uid', 'title', '_children']);
        $actual = $this->normalizeTreeArray($actual, $keepProperties);
        self::assertEquals($expected, $actual[0]);
    }

    public static function fetchFilteredTreeDataProvider(): \Generator
    {
        yield 'Third level find by title' => [
            'Sub Area 2',
            0,
            2,
            [
                'uid' => 2,
                'title' => 'Main Area',
                '_children' => [
                    [
                        'uid' => 21,
                        'title' => 'Main Area Sub 2',
                        '_children' => [
                            [
                                'uid' => 31,
                                'title' => 'Sub Area 2',
                                '_children' => [],
                            ],
                        ],
                    ],
                ],
            ],
        ];
        yield 'Second level find by UID' => [
            '2',
            0,
            1,
            [
                'uid' => 1,
                'title' => 'Home',
                '_children' => [
                    [
                        'uid' => 2,
                        'title' => 'Main Area',
                        '_children' => [
                            [
                                'uid' => 21,
                                'title' => 'Main Area Sub 2',
                                '_children' => [
                                    [
                                        'uid' => 31,
                                        'title' => 'Sub Area 2',
                                        '_children' => [],
                                    ],
                                ],
                            ],
                            [
                                'uid' => 22,
                                'title' => 'Main Area Sub 3 (called 30,32)',
                                '_children' => [],
                            ],
                        ],
                    ],
                ],
            ],
        ];
        yield 'Second level find by UID in workspace' => [
            '20',
            1,
            2,
            [
                'uid' => 2,
                'title' => 'Main Area',
                '_children' => [
                    [
                        'uid' => 20,
                        'title' => 'Main Area Sub 1 Modified',
                        '_children' => [],
                    ],
                ],
            ],
        ];
        yield 'Two finds by comma separated UIDs' => [
            '30,31',
            0,
            1,
            [
                'uid' => 1,
                'title' => 'Home',
                '_children' => [
                    [
                        'uid' => 2,
                        'title' => 'Main Area',
                        '_children' => [
                            [
                                'uid' => 21,
                                'title' => 'Main Area Sub 2',
                                '_children' => [
                                    [
                                        'uid' => 30,
                                        'title' => 'Sub Area 1',
                                        '_children' => [],
                                    ],
                                    [
                                        'uid' => 31,
                                        'title' => 'Sub Area 2',
                                        '_children' => [],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
        yield 'Three finds by comma separated UIDs' => [
            '30,32',
            0,
            1,
            [
                'uid' => 1,
                'title' => 'Home',
                '_children' => [
                    [
                        'uid' => 2,
                        'title' => 'Main Area',
                        '_children' => [
                            [
                                'uid' => 21,
                                'title' => 'Main Area Sub 2',
                                '_children' => [
                                    [
                                        'uid' => 30,
                                        'title' => 'Sub Area 1',
                                        '_children' => [],
                                    ],
                                    [
                                        'uid' => 32,
                                        'title' => 'Sub Area 3',
                                        '_children' => [],
                                    ],
                                ],
                            ],
                            [
                                'uid' => 22,
                                'title' => 'Main Area Sub 3 (called 30,32)',
                                '_children' => [],
                            ],
                        ],
                    ],
                ],
            ],
        ];
        yield 'Two finds by comma separated UIDs and a string' => [
            '30,string,31',
            0,
            1,
            [
                'uid' => 1,
                'title' => 'Home',
                '_children' => [
                    [
                        'uid' => 2,
                        'title' => 'Main Area',
                        '_children' => [
                            [
                                'uid' => 21,
                                'title' => 'Main Area Sub 2',
                                '_children' => [
                                    [
                                        'uid' => 30,
                                        'title' => 'Sub Area 1',
                                        '_children' => [],
                                    ],
                                    [
                                        'uid' => 31,
                                        'title' => 'Sub Area 2',
                                        '_children' => [],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
        yield 'One find by comma separated negative and positive UIDs' => [
            '-30,31',
            0,
            1,
            [
                'uid' => 1,
                'title' => 'Home',
                '_children' => [
                    [
                        'uid' => 2,
                        'title' => 'Main Area',
                        '_children' => [
                            [
                                'uid' => 21,
                                'title' => 'Main Area Sub 2',
                                '_children' => [
                                    [
                                        'uid' => 31,
                                        'title' => 'Sub Area 2',
                                        '_children' => [],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
        yield 'No finds by arbitrary string' => [
            bin2hex(random_bytes(20)),
            0,
            0,
            [
                'uid' => 0,
                'title' => 'New TYPO3 site',
                '_children' => [],
            ],
        ];
        yield 'No finds by string starting with int' => [
            '30isAnInteger',
            0,
            0,
            [
                'uid' => 0,
                'title' => 'New TYPO3 site',
                '_children' => [],
            ],
        ];
        yield 'No finds by string ending with int' => [
            'AnIntegerIs30',
            0,
            0,
            [
                'uid' => 0,
                'title' => 'New TYPO3 site',
                '_children' => [],
            ],
        ];
        yield 'No finds by float' => [
            '30.0',
            0,
            0,
            [
                'uid' => 0,
                'title' => 'New TYPO3 site',
                '_children' => [],
            ],
        ];
        yield 'No finds by exponential format' => [
            '2e+1', // that's a 20
            0,
            0,
            [
                'uid' => 0,
                'title' => 'New TYPO3 site',
                '_children' => [],
            ],
        ];
    }

    #[DataProvider('fetchFilteredTreeDataProvider')]
    #[Test]
    public function fetchFilteredTreeShowsResults(string $search, int $workspaceId, int $entryPoint, array $expectedResult): void
    {
        $pageTreeRepository = new PageTreeRepository($workspaceId);
        $pageTreeRepository->fetchFilteredTree($search, [$entryPoint], '');
        $actual = $pageTreeRepository->getTree($entryPoint, null, [$entryPoint]);
        $actual = $this->sortTreeArray([$actual]);
        $keepProperties = array_flip(['uid', 'title', '_children']);
        $actual = $this->normalizeTreeArray($actual, $keepProperties);
        self::assertEquals($expectedResult, $actual[0]);
    }

    #[Test]
    public function afterRawPageRowPreparedEventIsCalled()
    {
        $afterRawPageRowPreparedEvent = [];
        $expectedResult = [
            'uid' => 1,
            'title' => 'Home',
            '_children' => [
                [
                    'uid' => 2,
                    'title' => 'Main Area',
                    '_children' => [
                        [
                            'uid' => 21,
                            'title' => 'Main Area Sub 2',
                            '_children' => [
                                // event listener drops children uid 31
                            ],
                        ],
                    ],
                ],
            ],
        ];

        /** @var Container $container */
        $container = $this->get('service_container');
        $container->set(
            'after-raw-page-row-prepared-listener',
            static function (AfterRawPageRowPreparedEvent $event) use (&$afterRawPageRowPreparedEvent) {
                $afterRawPageRowPreparedEvent[] = $event;
                $rawPage = $event->getRawPage();
                if ($rawPage['uid'] === 21) {
                    $rawPage['_children'] = [];
                    $event->setRawPage($rawPage);
                }
            }
        );

        $eventListener = $container->get(ListenerProvider::class);
        $eventListener->addListener(AfterRawPageRowPreparedEvent::class, 'after-raw-page-row-prepared-listener');

        $pageTreeRepository = new PageTreeRepository(0);
        $pageTreeRepository->fetchFilteredTree('-30,31', [1], '');
        $actual = $pageTreeRepository->getTree(1, null, [1]);
        $actual = $this->sortTreeArray([$actual]);
        $keepProperties = array_flip(['uid', 'title', '_children']);
        $actual = $this->normalizeTreeArray($actual, $keepProperties);
        self::assertEquals($expectedResult, $actual[0]);
        self::assertCount(4, $afterRawPageRowPreparedEvent);
    }
}
