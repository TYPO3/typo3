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
use TYPO3\CMS\Core\Tests\UnitTestCase;
use TYPO3\CMS\Core\Service\DependencyOrderingService;

/**
 * Test case
 */
class DependencyResolverTest extends UnitTestCase {

	/**
	 * @test
	 * @param array $unsortedPackageStatesConfiguration
	 * @param array $frameworkPackageKeys
	 * @param array $expectedGraph
	 * @dataProvider buildDependencyGraphBuildsCorrectGraphDataProvider
	 */
	public function buildDependencyGraphBuildsCorrectGraph(array $unsortedPackageStatesConfiguration, array $frameworkPackageKeys, array $expectedGraph) {
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
	public function sortPackageStatesConfigurationByDependencyMakesSureThatDependantPackagesAreStandingBeforeAPackageInTheInternalPackagesAndPackagesConfigurationArrays($unsortedPackageStatesConfiguration, $frameworkPackageKeys, $expectedSortedPackageStatesConfiguration) {
		/** @var DependencyResolver|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface $dependencyResolver */
		$dependencyResolver = $this->getAccessibleMock(DependencyResolver::class, array('findFrameworkPackages'));
		$dependencyResolver->injectDependencyOrderingService(new DependencyOrderingService());
		$dependencyResolver->expects($this->any())->method('findFrameworkPackages')->willReturn($frameworkPackageKeys);
		$sortedPackageStatesConfiguration = $dependencyResolver->_call('sortPackageStatesConfigurationByDependency', $unsortedPackageStatesConfiguration);

		$this->assertEquals($expectedSortedPackageStatesConfiguration, $sortedPackageStatesConfiguration, 'The package states configurations have not been ordered according to their dependencies!');
	}

	/**
	 * @test
	 * @expectedException \UnexpectedValueException
	 */
	public function sortPackageStatesConfigurationByDependencyThrowsExceptionWhenCycleDetected() {
		$unsortedPackageStatesConfiguration = array(
			'A' => array(
				'state' => 'active',
				'dependencies' => array('B'),
			),
			'B' => array(
				'state' => 'active',
				'dependencies' => array('A')
			),
		);

		/** @var DependencyResolver|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface $dependencyResolver */
		$dependencyResolver = $this->getAccessibleMock(DependencyResolver::class, array('findFrameworkPackages'));
		$dependencyResolver->injectDependencyOrderingService(new DependencyOrderingService());
		$dependencyResolver->expects($this->any())->method('findFrameworkPackages')->willReturn(array());
		$dependencyResolver->_call('sortPackageStatesConfigurationByDependency', $unsortedPackageStatesConfiguration);
	}

	/**
	 * @return array
	 */
	public function buildDependencyGraphBuildsCorrectGraphDataProvider() {
		return array(
			'TYPO3 Flow Packages' => array(
				array(
					'TYPO3.Flow' => array(
						'state' => 'active',
						'dependencies' => array('Symfony.Component.Yaml', 'Doctrine.Common', 'Doctrine.DBAL', 'Doctrine.ORM')
					),
					'Doctrine.ORM' => array(
						'state' => 'active',
						'dependencies' => array('Doctrine.Common', 'Doctrine.DBAL')
					),
					'Doctrine.Common' => array(
						'state' => 'active',
						'dependencies' => array()
					),
					'Doctrine.DBAL' => array(
						'state' => 'active',
						'dependencies' => array('Doctrine.Common')
					),
					'Symfony.Component.Yaml' => array(
						'state' => 'active',
						'dependencies' => array()
					),
				),
				array(
					'Doctrine.Common'
				),
				array(
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
						'Doctrine.Common' => TRUE,
						'Doctrine.DBAL' => FALSE,
						'Symfony.Component.Yaml' => FALSE,
					),
				),
			),
			'TYPO3 CMS Extensions' => array(
				array(
					'core' => array(
						'state' => 'active',
						'dependencies' => array(),
					),
					'setup' => array (
						'state' => 'active',
						'dependencies' => array('core'),
					),
					'openid' => array(
						'state' => 'active',
						'dependencies' => array('core', 'setup')
					),
					'news' => array (
						'state' => 'active',
						'dependencies' => array('extbase'),
					),
					'extbase' => array (
						'state' => 'active',
						'dependencies' => array('core'),
					),
					'pt_extbase' => array (
						'state' => 'active',
						'dependencies' => array('extbase'),
					),
					'foo' => array (
						'state' => 'active',
						'dependencies' => array(),
					),
				),
				array(
					'core', 'setup', 'openid', 'extbase'
				),
				array(
					'core' => array(
						'core' => FALSE,
						'setup' => FALSE,
						'openid' => FALSE,
						'news' => FALSE,
						'extbase' => FALSE,
						'pt_extbase' => FALSE,
						'foo' => FALSE
					),
					'setup' => array(
						'core' => TRUE,
						'setup' => FALSE,
						'openid' => FALSE,
						'news' => FALSE,
						'extbase' => FALSE,
						'pt_extbase' => FALSE,
						'foo' => FALSE
					),
					'openid' => array (
						'core' => TRUE,
						'setup' => TRUE,
						'openid' => FALSE,
						'news' => FALSE,
						'extbase' => FALSE,
						'pt_extbase' => FALSE,
						'foo' => FALSE
					),
					'news' => array (
						'core' => FALSE,
						'setup' => FALSE,
						'openid' => TRUE,
						'news' => FALSE,
						'extbase' => TRUE,
						'pt_extbase' => FALSE,
						'foo' => FALSE
					),
					'extbase' => array (
						'core' => TRUE,
						'setup' => FALSE,
						'openid' => FALSE,
						'news' => FALSE,
						'extbase' => FALSE,
						'pt_extbase' => FALSE,
						'foo' => FALSE
					),
					'pt_extbase' => array(
						'core' => FALSE,
						'setup' => FALSE,
						'openid' => TRUE,
						'news' => FALSE,
						'extbase' => TRUE,
						'pt_extbase' => FALSE,
						'foo' => FALSE
					),
					'foo' => array(
						'core' => FALSE,
						'setup' => FALSE,
						'openid' => TRUE,
						'news' => FALSE,
						'extbase' => TRUE,
						'pt_extbase' => FALSE,
						'foo' => FALSE
					),
				),
			),
			'Dummy Packages' => array(
				array(
					'A' => array(
						'state' => 'active',
						'dependencies' => array('B', 'D', 'C'),
					),
					'B' => array(
						'state' => 'active',
						'dependencies' => array()
					),
					'C' => array(
						'state' => 'active',
						'dependencies' => array('E')
					),
					'D' => array (
						'state' => 'active',
						'dependencies' => array('E'),
					),
					'E' => array (
						'state' => 'active',
						'dependencies' => array(),
					),
					'F' => array (
						'state' => 'active',
						'dependencies' => array(),
					),
				),
				array(
					'B', 'C', 'E'
				),
				array(
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
						'B' => TRUE,
						'C' => TRUE,
						'D' => FALSE,
						'E' => FALSE,
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
						'B' => TRUE,
						'C' => TRUE,
						'D' => FALSE,
						'E' => FALSE,
						'F' => FALSE,
					),
				),
			),
		);
	}

	/**
	 * @return array
	 */
	public function packageSortingDataProvider() {
		return array(
			'TYPO3 Flow Packages' => array(
				array(
					'TYPO3.Flow' => array(
						'state' => 'active',
						'dependencies' => array('Symfony.Component.Yaml', 'Doctrine.Common', 'Doctrine.DBAL', 'Doctrine.ORM')
					),
					'Doctrine.ORM' => array(
						'state' => 'active',
						'dependencies' => array('Doctrine.Common', 'Doctrine.DBAL')
					),
					'Doctrine.Common' => array(
						'state' => 'active',
						'dependencies' => array()
					),
					'Doctrine.DBAL' => array(
						'state' => 'active',
						'dependencies' => array('Doctrine.Common')
					),
					'Symfony.Component.Yaml' => array(
						'state' => 'active',
						'dependencies' => array()
					),
				),
				array(
					'Doctrine.Common'
				),
				array(
					'Doctrine.Common' => array(
						'state' => 'active',
						'dependencies' => array()
					),
					'Doctrine.DBAL' => array(
						'state' => 'active',
						'dependencies' => array('Doctrine.Common')
					),
					'Doctrine.ORM' => array(
						'state' => 'active',
						'dependencies' => array('Doctrine.Common', 'Doctrine.DBAL')
					),
					'Symfony.Component.Yaml' => array(
						'state' => 'active',
						'dependencies' => array()
					),
					'TYPO3.Flow' => array(
						'state' => 'active',
						'dependencies' => array('Symfony.Component.Yaml', 'Doctrine.Common', 'Doctrine.DBAL', 'Doctrine.ORM')
					),
				),
			),
			'TYPO3 CMS Extensions' => array(
				array(
					'core' => array(
						'state' => 'active',
						'dependencies' => array(),
					),
					'setup' => array (
						'state' => 'active',
						'dependencies' => array('core'),
					),
					'openid' => array(
						'state' => 'active',
						'dependencies' => array('core', 'setup')
					),
					'news' => array (
						'state' => 'active',
						'dependencies' => array('extbase'),
					),
					'extbase' => array (
						'state' => 'active',
						'dependencies' => array('core'),
					),
					'pt_extbase' => array (
						'state' => 'active',
						'dependencies' => array('extbase'),
					),
					'foo' => array (
						'state' => 'active',
						'dependencies' => array(),
					),
				),
				array(
					'core', 'setup', 'openid', 'extbase'
				),
				array(
					'core' => array(
						'state' => 'active',
						'dependencies' => array(),
					),
					'setup' => array (
						'state' => 'active',
						'dependencies' => array('core'),
					),
					'openid' => array(
						'state' => 'active',
						'dependencies' => array('core', 'setup')
					),
					'extbase' => array (
						'state' => 'active',
						'dependencies' => array('core'),
					),
					'foo' => array (
						'state' => 'active',
						'dependencies' => array(),
					),
					'pt_extbase' => array (
						'state' => 'active',
						'dependencies' => array('extbase'),
					),
					'news' => array (
						'state' => 'active',
						'dependencies' => array('extbase'),
					),
				),
			),
			'Dummy Packages' => array(
				array(
					'A' => array(
						'state' => 'active',
						'dependencies' => array('B', 'D', 'C'),
					),
					'B' => array(
						'state' => 'active',
						'dependencies' => array()
					),
					'C' => array(
						'state' => 'active',
						'dependencies' => array('E')
					),
					'D' => array (
						'state' => 'active',
						'dependencies' => array('E'),
					),
					'E' => array (
						'state' => 'active',
						'dependencies' => array(),
					),
					'F' => array (
						'state' => 'active',
						'dependencies' => array(),
					),
				),
				array(
					'B', 'C', 'E'
				),
				array(
					'B' => array(
						'state' => 'active',
						'dependencies' => array(),
					),
					'E' => array (
						'state' => 'active',
						'dependencies' => array(),
					),
					'C' => array (
						'state' => 'active',
						'dependencies' => array('E'),
					),
					'F' => array (
						'state' => 'active',
						'dependencies' => array(),
					),
					'D' => array(
						'state' => 'active',
						'dependencies' => array('E'),
					),
					'A' => array(
						'state' => 'active',
						'dependencies' => array('B', 'D', 'C'),
					),
				),
			),
		);
	}

}
