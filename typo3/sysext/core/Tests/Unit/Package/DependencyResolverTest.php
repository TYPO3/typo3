<?php
namespace TYPO3\CMS\Core\Tests\Unit\Package;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Markus Klein <klein.t3@mfc-linz.at>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use TYPO3\CMS\Core\Package\DependencyResolver;

/**
 * Testcase for the dependency resolver class
 *
 * @author Markus Klein <klein.t3@mfc-linz.at>
 */
class DependencyResolverTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @test
	 * @param array $unsortedPackageStatesConfiguration
	 * @param array $frameworkPackageKeys
	 * @param array $expectedGraph
	 * @dataProvider buildDependencyGraphBuildsCorrectGraphDataProvider
	 */
	public function buildDependencyGraphBuildsCorrectGraph(array $unsortedPackageStatesConfiguration, array $frameworkPackageKeys, array $expectedGraph) {
		$packageKeys = array_keys($unsortedPackageStatesConfiguration);

		$basePathAssignment = array(
			array($unsortedPackageStatesConfiguration, '', array(DependencyResolver::SYSEXT_FOLDER), array_diff($packageKeys, $frameworkPackageKeys)),
			array($unsortedPackageStatesConfiguration, DependencyResolver::SYSEXT_FOLDER, array(), $frameworkPackageKeys),
		);

		$dependencyResolver = $this->getAccessibleMock('\TYPO3\CMS\Core\Package\DependencyResolver', array('getPackageKeysInBasePath'));
		$dependencyResolver->expects($this->any())->method('getPackageKeysInBasePath')->will($this->returnValueMap($basePathAssignment));
		$dependencyGraph = $dependencyResolver->_call('buildDependencyGraph', $unsortedPackageStatesConfiguration);

		$this->assertEquals($expectedGraph, $dependencyGraph);
	}

	/**
	 * @test
	 * @dataProvider packageSortingDataProvider
	 */
	public function sortPackageStatesConfigurationByDependencyMakesSureThatDependantPackagesAreStandingBeforeAPackageInTheInternalPackagesAndPackagesConfigurationArrays($unsortedPackageStatesConfiguration, $frameworkPackageKeys, $expectedSortedPackageStatesConfiguration) {
		$packageKeys = array_keys($unsortedPackageStatesConfiguration);

		$basePathAssignment = array(
			array($unsortedPackageStatesConfiguration, '', array(DependencyResolver::SYSEXT_FOLDER), array_diff($packageKeys, $frameworkPackageKeys)),
			array($unsortedPackageStatesConfiguration, DependencyResolver::SYSEXT_FOLDER, array(), $frameworkPackageKeys),
		);

		$dependencyResolver = $this->getAccessibleMock('\TYPO3\CMS\Core\Package\DependencyResolver', array('getPackageKeysInBasePath'));
		$dependencyResolver->expects($this->any())->method('getPackageKeysInBasePath')->will($this->returnValueMap($basePathAssignment));
		$sortedPackageStatesConfiguration = $dependencyResolver->_call('sortPackageStatesConfigurationByDependency', $unsortedPackageStatesConfiguration);

		$this->assertEquals($expectedSortedPackageStatesConfiguration, $sortedPackageStatesConfiguration, 'The package states configurations have not been ordered according to their dependencies!');
	}

	/**
	 * @test
	 * @dataProvider buildDependencyGraphForPackagesBuildsCorrectGraphDataProvider
	 */
	public function buildDependencyGraphForPackagesBuildsCorrectGraph($packages, $expectedGraph) {
		$dependencyResolver = $this->getAccessibleMock('\TYPO3\CMS\Core\Package\DependencyResolver', array('dummy'));
		$dependencyGraph = $dependencyResolver->_call('buildDependencyGraphForPackages', $packages, array_keys($packages));

		$this->assertEquals($expectedGraph, $dependencyGraph);
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

		$packageKeys = array_keys($unsortedPackageStatesConfiguration);

		$basePathAssignment = array(
			array($unsortedPackageStatesConfiguration, '', array(DependencyResolver::SYSEXT_FOLDER), $packageKeys),
			array($unsortedPackageStatesConfiguration, DependencyResolver::SYSEXT_FOLDER, array(), array()),
		);

		$dependencyResolver = $this->getAccessibleMock('\TYPO3\CMS\Core\Package\DependencyResolver', array('getActivePackageKeysOfType'));
		$dependencyResolver->expects($this->any())->method('getActivePackageKeysOfType')->will($this->returnValueMap($basePathAssignment));
		$dependencyResolver->_call('sortPackageStatesConfigurationByDependency', $unsortedPackageStatesConfiguration);
	}

	/**
	 * @test
	 * @expectedException \UnexpectedValueException
	 */
	public function buildDependencyGraphForPackagesThrowsExceptionWhenDependencyOnUnavailablePackageDetected() {
		$packages = array(
			'A' => array(
				'dependencies' => array('B'),
			)
		);
		$dependencyResolver = $this->getAccessibleMock('\TYPO3\CMS\Core\Package\DependencyResolver', array('dummy'));
		$dependencyResolver->_call('buildDependencyGraphForPackages', $packages, array_keys($packages));
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

	/**
	 * @return array
	 */
	public function buildDependencyGraphForPackagesBuildsCorrectGraphDataProvider() {
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
				array(
					'core' => array(
						'state' => 'active',
						'dependencies' => array(),
					),
					'openid' => array(
						'state' => 'active',
						'dependencies' => array('core', 'setup')
					),
					'scheduler' => array (
						'state' => 'active',
						'dependencies' => array('core'),
					),
					'setup' => array (
						'state' => 'active',
						'dependencies' => array('core'),
					),
					'sv' => array (
						'state' => 'active',
						'dependencies' => array('core'),
					),
				),
				array(
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
		);
	}

}
