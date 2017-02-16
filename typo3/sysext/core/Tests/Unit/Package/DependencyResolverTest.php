<?php
namespace TYPO3\CMS\Core\Tests\Unit\Package;

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

use TYPO3\CMS\Core\Package\DependencyResolver;
use TYPO3\CMS\Core\Service\DependencyOrderingService;

/**
 * Test case
 */
class DependencyResolverTest extends \TYPO3\TestingFramework\Core\Unit\UnitTestCase
{
    /**
     * @test
     * @param array $unsortedPackageStatesConfiguration
     * @param array $frameworkPackageKeys
     * @param array $expectedGraph
     * @dataProvider buildDependencyGraphBuildsCorrectGraphDataProvider
     */
    public function buildDependencyGraphBuildsCorrectGraph(array $unsortedPackageStatesConfiguration, array $frameworkPackageKeys, array $expectedGraph)
    {
        /** @var DependencyResolver|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\TestingFramework\Core\AccessibleObjectInterface $dependencyResolver */
        $dependencyResolver = $this->getAccessibleMock(DependencyResolver::class, ['findFrameworkPackages']);
        $dependencyResolver->injectDependencyOrderingService(new DependencyOrderingService());
        $dependencyResolver->expects($this->any())->method('findFrameworkPackages')->willReturn($frameworkPackageKeys);
        $dependencyGraph = $dependencyResolver->_call('buildDependencyGraph', $unsortedPackageStatesConfiguration);

        $this->assertEquals($expectedGraph, $dependencyGraph);
    }

    /**
     * @test
     * @dataProvider packageSortingDataProvider
     * @param array $unsortedPackageStatesConfiguration
     * @param array $frameworkPackageKeys
     * @param array $expectedSortedPackageStatesConfiguration
     */
    public function sortPackageStatesConfigurationByDependencyMakesSureThatDependantPackagesAreStandingBeforeAPackageInTheInternalPackagesAndPackagesConfigurationArrays($unsortedPackageStatesConfiguration, $frameworkPackageKeys, $expectedSortedPackageKeys)
    {
        /** @var DependencyResolver|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\TestingFramework\Core\AccessibleObjectInterface $dependencyResolver */
        $dependencyResolver = $this->getAccessibleMock(DependencyResolver::class, ['findFrameworkPackages']);
        $dependencyResolver->injectDependencyOrderingService(new DependencyOrderingService());
        $dependencyResolver->expects($this->any())->method('findFrameworkPackages')->willReturn($frameworkPackageKeys);
        $sortedPackageKeys = $dependencyResolver->_call('sortPackageStatesConfigurationByDependency', $unsortedPackageStatesConfiguration);

        $this->assertEquals($expectedSortedPackageKeys, $sortedPackageKeys, 'The package states configurations have not been ordered according to their dependencies!');
    }

    /**
     * @test
     */
    public function sortPackageStatesConfigurationByDependencyThrowsExceptionWhenCycleDetected()
    {
        $unsortedPackageStatesConfiguration = [
            'A' => [
                'dependencies' => ['B'],
            ],
            'B' => [
                'dependencies' => ['A']
            ],
        ];

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionCode(1381960494);

        /** @var DependencyResolver|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\TestingFramework\Core\AccessibleObjectInterface $dependencyResolver */
        $dependencyResolver = $this->getAccessibleMock(DependencyResolver::class, ['findFrameworkPackages']);
        $dependencyResolver->injectDependencyOrderingService(new DependencyOrderingService());
        $dependencyResolver->expects($this->any())->method('findFrameworkPackages')->willReturn([]);
        $dependencyResolver->_call('sortPackageStatesConfigurationByDependency', $unsortedPackageStatesConfiguration);
    }

    /**
     * @return array
     */
    public function buildDependencyGraphBuildsCorrectGraphDataProvider()
    {
        return [
            'TYPO3 Flow Packages' => [
                [
                    'TYPO3.Flow' => [
                        'dependencies' => ['Symfony.Component.Yaml', 'Doctrine.Common', 'Doctrine.DBAL', 'Doctrine.ORM']
                    ],
                    'Doctrine.ORM' => [
                        'dependencies' => ['Doctrine.Common', 'Doctrine.DBAL']
                    ],
                    'Doctrine.Common' => [
                        'dependencies' => []
                    ],
                    'Doctrine.DBAL' => [
                        'dependencies' => ['Doctrine.Common']
                    ],
                    'Symfony.Component.Yaml' => [
                        'dependencies' => []
                    ],
                ],
                [
                    'Doctrine.Common'
                ],
                [
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
                        'Doctrine.Common' => true,
                        'Doctrine.DBAL' => false,
                        'Symfony.Component.Yaml' => false,
                    ],
                ],
            ],
            'TYPO3 CMS Extensions' => [
                [
                    'core' => [
                        'dependencies' => [],
                    ],
                    'setup' => [
                        'dependencies' => ['core'],
                    ],
                    'openid' => [
                        'dependencies' => ['core', 'setup']
                    ],
                    'news' => [
                        'dependencies' => ['extbase'],
                    ],
                    'extbase' => [
                        'dependencies' => ['core'],
                    ],
                    'pt_extbase' => [
                        'dependencies' => ['extbase'],
                    ],
                    'foo' => [
                        'dependencies' => [],
                    ],
                ],
                [
                    'core', 'setup', 'openid', 'extbase'
                ],
                [
                    'core' => [
                        'core' => false,
                        'setup' => false,
                        'openid' => false,
                        'news' => false,
                        'extbase' => false,
                        'pt_extbase' => false,
                        'foo' => false
                    ],
                    'setup' => [
                        'core' => true,
                        'setup' => false,
                        'openid' => false,
                        'news' => false,
                        'extbase' => false,
                        'pt_extbase' => false,
                        'foo' => false
                    ],
                    'openid' => [
                        'core' => true,
                        'setup' => true,
                        'openid' => false,
                        'news' => false,
                        'extbase' => false,
                        'pt_extbase' => false,
                        'foo' => false
                    ],
                    'news' => [
                        'core' => false,
                        'setup' => false,
                        'openid' => true,
                        'news' => false,
                        'extbase' => true,
                        'pt_extbase' => false,
                        'foo' => false
                    ],
                    'extbase' => [
                        'core' => true,
                        'setup' => false,
                        'openid' => false,
                        'news' => false,
                        'extbase' => false,
                        'pt_extbase' => false,
                        'foo' => false
                    ],
                    'pt_extbase' => [
                        'core' => false,
                        'setup' => false,
                        'openid' => true,
                        'news' => false,
                        'extbase' => true,
                        'pt_extbase' => false,
                        'foo' => false
                    ],
                    'foo' => [
                        'core' => false,
                        'setup' => false,
                        'openid' => true,
                        'news' => false,
                        'extbase' => true,
                        'pt_extbase' => false,
                        'foo' => false
                    ],
                ],
            ],
            'Dummy Packages' => [
                [
                    'A' => [
                        'dependencies' => ['B', 'D', 'C'],
                    ],
                    'B' => [
                        'dependencies' => []
                    ],
                    'C' => [
                        'dependencies' => ['E']
                    ],
                    'D' => [
                        'dependencies' => ['E'],
                    ],
                    'E' => [
                        'dependencies' => [],
                    ],
                    'F' => [
                        'dependencies' => [],
                    ],
                ],
                [
                    'B', 'C', 'E'
                ],
                [
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
                        'B' => true,
                        'C' => true,
                        'D' => false,
                        'E' => false,
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
                        'B' => true,
                        'C' => true,
                        'D' => false,
                        'E' => false,
                        'F' => false,
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array
     */
    public function packageSortingDataProvider()
    {
        return [
            'TYPO3 Flow Packages' => [
                [
                    'TYPO3.Flow' => [
                        'dependencies' => ['Symfony.Component.Yaml', 'Doctrine.Common', 'Doctrine.DBAL', 'Doctrine.ORM']
                    ],
                    'Doctrine.ORM' => [
                        'dependencies' => ['Doctrine.Common', 'Doctrine.DBAL']
                    ],
                    'Doctrine.Common' => [
                        'dependencies' => []
                    ],
                    'Doctrine.DBAL' => [
                        'dependencies' => ['Doctrine.Common']
                    ],
                    'Symfony.Component.Yaml' => [
                        'dependencies' => []
                    ],
                ],
                [
                    'Doctrine.Common'
                ],
                [
                    'Doctrine.Common',
                    'Doctrine.DBAL',
                    'Doctrine.ORM',
                    'Symfony.Component.Yaml',
                    'TYPO3.Flow',
                ],
            ],
            'TYPO3 CMS Extensions' => [
                [
                    'core' => [
                        'dependencies' => [],
                    ],
                    'setup' => [
                        'dependencies' => ['core'],
                    ],
                    'openid' => [
                        'dependencies' => ['core', 'setup']
                    ],
                    'news' => [
                        'dependencies' => ['extbase'],
                    ],
                    'extbase' => [
                        'dependencies' => ['core'],
                    ],
                    'pt_extbase' => [
                        'dependencies' => ['extbase'],
                    ],
                    'foo' => [
                        'dependencies' => [],
                    ],
                ],
                [
                    'core', 'setup', 'openid', 'extbase'
                ],
                [
                    'core',
                    'setup',
                    'openid',
                    'extbase',
                    'foo',
                    'news',
                    'pt_extbase',
                ],
            ],
            'Dummy Packages' => [
                [
                    'A' => [
                        'dependencies' => ['B', 'D', 'C'],
                    ],
                    'B' => [
                        'dependencies' => []
                    ],
                    'C' => [
                        'dependencies' => ['E']
                    ],
                    'D' => [
                        'dependencies' => ['E'],
                    ],
                    'E' => [
                        'dependencies' => [],
                    ],
                    'F' => [
                        'dependencies' => [],
                    ],
                ],
                [
                    'B', 'C', 'E'
                ],
                [
                    'E',
                    'C',
                    'B',
                    'D',
                    'A',
                    'F',
                ],
            ],
        ];
    }
}
