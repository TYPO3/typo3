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

use TYPO3\CMS\Core\Tests\UnitTestCase;
use TYPO3\CMS\Core\Service\DependencyOrderingService;

/**
 * Test case
 *
 * @author Markus Klein <markus.klein@typo3.org>
 */
class DependencyOrderingServiceTest extends UnitTestCase {

	/**
	 * @test
	 * @dataProvider orderByDependenciesBuildsCorrectOrderDataProvider
	 * @param array $items
	 * @param string $beforeKey
	 * @param string $afterKey
	 * @param array $expectedOrderedItems
	 */
	public function orderByDependenciesBuildsCorrectOrder(array $items, $beforeKey, $afterKey, array $expectedOrderedItems) {
		$orderedItems = (new DependencyOrderingService())->orderByDependencies($items, $beforeKey, $afterKey);
		$this->assertSame($expectedOrderedItems, $orderedItems);
	}

	/**
	 * @return array
	 */
	public function orderByDependenciesBuildsCorrectOrderDataProvider() {
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
						'otherProperty' => TRUE
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
						'otherProperty' => TRUE
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
						'otherProperty' => TRUE
					]
				],
				'before',
				'depends',
				[ // $expectedOrderedItems
					3 => [
						'otherProperty' => TRUE
					],
					2 => [
						'before' => [ 1 ],
						'depends' => [ 3 ]
					],
				]
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
	public function prepareDependenciesBuildsFullIdentifierList(array $dependencies, array $expectedDependencies) {
		/** @var DependencyOrderingService|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface $dependencyOrderingService */
		$dependencyOrderingService = $this->getAccessibleMock(DependencyOrderingService::class, ['dummy']);
		$preparedDependencies = $dependencyOrderingService->_call('prepareDependencies', $dependencies);
		$this->assertEquals($expectedDependencies, $preparedDependencies);
	}

	/**
	 * @return array
	 */
	public function prepareDependenciesBuildsFullIdentifierListDataProvider() {
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
	public function buildDependencyGraphBuildsValidGraph(array $dependencies, array $expectedGraph) {
		$graph = (new DependencyOrderingService())->buildDependencyGraph($dependencies);
		$this->assertEquals($expectedGraph, $graph);
	}

	/**
	 * @return array
	 */
	public function buildDependencyGraphBuildsValidGraphDataProvider() {
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
						1 => FALSE,
						2 => TRUE
					],
					2 => [
						1 => FALSE,
						2 => FALSE
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
						1 => FALSE,
						2 => TRUE,
						3 => FALSE,
					],
					2 => [
						1 => FALSE,
						2 => FALSE,
						3 => FALSE,
					],
					3 => [
						1 => TRUE,
						2 => FALSE,
						3 => FALSE,
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
						1 => FALSE,
						2 => TRUE,
						3 => FALSE,
					],
					2 => [
						1 => FALSE,
						2 => FALSE,
						3 => FALSE,
					],
					3 => [
						1 => TRUE,
						2 => TRUE,
						3 => FALSE,
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
						1 => FALSE,
						2 => TRUE,
					],
					2 => [
						1 => TRUE,
						2 => FALSE,
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
						'TYPO3.Flow' => FALSE,
						'Doctrine.ORM' => TRUE,
						'Doctrine.Common' => TRUE,
						'Doctrine.DBAL' => TRUE,
						'Symfony.Component.Yaml' => TRUE,
					),
					'Doctrine.ORM' => array(
						'TYPO3.Flow' => FALSE,
						'Doctrine.ORM' => FALSE,
						'Doctrine.Common' => TRUE,
						'Doctrine.DBAL' => TRUE,
						'Symfony.Component.Yaml' => FALSE,
					),
					'Doctrine.Common' => array(
						'TYPO3.Flow' => FALSE,
						'Doctrine.ORM' => FALSE,
						'Doctrine.Common' => FALSE,
						'Doctrine.DBAL' => FALSE,
						'Symfony.Component.Yaml' => FALSE,
					),
					'Doctrine.DBAL' => array(
						'TYPO3.Flow' => FALSE,
						'Doctrine.ORM' => FALSE,
						'Doctrine.Common' => TRUE,
						'Doctrine.DBAL' => FALSE,
						'Symfony.Component.Yaml' => FALSE,
					),
					'Symfony.Component.Yaml' => array(
						'TYPO3.Flow' => FALSE,
						'Doctrine.ORM' => FALSE,
						'Doctrine.Common' => FALSE,
						'Doctrine.DBAL' => FALSE,
						'Symfony.Component.Yaml' => FALSE,
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
					'scheduler' => array (
						'before' => [],
						'after' => array('core'),
					),
					'setup' => array (
						'before' => [],
						'after' => array('core'),
					),
					'sv' => array (
						'before' => [],
						'after' => array('core'),
					),
				),
				array( // graph
					'core' => array(
						'core' => FALSE,
						'setup' => FALSE,
						'sv' => FALSE,
						'scheduler' => FALSE,
						'openid' => FALSE,
					),
					'openid' => array(
						'core' => TRUE,
						'setup' => TRUE,
						'sv' => FALSE,
						'scheduler' => FALSE,
						'openid' => FALSE,
					),
					'scheduler' => array (
						'core' => TRUE,
						'setup' => FALSE,
						'sv' => FALSE,
						'scheduler' => FALSE,
						'openid' => FALSE,
					),
					'setup' => array (
						'core' => TRUE,
						'setup' => FALSE,
						'sv' => FALSE,
						'scheduler' => FALSE,
						'openid' => FALSE,
					),
					'sv' => array (
						'core' => TRUE,
						'setup' => FALSE,
						'sv' => FALSE,
						'scheduler' => FALSE,
						'openid' => FALSE,
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
					'D' => array (
						'before' => [],
						'after' => array('E'),
					),
					'E' => array (
						'before' => [],
						'after' => array(),
					),
					'F' => array (
						'before' => [],
						'after' => array(),
					),
				),
				array( // graph
					'A' => array(
						'A' => FALSE,
						'B' => TRUE,
						'C' => TRUE,
						'D' => TRUE,
						'E' => FALSE,
						'F' => FALSE,
					),
					'B' => array(
						'A' => FALSE,
						'B' => FALSE,
						'C' => FALSE,
						'D' => FALSE,
						'E' => FALSE,
						'F' => FALSE,
					),
					'C' => array(
						'A' => FALSE,
						'B' => FALSE,
						'C' => FALSE,
						'D' => FALSE,
						'E' => TRUE,
						'F' => FALSE,
					),
					'D' => array (
						'A' => FALSE,
						'B' => FALSE,
						'C' => FALSE,
						'D' => FALSE,
						'E' => TRUE,
						'F' => FALSE,
					),
					'E' => array (
						'A' => FALSE,
						'B' => FALSE,
						'C' => FALSE,
						'D' => FALSE,
						'E' => FALSE,
						'F' => FALSE,
					),
					'F' => array (
						'A' => FALSE,
						'B' => FALSE,
						'C' => FALSE,
						'D' => FALSE,
						'E' => FALSE,
						'F' => FALSE,
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
						'A' => FALSE,
						'B' => TRUE,
						'C' => FALSE,
					),
					'B' => array(
						'A' => FALSE,
						'B' => FALSE,
						'C' => FALSE,
					),
					'C' => array(
						'A' => TRUE,
						'B' => FALSE,
						'C' => FALSE,
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
						'A' => FALSE,
						'B' => FALSE,
						'C' => FALSE,
					),
					'B' => array(
						'A' => TRUE,
						'B' => FALSE,
						'C' => FALSE,
					),
					'C' => array(
						'A' => TRUE,
						'B' => FALSE,
						'C' => FALSE,
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
	public function calculateOrderResolvesCorrectOrder(array $graph, array $expectedList) {
		$list = (new DependencyOrderingService())->calculateOrder($graph);
		$this->assertSame($expectedList, $list);
	}

	/**
	 * @return array
	 */
	public function calculateOrderResolvesCorrectOrderDataProvider() {
		return [
			'list1' => [
				[ // $graph
					1 => [
						1 => FALSE,
						2 => TRUE
					],
					2 => [
						1 => FALSE,
						2 => FALSE
					],
				],
				[ // $expectedList
					2, 1
				]
			],
			'list2' => [
				[ // $graph
					1 => [
						1 => FALSE,
						2 => TRUE,
						3 => FALSE,
					],
					2 => [
						1 => FALSE,
						2 => FALSE,
						3 => FALSE,
					],
					3 => [
						1 => TRUE,
						2 => TRUE,
						3 => FALSE,
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
	public function calculateOrderDetectsCyclicGraph() {
		(new DependencyOrderingService())->calculateOrder([
			1 => [
				1 => FALSE,
				2 => TRUE,
			],
			2 => [
				1 => TRUE,
				2 => FALSE,
			],
		]);
	}

	/**
	 * @return array
	 */
	public function findPathInGraphReturnsCorrectPathDataProvider() {
		return array(
			'Simple path' => array(
				array(
					'A' => array('A' => FALSE, 'B' => FALSE, 'C' => FALSE, 'Z' => TRUE),
					'B' => array('A' => FALSE, 'B' => FALSE, 'C' => FALSE, 'Z' => FALSE),
					'C' => array('A' => FALSE, 'B' => FALSE, 'C' => FALSE, 'Z' => FALSE),
					'Z' => array('A' => FALSE, 'B' => FALSE, 'C' => FALSE, 'Z' => FALSE)
				),
				'A', 'Z',
				array('A', 'Z')
			),
			'No path' => array(
				array(
					'A' => array('A' => FALSE, 'B' => TRUE, 'C' => FALSE, 'Z' => FALSE),
					'B' => array('A' => FALSE, 'B' => FALSE, 'C' => FALSE, 'Z' => FALSE),
					'C' => array('A' => FALSE, 'B' => TRUE, 'C' => FALSE, 'Z' => FALSE),
					'Z' => array('A' => FALSE, 'B' => TRUE, 'C' => FALSE, 'Z' => FALSE)
				),
				'A', 'C',
				array()
			),
			'Longer path' => array(
				array(
					'A' => array('A' => FALSE, 'B' => TRUE, 'C' => TRUE, 'Z' => TRUE),
					'B' => array('A' => FALSE, 'B' => FALSE, 'C' => FALSE, 'Z' => FALSE),
					'C' => array('A' => FALSE, 'B' => FALSE, 'C' => FALSE, 'Z' => TRUE),
					'Z' => array('A' => FALSE, 'B' => FALSE, 'C' => FALSE, 'Z' => FALSE)
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
	public function findPathInGraphReturnsCorrectPath(array $graph, $from, $to, array $expected) {
		/** @var DependencyOrderingService|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface $dependencyOrderingService */
		$dependencyOrderingService = $this->getAccessibleMock(DependencyOrderingService::class, ['dummy']);
		$path = $dependencyOrderingService->_call('findPathInGraph', $graph, $from, $to);

		$this->assertSame($expected, $path);
	}

}
