<?php
namespace TYPO3\CMS\Core\Tests\Unit\Service;

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

use TYPO3\CMS\Core\Service\DependencyOrderingService;
use TYPO3\CMS\Core\Tests\UnitTestCase;

/**
 * Test case
 */
class DependencyOrderingServiceTest extends UnitTestCase
{
    /**
     * @test
     * @dataProvider orderByDependenciesBuildsCorrectOrderDataProvider
     * @param array $items
     * @param string $beforeKey
     * @param string $afterKey
     * @param array $expectedOrderedItems
     */
    public function orderByDependenciesBuildsCorrectOrder(array $items, $beforeKey, $afterKey, array $expectedOrderedItems)
    {
        $orderedItems = (new DependencyOrderingService())->orderByDependencies($items, $beforeKey, $afterKey);
        $this->assertSame($expectedOrderedItems, $orderedItems);
    }

    /**
     * @return array
     */
    public function orderByDependenciesBuildsCorrectOrderDataProvider()
    {
        return [
            'unordered' => [
                [ // $items
                    1 => [],
                    2 => [],
                ],
                'before',
                'after',
                [ // $expectedOrderedItems
                    1 => [],
                    2 => [],
                ]
            ],
            'ordered' => [
                [ // $items
                    1 => [],
                    2 => [
                        'precedes' => [ 1 ]
                    ],
                ],
                'precedes',
                'after',
                [ // $expectedOrderedItems
                    2 => [
                        'precedes' => [ 1 ]
                    ],
                    1 => [],
                ]
            ],
            'mixed' => [
                [ // $items
                    1 => [],
                    2 => [
                        'before' => [ 1 ]
                    ],
                    3 => [
                        'otherProperty' => true
                    ]
                ],
                'before',
                'after',
                [ // $expectedOrderedItems
                    2 => [
                        'before' => [ 1 ]
                    ],
                    1 => [],
                    3 => [
                        'otherProperty' => true
                    ],
                ]
            ],
            'reference to non-existing' => [
                [ // $items
                    2 => [
                        'before' => [ 1 ],
                        'depends' => [ 3 ]
                    ],
                    3 => [
                        'otherProperty' => true
                    ]
                ],
                'before',
                'depends',
                [ // $expectedOrderedItems
                    3 => [
                        'otherProperty' => true
                    ],
                    2 => [
                        'before' => [ 1 ],
                        'depends' => [ 3 ]
                    ],
                ]
            ],
            'multiple dependencies' => [
                [ // $items
                    1 => [
                        'depends' => [ 3, 2, 4 ],
                    ],
                    2 => [],
                    3 => [
                        'depends' => [ 2 ],
                    ],
                ],
                'before',
                'depends',
                [ // $expectedOrderedItems
                    2 => [],
                    3 => [
                        'depends' => [ 2 ],
                    ],
                    1 => [
                        'depends' => [ 3, 2, 4 ],
                    ],
                ],
            ],
            'direct dependency is moved up' => [
                [ // $items
                    1 => [],
                    2 => [],
                    3 => [
                        'depends' => [ 1 ],
                    ],
                ],
                'before',
                'depends',
                [ // $expectedOrderedItems
                    1 => [],
                    3 => [
                        'depends' => [ 1 ],
                    ],
                    2 => [],
                ],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider prepareDependenciesBuildsFullIdentifierListDataProvider
     * @param array $dependencies
     * @param $expectedDependencies
     * @throws \InvalidArgumentException
     */
    public function prepareDependenciesBuildsFullIdentifierList(array $dependencies, array $expectedDependencies)
    {
        /** @var DependencyOrderingService|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface $dependencyOrderingService */
        $dependencyOrderingService = $this->getAccessibleMock(DependencyOrderingService::class, ['dummy']);
        $preparedDependencies = $dependencyOrderingService->_call('prepareDependencies', $dependencies);
        $this->assertEquals($expectedDependencies, $preparedDependencies);
    }

    /**
     * @return array
     */
    public function prepareDependenciesBuildsFullIdentifierListDataProvider()
    {
        return [
            'simple' => [
                [ // $dependencies
                    1 => [
                        'before' => [],
                        'after' => [ 2 ]
                    ],
                ],
                [ // $expectedDependencies
                    1 => [
                        'before' => [],
                        'after' => [ 2 ]
                    ],
                    2 => [
                        'before' => [],
                        'after' => [],
                    ]
                ]
            ],
            'missing before' => [
                [ // $dependencies
                    1 => [
                        'after' => [ 2 ]
                    ],
                ],
                [ // $expectedDependencies
                    1 => [
                        'before' => [],
                        'after' => [ 2 ]
                    ],
                    2 => [
                        'before' => [],
                        'after' => [],
                    ]
                ]
            ],
        ];
    }

    /**
     * @test
     * @dataProvider buildDependencyGraphBuildsValidGraphDataProvider
     * @param array $dependencies
     * @param array $expectedGraph
     */
    public function buildDependencyGraphBuildsValidGraph(array $dependencies, array $expectedGraph)
    {
        $graph = (new DependencyOrderingService())->buildDependencyGraph($dependencies);
        $this->assertEquals($expectedGraph, $graph);
    }

    /**
     * @return array
     */
    public function buildDependencyGraphBuildsValidGraphDataProvider()
    {
        return [
            'graph1' => [
                [ // dependencies
                    1 => [
                        'before' => [],
                        'after' => [ 2 ]
                    ],
                ],
                [ // graph
                    1 => [
                        1 => false,
                        2 => true
                    ],
                    2 => [
                        1 => false,
                        2 => false
                    ],
                ]
            ],
            'graph2' => [
                [ // dependencies
                    1 => [
                        'before' => [ 3 ],
                        'after' => [ 2 ]
                    ],
                ],
                [ // graph
                    1 => [
                        1 => false,
                        2 => true,
                        3 => false,
                    ],
                    2 => [
                        1 => false,
                        2 => false,
                        3 => false,
                    ],
                    3 => [
                        1 => true,
                        2 => false,
                        3 => false,
                    ],
                ]
            ],
            'graph3' => [
                [ // dependencies
                    3 => [
                        'before' => [],
                        'after' => []
                    ],
                    1 => [
                        'before' => [ 3 ],
                        'after' => [ 2 ]
                    ],
                    2 => [
                        'before' => [ 3 ],
                        'after' => []
                    ]
                ],
                [ // graph
                    1 => [
                        1 => false,
                        2 => true,
                        3 => false,
                    ],
                    2 => [
                        1 => false,
                        2 => false,
                        3 => false,
                    ],
                    3 => [
                        1 => true,
                        2 => true,
                        3 => false,
                    ],
                ]
            ],
            'cyclic graph' => [
                [ // dependencies
                    1 => [
                        'before' => [ 2 ],
                        'after' => []
                    ],
                    2 => [
                        'before' => [ 1 ],
                        'after' => []
                    ]
                ],
                [ // graph
                    1 => [
                        1 => false,
                        2 => true,
                    ],
                    2 => [
                        1 => true,
                        2 => false,
                    ],
                ]
            ],
            'TYPO3 Flow Packages' => [
                [ // dependencies
                    'TYPO3.Flow' => [
                        'before' => [],
                        'after' => ['Symfony.Component.Yaml', 'Doctrine.Common', 'Doctrine.DBAL', 'Doctrine.ORM']
                    ],
                    'Doctrine.ORM' => [
                        'before' => [],
                        'after' => ['Doctrine.Common', 'Doctrine.DBAL']
                    ],
                    'Doctrine.Common' => [
                        'before' => [],
                        'after' => []
                    ],
                    'Doctrine.DBAL' => [
                        'before' => [],
                        'after' => ['Doctrine.Common']
                    ],
                    'Symfony.Component.Yaml' => [
                        'before' => [],
                        'after' => []
                    ],
                ],
                [ // graph
                    'TYPO3.Flow' => [
                        'TYPO3.Flow' => false,
                        'Doctrine.ORM' => true,
                        'Doctrine.Common' => true,
                        'Doctrine.DBAL' => true,
                        'Symfony.Component.Yaml' => true,
                    ],
                    'Doctrine.ORM' => [
                        'TYPO3.Flow' => false,
                        'Doctrine.ORM' => false,
                        'Doctrine.Common' => true,
                        'Doctrine.DBAL' => true,
                        'Symfony.Component.Yaml' => false,
                    ],
                    'Doctrine.Common' => [
                        'TYPO3.Flow' => false,
                        'Doctrine.ORM' => false,
                        'Doctrine.Common' => false,
                        'Doctrine.DBAL' => false,
                        'Symfony.Component.Yaml' => false,
                    ],
                    'Doctrine.DBAL' => [
                        'TYPO3.Flow' => false,
                        'Doctrine.ORM' => false,
                        'Doctrine.Common' => true,
                        'Doctrine.DBAL' => false,
                        'Symfony.Component.Yaml' => false,
                    ],
                    'Symfony.Component.Yaml' => [
                        'TYPO3.Flow' => false,
                        'Doctrine.ORM' => false,
                        'Doctrine.Common' => false,
                        'Doctrine.DBAL' => false,
                        'Symfony.Component.Yaml' => false,
                    ],
                ],
            ],
            'TYPO3 CMS Extensions' => [
                [ // dependencies
                    'core' => [
                        'before' => [],
                        'after' => [],
                    ],
                    'openid' => [
                        'before' => [],
                        'after' => ['core', 'setup']
                    ],
                    'scheduler' => [
                        'before' => [],
                        'after' => ['core'],
                    ],
                    'setup' => [
                        'before' => [],
                        'after' => ['core'],
                    ],
                    'sv' => [
                        'before' => [],
                        'after' => ['core'],
                    ],
                ],
                [ // graph
                    'core' => [
                        'core' => false,
                        'setup' => false,
                        'sv' => false,
                        'scheduler' => false,
                        'openid' => false,
                    ],
                    'openid' => [
                        'core' => true,
                        'setup' => true,
                        'sv' => false,
                        'scheduler' => false,
                        'openid' => false,
                    ],
                    'scheduler' => [
                        'core' => true,
                        'setup' => false,
                        'sv' => false,
                        'scheduler' => false,
                        'openid' => false,
                    ],
                    'setup' => [
                        'core' => true,
                        'setup' => false,
                        'sv' => false,
                        'scheduler' => false,
                        'openid' => false,
                    ],
                    'sv' => [
                        'core' => true,
                        'setup' => false,
                        'sv' => false,
                        'scheduler' => false,
                        'openid' => false,
                    ],
                ],
            ],
            'Dummy Packages' => [
                [ // dependencies
                    'A' => [
                        'before' => [],
                        'after' => ['B', 'D', 'C'],
                    ],
                    'B' => [
                        'before' => [],
                        'after' => []
                    ],
                    'C' => [
                        'before' => [],
                        'after' => ['E']
                    ],
                    'D' => [
                        'before' => [],
                        'after' => ['E'],
                    ],
                    'E' => [
                        'before' => [],
                        'after' => [],
                    ],
                    'F' => [
                        'before' => [],
                        'after' => [],
                    ],
                ],
                [ // graph
                    'A' => [
                        'A' => false,
                        'B' => true,
                        'C' => true,
                        'D' => true,
                        'E' => false,
                        'F' => false,
                    ],
                    'B' => [
                        'A' => false,
                        'B' => false,
                        'C' => false,
                        'D' => false,
                        'E' => false,
                        'F' => false,
                    ],
                    'C' => [
                        'A' => false,
                        'B' => false,
                        'C' => false,
                        'D' => false,
                        'E' => true,
                        'F' => false,
                    ],
                    'D' => [
                        'A' => false,
                        'B' => false,
                        'C' => false,
                        'D' => false,
                        'E' => true,
                        'F' => false,
                    ],
                    'E' => [
                        'A' => false,
                        'B' => false,
                        'C' => false,
                        'D' => false,
                        'E' => false,
                        'F' => false,
                    ],
                    'F' => [
                        'A' => false,
                        'B' => false,
                        'C' => false,
                        'D' => false,
                        'E' => false,
                        'F' => false,
                    ],
                ],
            ],
            'Suggestions without reverse dependency' => [
                [ // dependencies
                    'A' => [
                        'before' => [],
                        'after' => [],
                        'after-resilient' => ['B'] // package suggestion
                    ],
                    'B' => [
                        'before' => [],
                        'after' => [],
                    ],
                    'C' => [
                        'before' => [],
                        'after' => ['A']
                    ],
                ],
                [ // graph
                    'A' => [
                        'A' => false,
                        'B' => true,
                        'C' => false,
                    ],
                    'B' => [
                        'A' => false,
                        'B' => false,
                        'C' => false,
                    ],
                    'C' => [
                        'A' => true,
                        'B' => false,
                        'C' => false,
                    ],
                ],
            ],
            'Suggestions with reverse dependency' => [
                [ // dependencies
                    'A' => [
                        'before' => [],
                        'after' => [],
                        'after-resilient' => ['B'], // package suggestion
                    ],
                    'B' => [
                        'before' => [],
                        'after' => ['A']
                    ],
                    'C' => [
                        'before' => [],
                        'after' => ['A']
                    ],
                ],
                [ // graph
                    'A' => [
                        'A' => false,
                        'B' => false,
                        'C' => false,
                    ],
                    'B' => [
                        'A' => true,
                        'B' => false,
                        'C' => false,
                    ],
                    'C' => [
                        'A' => true,
                        'B' => false,
                        'C' => false,
                    ],
                ],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider calculateOrderResolvesCorrectOrderDataProvider
     * @param array $graph
     * @param array $expectedList
     */
    public function calculateOrderResolvesCorrectOrder(array $graph, array $expectedList)
    {
        $list = (new DependencyOrderingService())->calculateOrder($graph);
        $this->assertSame($expectedList, $list);
    }

    /**
     * @return array
     */
    public function calculateOrderResolvesCorrectOrderDataProvider()
    {
        return [
            'list1' => [
                [ // $graph
                    1 => [
                        1 => false,
                        2 => true
                    ],
                    2 => [
                        1 => false,
                        2 => false
                    ],
                ],
                [ // $expectedList
                    2, 1
                ]
            ],
            'list2' => [
                [ // $graph
                    1 => [
                        1 => false,
                        2 => true,
                        3 => false,
                    ],
                    2 => [
                        1 => false,
                        2 => false,
                        3 => false,
                    ],
                    3 => [
                        1 => true,
                        2 => true,
                        3 => false,
                    ],
                ],
                [ // $expectedList
                    2, 1, 3
                ]
            ],
        ];
    }

    /**
     * @test
     * @expectedException \UnexpectedValueException
     * @expectedExceptionCode 1381960494
     */
    public function calculateOrderDetectsCyclicGraph()
    {
        (new DependencyOrderingService())->calculateOrder([
            1 => [
                1 => false,
                2 => true,
            ],
            2 => [
                1 => true,
                2 => false,
            ],
        ]);
    }

    /**
     * @return array
     */
    public function findPathInGraphReturnsCorrectPathDataProvider()
    {
        return [
            'Simple path' => [
                [
                    'A' => ['A' => false, 'B' => false, 'C' => false, 'Z' => true],
                    'B' => ['A' => false, 'B' => false, 'C' => false, 'Z' => false],
                    'C' => ['A' => false, 'B' => false, 'C' => false, 'Z' => false],
                    'Z' => ['A' => false, 'B' => false, 'C' => false, 'Z' => false]
                ],
                'A', 'Z',
                ['A', 'Z']
            ],
            'No path' => [
                [
                    'A' => ['A' => false, 'B' => true, 'C' => false, 'Z' => false],
                    'B' => ['A' => false, 'B' => false, 'C' => false, 'Z' => false],
                    'C' => ['A' => false, 'B' => true, 'C' => false, 'Z' => false],
                    'Z' => ['A' => false, 'B' => true, 'C' => false, 'Z' => false]
                ],
                'A', 'C',
                []
            ],
            'Longer path' => [
                [
                    'A' => ['A' => false, 'B' => true, 'C' => true, 'Z' => true],
                    'B' => ['A' => false, 'B' => false, 'C' => false, 'Z' => false],
                    'C' => ['A' => false, 'B' => false, 'C' => false, 'Z' => true],
                    'Z' => ['A' => false, 'B' => false, 'C' => false, 'Z' => false]
                ],
                'A', 'Z',
                ['A', 'C', 'Z']
            ],
        ];
    }

    /**
     * @param array $graph
     * @param string $from
     * @param string $to
     * @param array $expected
     * @test
     * @dataProvider findPathInGraphReturnsCorrectPathDataProvider
     */
    public function findPathInGraphReturnsCorrectPath(array $graph, $from, $to, array $expected)
    {
        /** @var DependencyOrderingService|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface $dependencyOrderingService */
        $dependencyOrderingService = $this->getAccessibleMock(DependencyOrderingService::class, ['dummy']);
        $path = $dependencyOrderingService->_call('findPathInGraph', $graph, $from, $to);

        $this->assertSame($expected, $path);
    }
}
