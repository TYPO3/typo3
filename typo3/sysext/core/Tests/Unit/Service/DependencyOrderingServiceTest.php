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

namespace TYPO3\CMS\Core\Tests\Unit\Service;

use TYPO3\CMS\Core\Service\DependencyOrderingService;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class DependencyOrderingServiceTest extends UnitTestCase
{
    /**
     * @test
     * @dataProvider orderByDependenciesBuildsCorrectOrderDataProvider
     */
    public function orderByDependenciesBuildsCorrectOrder(array $items, string $beforeKey, string $afterKey, array $expectedOrderedItems): void
    {
        $orderedItems = (new DependencyOrderingService())->orderByDependencies($items, $beforeKey, $afterKey);
        self::assertSame($expectedOrderedItems, $orderedItems);
    }

    public function orderByDependenciesBuildsCorrectOrderDataProvider(): array
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
                ],
            ],
            'ordered' => [
                [ // $items
                    1 => [],
                    2 => [
                        'precedes' => [ 1 ],
                    ],
                ],
                'precedes',
                'after',
                [ // $expectedOrderedItems
                    2 => [
                        'precedes' => [ 1 ],
                    ],
                    1 => [],
                ],
            ],
            'mixed' => [
                [ // $items
                    1 => [],
                    2 => [
                        'before' => [ 1 ],
                    ],
                    3 => [
                        'otherProperty' => true,
                    ],
                ],
                'before',
                'after',
                [ // $expectedOrderedItems
                    2 => [
                        'before' => [ 1 ],
                    ],
                    1 => [],
                    3 => [
                        'otherProperty' => true,
                    ],
                ],
            ],
            'reference to non-existing' => [
                [ // $items
                    2 => [
                        'before' => [ 1 ],
                        'depends' => [ 3 ],
                    ],
                    3 => [
                        'otherProperty' => true,
                    ],
                ],
                'before',
                'depends',
                [ // $expectedOrderedItems
                    3 => [
                        'otherProperty' => true,
                    ],
                    2 => [
                        'before' => [ 1 ],
                        'depends' => [ 3 ],
                    ],
                ],
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
     * @throws \InvalidArgumentException
     */
    public function prepareDependenciesBuildsFullIdentifierList(array $dependencies, array $expectedDependencies): void
    {
        $dependencyOrderingService = $this->getAccessibleMock(DependencyOrderingService::class, ['dummy']);
        $preparedDependencies = $dependencyOrderingService->_call('prepareDependencies', $dependencies);
        self::assertEquals($expectedDependencies, $preparedDependencies);
    }

    public function prepareDependenciesBuildsFullIdentifierListDataProvider(): array
    {
        return [
            'simple' => [
                [ // $dependencies
                    1 => [
                        'before' => [],
                        'after' => [ 2 ],
                    ],
                ],
                [ // $expectedDependencies
                    1 => [
                        'before' => [],
                        'after' => [ 2 ],
                    ],
                    2 => [
                        'before' => [],
                        'after' => [],
                    ],
                ],
            ],
            'missing before' => [
                [ // $dependencies
                    1 => [
                        'after' => [ 2 ],
                    ],
                ],
                [ // $expectedDependencies
                    1 => [
                        'before' => [],
                        'after' => [ 2 ],
                    ],
                    2 => [
                        'before' => [],
                        'after' => [],
                    ],
                ],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider buildDependencyGraphBuildsValidGraphDataProvider
     */
    public function buildDependencyGraphBuildsValidGraph(array $dependencies, array $expectedGraph): void
    {
        $graph = (new DependencyOrderingService())->buildDependencyGraph($dependencies);
        self::assertEquals($expectedGraph, $graph);
    }

    public function buildDependencyGraphBuildsValidGraphDataProvider(): array
    {
        return [
            'graph1' => [
                [ // dependencies
                    1 => [
                        'before' => [],
                        'after' => [ 2 ],
                    ],
                ],
                [ // graph
                    1 => [
                        1 => false,
                        2 => true,
                    ],
                    2 => [
                        1 => false,
                        2 => false,
                    ],
                ],
            ],
            'graph2' => [
                [ // dependencies
                    1 => [
                        'before' => [ 3 ],
                        'after' => [ 2 ],
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
                ],
            ],
            'graph3' => [
                [ // dependencies
                    3 => [
                        'before' => [],
                        'after' => [],
                    ],
                    1 => [
                        'before' => [ 3 ],
                        'after' => [ 2 ],
                    ],
                    2 => [
                        'before' => [ 3 ],
                        'after' => [],
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
                        2 => true,
                        3 => false,
                    ],
                ],
            ],
            'cyclic graph' => [
                [ // dependencies
                    1 => [
                        'before' => [ 2 ],
                        'after' => [],
                    ],
                    2 => [
                        'before' => [ 1 ],
                        'after' => [],
                    ],
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
                ],
            ],
            'TYPO3 Flow Packages' => [
                [ // dependencies
                    'TYPO3.Flow' => [
                        'before' => [],
                        'after' => ['Symfony.Component.Yaml', 'Doctrine.Common', 'Doctrine.DBAL', 'Doctrine.ORM'],
                    ],
                    'Doctrine.ORM' => [
                        'before' => [],
                        'after' => ['Doctrine.Common', 'Doctrine.DBAL'],
                    ],
                    'Doctrine.Common' => [
                        'before' => [],
                        'after' => [],
                    ],
                    'Doctrine.DBAL' => [
                        'before' => [],
                        'after' => ['Doctrine.Common'],
                    ],
                    'Symfony.Component.Yaml' => [
                        'before' => [],
                        'after' => [],
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
                        'after' => ['core', 'setup'],
                    ],
                    'scheduler' => [
                        'before' => [],
                        'after' => ['core'],
                    ],
                    'setup' => [
                        'before' => [],
                        'after' => ['core'],
                    ],
                ],
                [ // graph
                    'core' => [
                        'core' => false,
                        'setup' => false,
                        'scheduler' => false,
                        'openid' => false,
                    ],
                    'openid' => [
                        'core' => true,
                        'setup' => true,
                        'scheduler' => false,
                        'openid' => false,
                    ],
                    'scheduler' => [
                        'core' => true,
                        'setup' => false,
                        'scheduler' => false,
                        'openid' => false,
                    ],
                    'setup' => [
                        'core' => true,
                        'setup' => false,
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
                        'after' => [],
                    ],
                    'C' => [
                        'before' => [],
                        'after' => ['E'],
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
                        'after-resilient' => ['B'], // package suggestion
                    ],
                    'B' => [
                        'before' => [],
                        'after' => [],
                    ],
                    'C' => [
                        'before' => [],
                        'after' => ['A'],
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
                        'after' => ['A'],
                    ],
                    'C' => [
                        'before' => [],
                        'after' => ['A'],
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
     */
    public function calculateOrderResolvesCorrectOrder(array $graph, array $expectedList): void
    {
        $list = (new DependencyOrderingService())->calculateOrder($graph);
        self::assertSame($expectedList, $list);
    }

    public function calculateOrderResolvesCorrectOrderDataProvider(): array
    {
        return [
            'list1' => [
                [ // $graph
                    1 => [
                        1 => false,
                        2 => true,
                    ],
                    2 => [
                        1 => false,
                        2 => false,
                    ],
                ],
                [ // $expectedList
                    2, 1,
                ],
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
                    2, 1, 3,
                ],
            ],
        ];
    }

    /**
     * @test
     */
    public function calculateOrderDetectsCyclicGraph(): void
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

    public function findPathInGraphReturnsCorrectPathDataProvider(): array
    {
        return [
            'Simple path' => [
                [
                    'A' => ['A' => false, 'B' => false, 'C' => false, 'Z' => true],
                    'B' => ['A' => false, 'B' => false, 'C' => false, 'Z' => false],
                    'C' => ['A' => false, 'B' => false, 'C' => false, 'Z' => false],
                    'Z' => ['A' => false, 'B' => false, 'C' => false, 'Z' => false],
                ],
                'A', 'Z',
                ['A', 'Z'],
            ],
            'No path' => [
                [
                    'A' => ['A' => false, 'B' => true, 'C' => false, 'Z' => false],
                    'B' => ['A' => false, 'B' => false, 'C' => false, 'Z' => false],
                    'C' => ['A' => false, 'B' => true, 'C' => false, 'Z' => false],
                    'Z' => ['A' => false, 'B' => true, 'C' => false, 'Z' => false],
                ],
                'A', 'C',
                [],
            ],
            'Longer path' => [
                [
                    'A' => ['A' => false, 'B' => true, 'C' => true, 'Z' => true],
                    'B' => ['A' => false, 'B' => false, 'C' => false, 'Z' => false],
                    'C' => ['A' => false, 'B' => false, 'C' => false, 'Z' => true],
                    'Z' => ['A' => false, 'B' => false, 'C' => false, 'Z' => false],
                ],
                'A', 'Z',
                ['A', 'C', 'Z'],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider findPathInGraphReturnsCorrectPathDataProvider
     */
    public function findPathInGraphReturnsCorrectPath(array $graph, string $from, string $to, array $expected): void
    {
        $dependencyOrderingService = $this->getAccessibleMock(DependencyOrderingService::class, ['dummy']);
        $path = $dependencyOrderingService->_call('findPathInGraph', $graph, $from, $to);

        self::assertSame($expected, $path);
    }
}
