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
use TYPO3\CMS\Core\Tests\UnitTestCase;

/**
 * Test case
 */
class DependencyResolverTest extends UnitTestCase
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
        /** @var DependencyResolver|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface $dependencyResolver */
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
    public function sortPackageStatesConfigurationByDependencyMakesSureThatDependantPackagesAreStandingBeforeAPackageInTheInternalPackagesAndPackagesConfigurationArrays($unsortedPackageStatesConfiguration, $frameworkPackageKeys, $expectedSortedPackageStatesConfiguration)
    {
        /** @var DependencyResolver|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface $dependencyResolver */
        $dependencyResolver = $this->getAccessibleMock(DependencyResolver::class, ['findFrameworkPackages']);
        $dependencyResolver->injectDependencyOrderingService(new DependencyOrderingService());
        $dependencyResolver->expects($this->any())->method('findFrameworkPackages')->willReturn($frameworkPackageKeys);
        $sortedPackageStatesConfiguration = $dependencyResolver->_call('sortPackageStatesConfigurationByDependency', $unsortedPackageStatesConfiguration);

        $this->assertEquals($expectedSortedPackageStatesConfiguration, $sortedPackageStatesConfiguration, 'The package states configurations have not been ordered according to their dependencies!');
    }

    /**
     * @test
     * @expectedException \UnexpectedValueException
     */
    public function sortPackageStatesConfigurationByDependencyThrowsExceptionWhenCycleDetected()
    {
        $unsortedPackageStatesConfiguration = [
            'A' => [
                'state' => 'active',
                'dependencies' => ['B'],
            ],
            'B' => [
                'state' => 'active',
                'dependencies' => ['A']
            ],
        ];

        /** @var DependencyResolver|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface $dependencyResolver */
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
                        'state' => 'active',
                        'dependencies' => ['Symfony.Component.Yaml', 'Doctrine.Common', 'Doctrine.DBAL', 'Doctrine.ORM']
                    ],
                    'Doctrine.ORM' => [
                        'state' => 'active',
                        'dependencies' => ['Doctrine.Common', 'Doctrine.DBAL']
                    ],
                    'Doctrine.Common' => [
                        'state' => 'active',
                        'dependencies' => []
                    ],
                    'Doctrine.DBAL' => [
                        'state' => 'active',
                        'dependencies' => ['Doctrine.Common']
                    ],
                    'Symfony.Component.Yaml' => [
                        'state' => 'active',
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
                        'state' => 'active',
                        'dependencies' => [],
                    ],
                    'setup' => [
                        'state' => 'active',
                        'dependencies' => ['core'],
                    ],
                    'openid' => [
                        'state' => 'active',
                        'dependencies' => ['core', 'setup']
                    ],
                    'news' => [
                        'state' => 'active',
                        'dependencies' => ['extbase'],
                    ],
                    'extbase' => [
                        'state' => 'active',
                        'dependencies' => ['core'],
                    ],
                    'pt_extbase' => [
                        'state' => 'active',
                        'dependencies' => ['extbase'],
                    ],
                    'foo' => [
                        'state' => 'active',
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
                        'state' => 'active',
                        'dependencies' => ['B', 'D', 'C'],
                    ],
                    'B' => [
                        'state' => 'active',
                        'dependencies' => []
                    ],
                    'C' => [
                        'state' => 'active',
                        'dependencies' => ['E']
                    ],
                    'D' => [
                        'state' => 'active',
                        'dependencies' => ['E'],
                    ],
                    'E' => [
                        'state' => 'active',
                        'dependencies' => [],
                    ],
                    'F' => [
                        'state' => 'active',
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
                        'state' => 'active',
                        'dependencies' => ['Symfony.Component.Yaml', 'Doctrine.Common', 'Doctrine.DBAL', 'Doctrine.ORM']
                    ],
                    'Doctrine.ORM' => [
                        'state' => 'active',
                        'dependencies' => ['Doctrine.Common', 'Doctrine.DBAL']
                    ],
                    'Doctrine.Common' => [
                        'state' => 'active',
                        'dependencies' => []
                    ],
                    'Doctrine.DBAL' => [
                        'state' => 'active',
                        'dependencies' => ['Doctrine.Common']
                    ],
                    'Symfony.Component.Yaml' => [
                        'state' => 'active',
                        'dependencies' => []
                    ],
                ],
                [
                    'Doctrine.Common'
                ],
                [
                    'Doctrine.Common' => [
                        'state' => 'active',
                        'dependencies' => []
                    ],
                    'Doctrine.DBAL' => [
                        'state' => 'active',
                        'dependencies' => ['Doctrine.Common']
                    ],
                    'Doctrine.ORM' => [
                        'state' => 'active',
                        'dependencies' => ['Doctrine.Common', 'Doctrine.DBAL']
                    ],
                    'Symfony.Component.Yaml' => [
                        'state' => 'active',
                        'dependencies' => []
                    ],
                    'TYPO3.Flow' => [
                        'state' => 'active',
                        'dependencies' => ['Symfony.Component.Yaml', 'Doctrine.Common', 'Doctrine.DBAL', 'Doctrine.ORM']
                    ],
                ],
            ],
            'TYPO3 CMS Extensions' => [
                [
                    'core' => [
                        'state' => 'active',
                        'dependencies' => [],
                    ],
                    'setup' => [
                        'state' => 'active',
                        'dependencies' => ['core'],
                    ],
                    'openid' => [
                        'state' => 'active',
                        'dependencies' => ['core', 'setup']
                    ],
                    'news' => [
                        'state' => 'active',
                        'dependencies' => ['extbase'],
                    ],
                    'extbase' => [
                        'state' => 'active',
                        'dependencies' => ['core'],
                    ],
                    'pt_extbase' => [
                        'state' => 'active',
                        'dependencies' => ['extbase'],
                    ],
                    'foo' => [
                        'state' => 'active',
                        'dependencies' => [],
                    ],
                ],
                [
                    'core', 'setup', 'openid', 'extbase'
                ],
                [
                    'core' => [
                        'state' => 'active',
                        'dependencies' => [],
                    ],
                    'setup' => [
                        'state' => 'active',
                        'dependencies' => ['core'],
                    ],
                    'openid' => [
                        'state' => 'active',
                        'dependencies' => ['core', 'setup']
                    ],
                    'extbase' => [
                        'state' => 'active',
                        'dependencies' => ['core'],
                    ],
                    'foo' => [
                        'state' => 'active',
                        'dependencies' => [],
                    ],
                    'pt_extbase' => [
                        'state' => 'active',
                        'dependencies' => ['extbase'],
                    ],
                    'news' => [
                        'state' => 'active',
                        'dependencies' => ['extbase'],
                    ],
                ],
            ],
            'Dummy Packages' => [
                [
                    'A' => [
                        'state' => 'active',
                        'dependencies' => ['B', 'D', 'C'],
                    ],
                    'B' => [
                        'state' => 'active',
                        'dependencies' => []
                    ],
                    'C' => [
                        'state' => 'active',
                        'dependencies' => ['E']
                    ],
                    'D' => [
                        'state' => 'active',
                        'dependencies' => ['E'],
                    ],
                    'E' => [
                        'state' => 'active',
                        'dependencies' => [],
                    ],
                    'F' => [
                        'state' => 'active',
                        'dependencies' => [],
                    ],
                ],
                [
                    'B', 'C', 'E'
                ],
                [
                    'B' => [
                        'state' => 'active',
                        'dependencies' => [],
                    ],
                    'E' => [
                        'state' => 'active',
                        'dependencies' => [],
                    ],
                    'C' => [
                        'state' => 'active',
                        'dependencies' => ['E'],
                    ],
                    'F' => [
                        'state' => 'active',
                        'dependencies' => [],
                    ],
                    'D' => [
                        'state' => 'active',
                        'dependencies' => ['E'],
                    ],
                    'A' => [
                        'state' => 'active',
                        'dependencies' => ['B', 'D', 'C'],
                    ],
                ],
            ],
        ];
    }
}
