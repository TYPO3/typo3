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
        $dependencyResolver = $this->getAccessibleMock(DependencyResolver::class, array('findFrameworkPackages'));
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
        /** @var DependencyResolver|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface $dependencyResolver */
        $dependencyResolver = $this->getAccessibleMock(DependencyResolver::class, array('findFrameworkPackages'));
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
        $unsortedPackageStatesConfiguration = array(
            'A' => array(
                'dependencies' => array('B'),
            ),
            'B' => array(
                'dependencies' => array('A')
            ),
        );

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionCode(1381960494);

        /** @var DependencyResolver|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface $dependencyResolver */
        $dependencyResolver = $this->getAccessibleMock(DependencyResolver::class, array('findFrameworkPackages'));
        $dependencyResolver->injectDependencyOrderingService(new DependencyOrderingService());
        $dependencyResolver->expects($this->any())->method('findFrameworkPackages')->willReturn(array());
        $dependencyResolver->_call('sortPackageStatesConfigurationByDependency', $unsortedPackageStatesConfiguration);
    }

    /**
     * @return array
     */
    public function buildDependencyGraphBuildsCorrectGraphDataProvider()
    {
        return array(
            'TYPO3 Flow Packages' => array(
                array(
                    'TYPO3.Flow' => array(
                        'dependencies' => array('Symfony.Component.Yaml', 'Doctrine.Common', 'Doctrine.DBAL', 'Doctrine.ORM')
                    ),
                    'Doctrine.ORM' => array(
                        'dependencies' => array('Doctrine.Common', 'Doctrine.DBAL')
                    ),
                    'Doctrine.Common' => array(
                        'dependencies' => array()
                    ),
                    'Doctrine.DBAL' => array(
                        'dependencies' => array('Doctrine.Common')
                    ),
                    'Symfony.Component.Yaml' => array(
                        'dependencies' => array()
                    ),
                ),
                array(
                    'Doctrine.Common'
                ),
                array(
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
                        'Doctrine.Common' => true,
                        'Doctrine.DBAL' => false,
                        'Symfony.Component.Yaml' => false,
                    ),
                ),
            ),
            'TYPO3 CMS Extensions' => array(
                array(
                    'core' => array(
                        'dependencies' => array(),
                    ),
                    'setup' => array(
                        'dependencies' => array('core'),
                    ),
                    'openid' => array(
                        'dependencies' => array('core', 'setup')
                    ),
                    'news' => array(
                        'dependencies' => array('extbase'),
                    ),
                    'extbase' => array(
                        'dependencies' => array('core'),
                    ),
                    'pt_extbase' => array(
                        'dependencies' => array('extbase'),
                    ),
                    'foo' => array(
                        'dependencies' => array(),
                    ),
                ),
                array(
                    'core', 'setup', 'openid', 'extbase'
                ),
                array(
                    'core' => array(
                        'core' => false,
                        'setup' => false,
                        'openid' => false,
                        'news' => false,
                        'extbase' => false,
                        'pt_extbase' => false,
                        'foo' => false
                    ),
                    'setup' => array(
                        'core' => true,
                        'setup' => false,
                        'openid' => false,
                        'news' => false,
                        'extbase' => false,
                        'pt_extbase' => false,
                        'foo' => false
                    ),
                    'openid' => array(
                        'core' => true,
                        'setup' => true,
                        'openid' => false,
                        'news' => false,
                        'extbase' => false,
                        'pt_extbase' => false,
                        'foo' => false
                    ),
                    'news' => array(
                        'core' => false,
                        'setup' => false,
                        'openid' => true,
                        'news' => false,
                        'extbase' => true,
                        'pt_extbase' => false,
                        'foo' => false
                    ),
                    'extbase' => array(
                        'core' => true,
                        'setup' => false,
                        'openid' => false,
                        'news' => false,
                        'extbase' => false,
                        'pt_extbase' => false,
                        'foo' => false
                    ),
                    'pt_extbase' => array(
                        'core' => false,
                        'setup' => false,
                        'openid' => true,
                        'news' => false,
                        'extbase' => true,
                        'pt_extbase' => false,
                        'foo' => false
                    ),
                    'foo' => array(
                        'core' => false,
                        'setup' => false,
                        'openid' => true,
                        'news' => false,
                        'extbase' => true,
                        'pt_extbase' => false,
                        'foo' => false
                    ),
                ),
            ),
            'Dummy Packages' => array(
                array(
                    'A' => array(
                        'dependencies' => array('B', 'D', 'C'),
                    ),
                    'B' => array(
                        'dependencies' => array()
                    ),
                    'C' => array(
                        'dependencies' => array('E')
                    ),
                    'D' => array(
                        'dependencies' => array('E'),
                    ),
                    'E' => array(
                        'dependencies' => array(),
                    ),
                    'F' => array(
                        'dependencies' => array(),
                    ),
                ),
                array(
                    'B', 'C', 'E'
                ),
                array(
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
                        'B' => true,
                        'C' => true,
                        'D' => false,
                        'E' => false,
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
                        'B' => true,
                        'C' => true,
                        'D' => false,
                        'E' => false,
                        'F' => false,
                    ),
                ),
            ),
        );
    }

    /**
     * @return array
     */
    public function packageSortingDataProvider()
    {
        return array(
            'TYPO3 Flow Packages' => array(
                array(
                    'TYPO3.Flow' => array(
                        'dependencies' => array('Symfony.Component.Yaml', 'Doctrine.Common', 'Doctrine.DBAL', 'Doctrine.ORM')
                    ),
                    'Doctrine.ORM' => array(
                        'dependencies' => array('Doctrine.Common', 'Doctrine.DBAL')
                    ),
                    'Doctrine.Common' => array(
                        'dependencies' => array()
                    ),
                    'Doctrine.DBAL' => array(
                        'dependencies' => array('Doctrine.Common')
                    ),
                    'Symfony.Component.Yaml' => array(
                        'dependencies' => array()
                    ),
                ),
                array(
                    'Doctrine.Common'
                ),
                array(
                    'Doctrine.Common',
                    'Doctrine.DBAL',
                    'Doctrine.ORM',
                    'Symfony.Component.Yaml',
                    'TYPO3.Flow',
                ),
            ),
            'TYPO3 CMS Extensions' => array(
                array(
                    'core' => array(
                        'dependencies' => array(),
                    ),
                    'setup' => array(
                        'dependencies' => array('core'),
                    ),
                    'openid' => array(
                        'dependencies' => array('core', 'setup')
                    ),
                    'news' => array(
                        'dependencies' => array('extbase'),
                    ),
                    'extbase' => array(
                        'dependencies' => array('core'),
                    ),
                    'pt_extbase' => array(
                        'dependencies' => array('extbase'),
                    ),
                    'foo' => array(
                        'dependencies' => array(),
                    ),
                ),
                array(
                    'core', 'setup', 'openid', 'extbase'
                ),
                array(
                    'core',
                    'setup',
                    'openid',
                    'extbase',
                    'foo',
                    'news',
                    'pt_extbase',
                ),
            ),
            'Dummy Packages' => array(
                array(
                    'A' => array(
                        'dependencies' => array('B', 'D', 'C'),
                    ),
                    'B' => array(
                        'dependencies' => array()
                    ),
                    'C' => array(
                        'dependencies' => array('E')
                    ),
                    'D' => array(
                        'dependencies' => array('E'),
                    ),
                    'E' => array(
                        'dependencies' => array(),
                    ),
                    'F' => array(
                        'dependencies' => array(),
                    ),
                ),
                array(
                    'B', 'C', 'E'
                ),
                array(
                    'E',
                    'C',
                    'B',
                    'D',
                    'A',
                    'F',
                ),
            ),
        );
    }
}
