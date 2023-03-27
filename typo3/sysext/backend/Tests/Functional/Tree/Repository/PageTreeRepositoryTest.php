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

use TYPO3\CMS\Backend\Tests\Functional\Tree\Repository\Fixtures\Tree\NormalizeTreeTrait;
use TYPO3\CMS\Backend\Tree\Repository\PageTreeRepository;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class PageTreeRepositoryTest extends FunctionalTestCase
{
    use NormalizeTreeTrait;

    public function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet('typo3/sysext/backend/Tests/Functional/Tree/Repository/Fixtures/PageTree.csv');
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
                                ],
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
                                ],
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
                                ],
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
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider getTreeLevelsReturnsGroupedAndSortedPageTreeArrayDataProvider
     * @test
     */
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
    }

    /**
     * @test
     * @dataProvider fetchFilteredTreeDataProvider
     */
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
}
