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
            'TYPO3 Flow Packages' => array(
                array( // dependencies
                    'TYPO3.Flow' => array(
                        'before' => [],
                        'after' => array('Symfony.Component.Yaml', 'Doctrine.Common', 'Doctrine.DBAL', 'Doctrine.ORM')
                    ),
                    'Doctrine.ORM' => array(
                        'before' => [],
                        'after' => array('Doctrine.Common', 'Doctrine.DBAL')
                    ),
                    'Doctrine.Common' => array(
                        'before' => [],
                        'after' => array()
                    ),
                    'Doctrine.DBAL' => array(
                        'before' => [],
                        'after' => array('Doctrine.Common')
                    ),
                    'Symfony.Component.Yaml' => array(
                        'before' => [],
                        'after' => array()
                    ),
                ),
                array( // graph
                    'TYPO3.Flow' => array(
                        'TYPO3.Flow' => false,
                        'Doctrine.ORM' => true,
                        'Doctrine.Common' => true,
                        'Doctrine.DBAL' => true,
                        'Symfony.Component.Yaml' => true,
                    ),
                    'Doctrine.ORM' => array(
                        'TYPO3.Flow' => false,
                        'Doctrine.ORM' => false,
                        'Doctrine.Common' => true,
                        'Doctrine.DBAL' => true,
                        'Symfony.Component.Yaml' => false,
                    ),
                    'Doctrine.Common' => array(
                        'TYPO3.Flow' => false,
                        'Doctrine.ORM' => false,
                        'Doctrine.Common' => false,
                        'Doctrine.DBAL' => false,
                        'Symfony.Component.Yaml' => false,
                    ),
                    'Doctrine.DBAL' => array(
                        'TYPO3.Flow' => false,
                        'Doctrine.ORM' => false,
                        'Doctrine.Common' => true,
                        'Doctrine.DBAL' => false,
                        'Symfony.Component.Yaml' => false,
                    ),
                    'Symfony.Component.Yaml' => array(
                        'TYPO3.Flow' => false,
                        'Doctrine.ORM' => false,
                        'Doctrine.Common' => false,
                        'Doctrine.DBAL' => false,
                        'Symfony.Component.Yaml' => false,
                    ),
                ),
            ),
            'TYPO3 CMS Extensions' => array(
                array( // dependencies
                    'core' => array(
                        'before' => [],
                        'after' => array(),
                    ),
                    'openid' => array(
                        'before' => [],
                        'after' => array('core', 'setup')
                    ),
                    'scheduler' => array(
                        'before' => [],
                        'after' => array('core'),
                    ),
                    'setup' => array(
                        'before' => [],
                        'after' => array('core'),
                    ),
                    'sv' => array(
                        'before' => [],
                        'after' => array('core'),
                    ),
                ),
                array( // graph
                    'core' => array(
                        'core' => false,
                        'setup' => false,
                        'sv' => false,
                        'scheduler' => false,
                        'openid' => false,
                    ),
                    'openid' => array(
                        'core' => true,
                        'setup' => true,
                        'sv' => false,
                        'scheduler' => false,
                        'openid' => false,
                    ),
                    'scheduler' => array(
                        'core' => true,
                        'setup' => false,
                        'sv' => false,
                        'scheduler' => false,
                        'openid' => false,
                    ),
                    'setup' => array(
                        'core' => true,
                        'setup' => false,
                        'sv' => false,
                        'scheduler' => false,
                        'openid' => false,
                    ),
                    'sv' => array(
                        'core' => true,
                        'setup' => false,
                        'sv' => false,
                        'scheduler' => false,
                        'openid' => false,
                    ),
                ),
            ),
            'Dummy Packages' => array(
                array( // dependencies
                    'A' => array(
                        'before' => [],
                        'after' => array('B', 'D', 'C'),
                    ),
                    'B' => array(
                        'before' => [],
                        'after' => array()
                    ),
                    'C' => array(
                        'before' => [],
                        'after' => array('E')
                    ),
                    'D' => array(
                        'before' => [],
                        'after' => array('E'),
                    ),
                    'E' => array(
                        'before' => [],
                        'after' => array(),
                    ),
                    'F' => array(
                        'before' => [],
                        'after' => array(),
                    ),
                ),
                array( // graph
                    'A' => array(
                        'A' => false,
                        'B' => true,
                        'C' => true,
                        'D' => true,
                        'E' => false,
                        'F' => false,
                    ),
                    'B' => array(
                        'A' => false,
                        'B' => false,
                        'C' => false,
                        'D' => false,
                        'E' => false,
                        'F' => false,
                    ),
                    'C' => array(
                        'A' => false,
                        'B' => false,
                        'C' => false,
                        'D' => false,
                        'E' => true,
                        'F' => false,
                    ),
                    'D' => array(
                        'A' => false,
                        'B' => false,
                        'C' => false,
                        'D' => false,
                        'E' => true,
                        'F' => false,
                    ),
                    'E' => array(
                        'A' => false,
                        'B' => false,
                        'C' => false,
                        'D' => false,
                        'E' => false,
                        'F' => false,
                    ),
                    'F' => array(
                        'A' => false,
                        'B' => false,
                        'C' => false,
                        'D' => false,
                        'E' => false,
                        'F' => false,
                    ),
                ),
            ),
            'Suggestions without reverse dependency' => array(
                array( // dependencies
                    'A' => array(
                        'before' => [],
                        'after' => [],
                        'after-resilient' => array('B') // package suggestion
                    ),
                    'B' => array(
                        'before' => [],
                        'after' => [],
                    ),
                    'C' => array(
                        'before' => [],
                        'after' => array('A')
                    ),
                ),
                array( // graph
                    'A' => array(
                        'A' => false,
                        'B' => true,
                        'C' => false,
                    ),
                    'B' => array(
                        'A' => false,
                        'B' => false,
                        'C' => false,
                    ),
                    'C' => array(
                        'A' => true,
                        'B' => false,
                        'C' => false,
                    ),
                ),
            ),
            'Suggestions with reverse dependency' => array(
                array( // dependencies
                    'A' => array(
                        'before' => [],
                        'after' => [],
                        'after-resilient' => array('B'), // package suggestion
                    ),
                    'B' => array(
                        'before' => [],
                        'after' => array('A')
                    ),
                    'C' => array(
                        'before' => [],
                        'after' => array('A')
                    ),
                ),
                array( // graph
                    'A' => array(
                        'A' => false,
                        'B' => false,
                        'C' => false,
                    ),
                    'B' => array(
                        'A' => true,
                        'B' => false,
                        'C' => false,
                    ),
                    'C' => array(
                        'A' => true,
                        'B' => false,
                        'C' => false,
                    ),
                ),
            ),
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
     */
    public function calculateOrderDetectsCyclicGraph()
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionCode(1381960494);

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
        return array(
            'Simple path' => array(
                array(
                    'A' => array('A' => false, 'B' => false, 'C' => false, 'Z' => true),
                    'B' => array('A' => false, 'B' => false, 'C' => false, 'Z' => false),
                    'C' => array('A' => false, 'B' => false, 'C' => false, 'Z' => false),
                    'Z' => array('A' => false, 'B' => false, 'C' => false, 'Z' => false)
                ),
                'A', 'Z',
                array('A', 'Z')
            ),
            'No path' => array(
                array(
                    'A' => array('A' => false, 'B' => true, 'C' => false, 'Z' => false),
                    'B' => array('A' => false, 'B' => false, 'C' => false, 'Z' => false),
                    'C' => array('A' => false, 'B' => true, 'C' => false, 'Z' => false),
                    'Z' => array('A' => false, 'B' => true, 'C' => false, 'Z' => false)
                ),
                'A', 'C',
                array()
            ),
            'Longer path' => array(
                array(
                    'A' => array('A' => false, 'B' => true, 'C' => true, 'Z' => true),
                    'B' => array('A' => false, 'B' => false, 'C' => false, 'Z' => false),
                    'C' => array('A' => false, 'B' => false, 'C' => false, 'Z' => true),
                    'Z' => array('A' => false, 'B' => false, 'C' => false, 'Z' => false)
                ),
                'A', 'Z',
                array('A', 'C', 'Z')
            ),
        );
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
