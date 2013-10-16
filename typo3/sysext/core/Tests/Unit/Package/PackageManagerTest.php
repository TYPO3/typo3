<?php
namespace TYPO3\CMS\Core\Tests\Unit\Package;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Package\PackageInterface;
use org\bovigo\vfs\vfsStream;

/**
 * Testcase for the default package manager
 *
 */
class PackageManagerTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\Flow\Package\PackageManager
	 */
	protected $packageManager;

	/**
	 * Sets up this test case
	 *
	 */
	protected function setUp() {
		vfsStream::setup('Test');
		$mockBootstrap = $this->getMock('TYPO3\CMS\Core\Core\Bootstrap', array(), array(), '', FALSE);
		$mockCache = $this->getMock('TYPO3\CMS\Core\Cache\Frontend\PhpFrontend', array('has', 'set', 'getBackend'), array(), '', FALSE);
		$mockCacheBackend = $this->getMock('TYPO3\CMS\Core\Cache\Backend\SimpleFileBackend', array('has', 'set', 'getBackend'), array(), '', FALSE);
		$mockCache->expects($this->any())->method('has')->will($this->returnValue(FALSE));
		$mockCache->expects($this->any())->method('set')->will($this->returnValue(TRUE));
		$mockCache->expects($this->any())->method('getBackend')->will($this->returnValue($mockCacheBackend));
		$mockCacheBackend->expects($this->any())->method('getCacheDirectory')->will($this->returnValue('vfs://Test/Cache'));
		$this->packageManager = $this->getMock('TYPO3\\CMS\\Core\\Package\\PackageManager', array('sortAndSavePackageStates'));

		mkdir('vfs://Test/Packages/Application', 0700, TRUE);
		mkdir('vfs://Test/Configuration');
		file_put_contents('vfs://Test/Configuration/PackageStates.php', "<?php return array ('packages' => array(), 'version' => 4); ");

		$mockClassLoader = $this->getMock('TYPO3\CMS\Core\Core\ClassLoader');
		$mockClassLoader->expects($this->any())->method('setCacheIdentifier')->will($this->returnSelf());

		$composerNameToPackageKeyMap = array(
			'typo3/flow' => 'TYPO3.Flow'
		);

		$this->packageManager->injectClassLoader($mockClassLoader);
		$this->packageManager->injectCoreCache($mockCache);
		$this->inject($this->packageManager, 'composerNameToPackageKeyMap', $composerNameToPackageKeyMap);
		$this->packageManager->initialize($mockBootstrap, 'vfs://Test/Packages/', 'vfs://Test/Configuration/PackageStates.php');
	}

	/**
	 * @test
	 */
	public function getPackageReturnsTheSpecifiedPackage() {
		$this->packageManager->createPackage('TYPO3.Flow');

		$package = $this->packageManager->getPackage('TYPO3.Flow');
		$this->assertInstanceOf('TYPO3\Flow\Package\PackageInterface', $package, 'The result of getPackage() was no valid package object.');
	}

	/**
	 * @test
	 * @expectedException \TYPO3\Flow\Package\Exception\UnknownPackageException
	 */
	public function getPackageThrowsExceptionOnUnknownPackage() {
		$this->packageManager->getPackage('PrettyUnlikelyThatThisPackageExists');
	}

	/**
	 * @test
	 */
	public function getCaseSensitivePackageKeyReturnsTheUpperCamelCaseVersionOfAGivenPackageKeyIfThePackageIsRegistered() {
		$packageManager = $this->getAccessibleMock('TYPO3\Flow\Package\PackageManager', array('dummy'));
		$packageManager->_set('packageKeys', array('acme.testpackage' => 'Acme.TestPackage'));
		$this->assertEquals('Acme.TestPackage', $packageManager->getCaseSensitivePackageKey('acme.testpackage'));
	}

	/**
	 * @test
	 */
	public function scanAvailablePackagesTraversesThePackagesDirectoryAndRegistersPackagesItFinds() {
		$expectedPackageKeys = array(
			'TYPO3.Flow' . md5(uniqid(mt_rand(), TRUE)),
			'TYPO3.Flow.Test' . md5(uniqid(mt_rand(), TRUE)),
			'TYPO3.YetAnotherTestPackage' . md5(uniqid(mt_rand(), TRUE)),
			'RobertLemke.Flow.NothingElse' . md5(uniqid(mt_rand(), TRUE))
		);

		foreach ($expectedPackageKeys as $packageKey) {
			$packagePath = 'vfs://Test/Packages/Application/' . $packageKey . '/';

			mkdir($packagePath, 0770, TRUE);
			mkdir($packagePath . 'Classes');
			file_put_contents($packagePath . 'composer.json', '{"name": "' . $packageKey . '", "type": "flow-test"}');
		}

		$packageManager = $this->getAccessibleMock('TYPO3\Flow\Package\PackageManager', array('dummy'));
		$packageManager->_set('packagesBasePath', 'vfs://Test/Packages/');
		$packageManager->_set('packageStatesPathAndFilename', 'vfs://Test/Configuration/PackageStates.php');

		$packageFactory = new \TYPO3\Flow\Package\PackageFactory($packageManager);
		$this->inject($packageManager, 'packageFactory', $packageFactory);

		$packageManager->_set('packages', array());
		$packageManager->_call('scanAvailablePackages');

		$packageStates = require('vfs://Test/Configuration/PackageStates.php');
		$actualPackageKeys = array_keys($packageStates['packages']);
		$this->assertEquals(sort($expectedPackageKeys), sort($actualPackageKeys));
	}

	/**
	 * @test
	 */
	public function scanAvailablePackagesKeepsExistingPackageConfiguration() {
		$expectedPackageKeys = array(
			'TYPO3.Flow' . md5(uniqid(mt_rand(), TRUE)),
			'TYPO3.Flow.Test' . md5(uniqid(mt_rand(), TRUE)),
			'TYPO3.YetAnotherTestPackage' . md5(uniqid(mt_rand(), TRUE)),
			'RobertLemke.Flow.NothingElse' . md5(uniqid(mt_rand(), TRUE))
		);

		foreach ($expectedPackageKeys as $packageKey) {
			$packagePath = 'vfs://Test/Packages/Application/' . $packageKey . '/';

			mkdir($packagePath, 0770, TRUE);
			mkdir($packagePath . 'Classes');
			file_put_contents($packagePath . 'composer.json', '{"name": "' . $packageKey . '", "type": "flow-test"}');
		}

		$packageManager = $this->getAccessibleMock('TYPO3\Flow\Package\PackageManager', array('dummy'));
		$packageManager->_set('packagesBasePath', 'vfs://Test/Packages/');
		$packageManager->_set('packageStatesPathAndFilename', 'vfs://Test/Configuration/PackageStates.php');

		$packageFactory = new \TYPO3\Flow\Package\PackageFactory($packageManager);
		$this->inject($packageManager, 'packageFactory', $packageFactory);

		$packageManager->_set('packageStatesConfiguration', array(
			'packages' => array(
				$packageKey => array(
					'state' => 'inactive',
					'frozen' => FALSE,
					'packagePath' => 'Application/' . $packageKey . '/',
					'classesPath' => 'Classes/'
				)
			),
			'version' => 2
		));
		$packageManager->_call('scanAvailablePackages');
		$packageManager->_call('sortAndsavePackageStates');

		$packageStates = require('vfs://Test/Configuration/PackageStates.php');
		$this->assertEquals('inactive', $packageStates['packages'][$packageKey]['state']);
	}


	/**
	 * @test
	 */
	public function packageStatesConfigurationContainsRelativePaths() {
		$packageKeys = array(
			'RobertLemke.Flow.NothingElse' . md5(uniqid(mt_rand(), TRUE)),
			'TYPO3.Flow' . md5(uniqid(mt_rand(), TRUE)),
			'TYPO3.YetAnotherTestPackage' . md5(uniqid(mt_rand(), TRUE)),
		);

		foreach ($packageKeys as $packageKey) {
			$packagePath = 'vfs://Test/Packages/Application/' . $packageKey . '/';

			mkdir($packagePath, 0770, TRUE);
			mkdir($packagePath . 'Classes');
			file_put_contents($packagePath . 'composer.json', '{"name": "' . $packageKey . '", "type": "flow-test"}');
		}

		$packageManager = $this->getAccessibleMock('TYPO3\Flow\Package\PackageManager', array('updateShortcuts'), array(), '', FALSE);
		$packageManager->_set('packagesBasePath', 'vfs://Test/Packages/');
		$packageManager->_set('packageStatesPathAndFilename', 'vfs://Test/Configuration/PackageStates.php');

		$packageFactory = new \TYPO3\Flow\Package\PackageFactory($packageManager);
		$this->inject($packageManager, 'packageFactory', $packageFactory);

		$packageManager->_set('packages', array());
		$packageManager->_call('scanAvailablePackages');

		$expectedPackageStatesConfiguration = array();
		foreach ($packageKeys as $packageKey) {
			$expectedPackageStatesConfiguration[$packageKey] = array(
				'state' => 'active',
				'packagePath' => 'Application/' . $packageKey . '/',
				'classesPath' => 'Classes/',
				'manifestPath' => '',
				'composerName' => $packageKey
			);
		}

		$actualPackageStatesConfiguration = $packageManager->_get('packageStatesConfiguration');
		$this->assertEquals($expectedPackageStatesConfiguration, $actualPackageStatesConfiguration['packages']);
	}

	/**
	 * Data Provider returning valid package keys and the corresponding path
	 *
	 * @return array
	 */
	public function packageKeysAndPaths() {
		return array(
			array('TYPO3.YetAnotherTestPackage', 'vfs://Test/Packages/Application/TYPO3.YetAnotherTestPackage/'),
			array('RobertLemke.Flow.NothingElse', 'vfs://Test/Packages/Application/RobertLemke.Flow.NothingElse/')
		);
	}

	/**
	 * @test
	 * @dataProvider packageKeysAndPaths
	 */
	public function createPackageCreatesPackageFolderAndReturnsPackage($packageKey, $expectedPackagePath) {
		$actualPackage = $this->packageManager->createPackage($packageKey);
		$actualPackagePath = $actualPackage->getPackagePath();

		$this->assertEquals($expectedPackagePath, $actualPackagePath);
		$this->assertTrue(is_dir($actualPackagePath), 'Package path should exist after createPackage()');
		$this->assertEquals($packageKey, $actualPackage->getPackageKey());
		$this->assertTrue($this->packageManager->isPackageAvailable($packageKey));
	}

	/**
	 * @test
	 */
	public function createPackageWritesAComposerManifestUsingTheGivenMetaObject() {
		$metaData = new \TYPO3\Flow\Package\MetaData('Acme.YetAnotherTestPackage');
		$metaData->setDescription('Yet Another Test Package');

		$package = $this->packageManager->createPackage('Acme.YetAnotherTestPackage', $metaData);

		$json = file_get_contents($package->getPackagePath() . '/composer.json');
		$composerManifest = json_decode($json);

		$this->assertEquals('acme/yetanothertestpackage', $composerManifest->name);
		$this->assertEquals('Yet Another Test Package', $composerManifest->description);
	}

	/**
	 * @test
	 */
	public function createPackageCanChangePackageTypeInComposerManifest() {
		$metaData = new \TYPO3\Flow\Package\MetaData('Acme.YetAnotherTestPackage2');
		$metaData->setDescription('Yet Another Test Package');
		$metaData->setPackageType('flow-custom-package');

		$package = $this->packageManager->createPackage('Acme.YetAnotherTestPackage2', $metaData);

		$json = file_get_contents($package->getPackagePath() . '/composer.json');
		$composerManifest = json_decode($json);

		$this->assertEquals('flow-custom-package', $composerManifest->type);
	}

	/**
	 * Checks if createPackage() creates the folders for classes, configuration, documentation, resources and tests.
	 *
	 * @test
	 */
	public function createPackageCreatesCommonFolders() {
		$package = $this->packageManager->createPackage('Acme.YetAnotherTestPackage');
		$packagePath = $package->getPackagePath();

		$this->assertTrue(is_dir($packagePath . PackageInterface::DIRECTORY_CLASSES), "Classes directory was not created");
		$this->assertTrue(is_dir($packagePath . PackageInterface::DIRECTORY_CONFIGURATION), "Configuration directory was not created");
		$this->assertTrue(is_dir($packagePath . PackageInterface::DIRECTORY_DOCUMENTATION), "Documentation directory was not created");
		$this->assertTrue(is_dir($packagePath . PackageInterface::DIRECTORY_RESOURCES), "Resources directory was not created");
		$this->assertTrue(is_dir($packagePath . PackageInterface::DIRECTORY_TESTS_UNIT), "Tests/Unit directory was not created");
		$this->assertTrue(is_dir($packagePath . PackageInterface::DIRECTORY_TESTS_FUNCTIONAL), "Tests/Functional directory was not created");
		$this->assertTrue(is_dir($packagePath . PackageInterface::DIRECTORY_METADATA), "Metadata directory was not created");
	}

	/**
	 * Makes sure that an exception is thrown and no directory is created on passing invalid package keys.
	 *
	 * @test
	 */
	public function createPackageThrowsExceptionOnInvalidPackageKey() {
		try {
			$this->packageManager->createPackage('Invalid_PackageKey');
		} catch (\TYPO3\Flow\Package\Exception\InvalidPackageKeyException $exception) {
		}
		$this->assertFalse(is_dir('vfs://Test/Packages/Application/Invalid_PackageKey'), 'Package folder with invalid package key was created');
	}

	/**
	 * Makes sure that duplicate package keys are detected.
	 *
	 * @test
	 * @expectedException \TYPO3\Flow\Package\Exception\PackageKeyAlreadyExistsException
	 */
	public function createPackageThrowsExceptionForExistingPackageKey() {
		$this->packageManager->createPackage('Acme.YetAnotherTestPackage');
		$this->packageManager->createPackage('Acme.YetAnotherTestPackage');
	}

	/**
	 * @test
	 */
	public function createPackageActivatesTheNewlyCreatedPackage() {
		$this->packageManager->createPackage('Acme.YetAnotherTestPackage');
		$this->assertTrue($this->packageManager->isPackageActive('Acme.YetAnotherTestPackage'));
	}

	/**
	 * @test
	 */
	public function activatePackageAndDeactivatePackageActivateAndDeactivateTheGivenPackage() {
		$packageKey = 'Acme.YetAnotherTestPackage';

		$this->packageManager->createPackage($packageKey);

		$this->packageManager->deactivatePackage($packageKey);
		$this->assertFalse($this->packageManager->isPackageActive($packageKey));

		$this->packageManager->activatePackage($packageKey);
		$this->assertTrue($this->packageManager->isPackageActive($packageKey));
	}

	/**
	 * @test
	 * @expectedException \TYPO3\Flow\Package\Exception\ProtectedPackageKeyException
	 */
	public function deactivatePackageThrowsAnExceptionIfPackageIsProtected() {
		$package = $this->packageManager->createPackage('Acme.YetAnotherTestPackage');
		$package->setProtected(TRUE);
		$this->packageManager->deactivatePackage('Acme.YetAnotherTestPackage');
	}

	/**
	 * @test
	 * @expectedException \TYPO3\Flow\Package\Exception\UnknownPackageException
	 */
	public function deletePackageThrowsErrorIfPackageIsNotAvailable() {
		$this->packageManager->deletePackage('PrettyUnlikelyThatThisPackageExists');
	}

	/**
	 * @test
	 * @expectedException \TYPO3\Flow\Package\Exception\ProtectedPackageKeyException
	 */
	public function deletePackageThrowsAnExceptionIfPackageIsProtected() {
		$package = $this->packageManager->createPackage('Acme.YetAnotherTestPackage');
		$package->setProtected(TRUE);
		$this->packageManager->deletePackage('Acme.YetAnotherTestPackage');
	}

	/**
	 * @test
	 */
	public function deletePackageRemovesPackageFromAvailableAndActivePackagesAndDeletesThePackageDirectory() {
		$package = $this->packageManager->createPackage('Acme.YetAnotherTestPackage');
		$packagePath = $package->getPackagePath();

		$this->assertTrue(is_dir($packagePath . PackageInterface::DIRECTORY_METADATA));
		$this->assertTrue($this->packageManager->isPackageActive('Acme.YetAnotherTestPackage'));
		$this->assertTrue($this->packageManager->isPackageAvailable('Acme.YetAnotherTestPackage'));

		$this->packageManager->deletePackage('Acme.YetAnotherTestPackage');

		$this->assertFalse(is_dir($packagePath . PackageInterface::DIRECTORY_METADATA));
		$this->assertFalse($this->packageManager->isPackageActive('Acme.YetAnotherTestPackage'));
		$this->assertFalse($this->packageManager->isPackageAvailable('Acme.YetAnotherTestPackage'));
	}

	/**
	 * @test
	 */
	public function getDependencyArrayForPackageReturnsCorrectResult() {
		$mockFlowMetadata = $this->getMock('TYPO3\Flow\Package\MetaDataInterface');
		$mockFlowMetadata->expects($this->any())->method('getConstraintsByType')->will($this->returnValue(array(
			new \TYPO3\Flow\Package\MetaData\PackageConstraint('depends', 'TYPO3.Fluid'),
			new \TYPO3\Flow\Package\MetaData\PackageConstraint('depends', 'Doctrine.ORM')
		)));
		$mockFlowPackage = $this->getMock('TYPO3\Flow\Package\PackageInterface');
		$mockFlowPackage->expects($this->any())->method('getPackageMetaData')->will($this->returnValue($mockFlowMetadata));

		$mockFluidMetadata = $this->getMock('TYPO3\Flow\Package\MetaDataInterface');
		$mockFluidMetadata->expects($this->any())->method('getConstraintsByType')->will($this->returnValue(array(
			new \TYPO3\Flow\Package\MetaData\PackageConstraint('depends', 'TYPO3.Flow')
		)));
		$mockFluidPackage = $this->getMock('TYPO3\Flow\Package\PackageInterface');
		$mockFluidPackage->expects($this->any())->method('getPackageMetaData')->will($this->returnValue($mockFluidMetadata));

		$mockOrmMetadata = $this->getMock('TYPO3\Flow\Package\MetaDataInterface');
		$mockOrmMetadata->expects($this->any())->method('getConstraintsByType')->will($this->returnValue(array(
			new \TYPO3\Flow\Package\MetaData\PackageConstraint('depends', 'Doctrine.DBAL')
		)));
		$mockOrmPackage = $this->getMock('TYPO3\Flow\Package\PackageInterface');
		$mockOrmPackage->expects($this->any())->method('getPackageMetaData')->will($this->returnValue($mockOrmMetadata));

		$mockDbalMetadata = $this->getMock('TYPO3\Flow\Package\MetaDataInterface');
		$mockDbalMetadata->expects($this->any())->method('getConstraintsByType')->will($this->returnValue(array(
			new \TYPO3\Flow\Package\MetaData\PackageConstraint('depends', 'Doctrine.Common')
		)));
		$mockDbalPackage = $this->getMock('TYPO3\Flow\Package\PackageInterface');
		$mockDbalPackage->expects($this->any())->method('getPackageMetaData')->will($this->returnValue($mockDbalMetadata));

		$mockCommonMetadata = $this->getMock('TYPO3\Flow\Package\MetaDataInterface');
		$mockCommonMetadata->expects($this->any())->method('getConstraintsByType')->will($this->returnValue(array()));
		$mockCommonPackage = $this->getMock('TYPO3\Flow\Package\PackageInterface');
		$mockCommonPackage->expects($this->any())->method('getPackageMetaData')->will($this->returnValue($mockCommonMetadata));

		$packages = array(
			'TYPO3.Flow' => $mockFlowPackage,
			'TYPO3.Fluid' => $mockFluidPackage,
			'Doctrine.ORM' => $mockOrmPackage,
			'Doctrine.DBAL' => $mockDbalPackage,
			'Doctrine.Common' => $mockCommonPackage
		);

		$packageManager = $this->getAccessibleMock('\TYPO3\Flow\Package\PackageManager', array('dummy'));
		$packageManager->_set('packages', $packages);
		$dependencyArray = $packageManager->_call('getDependencyArrayForPackage', 'TYPO3.Flow');

		$this->assertEquals(array('Doctrine.Common', 'Doctrine.DBAL', 'Doctrine.ORM', 'TYPO3.Fluid'), $dependencyArray);
	}

	/**
	 * @return array
	 */
	public function buildDependencyGraphBuildsCorrectGraphDataProvider() {
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
						'dependencies' => array(),
					),
					'setup' => array (
						'dependencies' => array('core'),
					),
					'openid' => array(
						'dependencies' => array('core', 'setup')
					),
					'news' => array (
						'dependencies' => array('extbase'),
					),
					'extbase' => array (
						'dependencies' => array('core'),
					),
					'pt_extbase' => array (
						'dependencies' => array('extbase'),
					),
					'foo' => array (
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
						'dependencies' => array('B', 'D', 'C'),
					),
					'B' => array(
						'dependencies' => array()
					),
					'C' => array(
						'dependencies' => array('E')
					),
					'D' => array (
						'dependencies' => array('E'),
					),
					'E' => array (
						'dependencies' => array(),
					),
					'F' => array (
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
	 * @test
	 * @param array $unsorted
	 * @param array $frameworkPackageKeys
	 * @param array $expectedGraph
	 * @dataProvider buildDependencyGraphBuildsCorrectGraphDataProvider
	 */
	public function buildDependencyGraphBuildsCorrectGraph(array $unsorted, array $frameworkPackageKeys, array $expectedGraph) {
		$unsortedPackageStatesConfiguration = array('packages' => $unsorted);

		$packageKeys = array_keys($unsorted);
		$unsortedPackages = array();
		foreach ($packageKeys as $packageKey) {
			$packageMock = $this->getMock('\TYPO3\Flow\Package\PackageInterface');
			$packageMock->expects($this->any())->method('getPackageKey')->will($this->returnValue($packageKey));
			$unsortedPackages[$packageKey] = $packageMock;
		}

		$typeAssignment = array(
			array('', array('typo3-cms-framework'), array_diff($packageKeys, $frameworkPackageKeys)),
			array('typo3-cms-framework', array(), $frameworkPackageKeys),
		);

		$packageManager = $this->getAccessibleMock('\TYPO3\CMS\Core\Package\PackageManager', array('resolvePackageDependencies','getPackageKeysOfType'));
		$packageManager->expects($this->any())->method('getPackageKeysOfType')->will($this->returnValueMap($typeAssignment));
		$packageManager->_set('packages', $unsortedPackages);
		$packageManager->_set('packageStatesConfiguration', $unsortedPackageStatesConfiguration);
		$packageManager->_call('buildDependencyGraph');

		$this->assertEquals($expectedGraph, $packageManager->_get('dependencyGraph'));
	}

	/**
	 * @return array
	 */
	public function packageSortingDataProvider() {
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
					'Doctrine.Common' => array(
						'dependencies' => array()
					),
					'Doctrine.DBAL' => array(
						'dependencies' => array('Doctrine.Common')
					),
					'Doctrine.ORM' => array(
						'dependencies' => array('Doctrine.Common', 'Doctrine.DBAL')
					),
					'Symfony.Component.Yaml' => array(
						'dependencies' => array('Doctrine.Common')
					),
					'TYPO3.Flow' => array(
						'dependencies' => array('Doctrine.Common', 'Symfony.Component.Yaml', 'Doctrine.DBAL', 'Doctrine.ORM')
					),
				),
			),
			'TYPO3 CMS Extensions' => array(
				array(
					'core' => array(
						'dependencies' => array(),
					),
					'setup' => array (
						'dependencies' => array('core'),
					),
					'openid' => array(
						'dependencies' => array('core', 'setup')
					),
					'news' => array (
						'dependencies' => array('extbase'),
					),
					'extbase' => array (
						'dependencies' => array('core'),
					),
					'pt_extbase' => array (
						'dependencies' => array('extbase'),
					),
					'foo' => array (
						'dependencies' => array(),
					),
				),
				array(
					'core', 'setup', 'openid', 'extbase'
				),
				array(
					'core' => array(
						'dependencies' => array(),
					),
					'setup' => array (
						'dependencies' => array('core'),
					),
					'openid' => array(
						'dependencies' => array('core', 'setup')
					),
					'extbase' => array (
						'dependencies' => array('core'),
					),
					'foo' => array (
						'dependencies' => array('openid', 'extbase'),
					),
					'pt_extbase' => array (
						'dependencies' => array('openid', 'extbase'),
					),
					'news' => array (
						'dependencies' => array('openid', 'extbase'),
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
					'D' => array (
						'dependencies' => array('E'),
					),
					'E' => array (
						'dependencies' => array(),
					),
					'F' => array (
						'dependencies' => array(),
					),
				),
				array(
					'B', 'C', 'E'
				),
				array(
					'B' => array(
						'dependencies' => array(),
					),
					'E' => array (
						'dependencies' => array(),
					),
					'C' => array (
						'dependencies' => array('E'),
					),
					'F' => array (
						'dependencies' => array('B', 'C'),
					),
					'D' => array(
						'dependencies' => array('B', 'C'),
					),
					'A' => array(
						'dependencies' => array('B', 'C', 'D'),
					),
				),
			),
		);
	}

	/**
	 * @test
	 * @dataProvider packageSortingDataProvider
	 */
	public function sortAvailablePackagesByDependenciesMakesSureThatDependantPackagesAreStandingBeforeAPackageInTheInternalPackagesAndPackagesConfigurationArrays($unsorted, $frameworkPackageKeys, $expected) {
		$unsortedPackageStatesConfiguration = array('packages' => $unsorted);
		$expectedSortedPackageStatesConfiguration = array('packages' => $expected);

		$unsortedPackages = array();
		$packageKeys = array_keys($unsorted);
		foreach ($packageKeys as $packageKey) {
			$packageMock = $this->getMock('\TYPO3\Flow\Package\PackageInterface');
			$packageMock->expects($this->any())->method('getPackageKey')->will($this->returnValue($packageKey));
			$unsortedPackages[$packageKey] = $packageMock;
		}

		$typeAssignment = array(
			array('', array('typo3-cms-framework'), array_diff($packageKeys, $frameworkPackageKeys)),
			array('typo3-cms-framework', array(), $frameworkPackageKeys),
		);

		$packageManager = $this->getAccessibleMock('\TYPO3\CMS\Core\Package\PackageManager', array('resolvePackageDependencies','getPackageKeysOfType'));
		$packageManager->expects($this->any())->method('getPackageKeysOfType')->will($this->returnValueMap($typeAssignment));
		$packageManager->_set('packages', $unsortedPackages);
		$packageManager->_set('packageStatesConfiguration', $unsortedPackageStatesConfiguration);
		$packageManager->_call('sortAvailablePackagesByDependencies');

		$expectedSortedPackageKeys = array_keys($expected);

		$this->assertEquals($expectedSortedPackageKeys, array_keys($packageManager->_get('packages')), 'The packages have not been ordered according to their dependencies!');
		$this->assertEquals($expectedSortedPackageStatesConfiguration, $packageManager->_get('packageStatesConfiguration'), 'The package states configurations have not been ordered according to their dependencies!');
	}

	/**
	 * @return array
	 */
	public function buildDependencyGraphForPackagesBuildsCorrectGraphDataProvider() {
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
						'dependencies' => array(),
					),
					'openid' => array(
						'dependencies' => array('core', 'setup')
					),
					'scheduler' => array (
						'dependencies' => array('core'),
					),
					'setup' => array (
						'dependencies' => array('core'),
					),
					'sv' => array (
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
						'dependencies' => array('B', 'D', 'C'),
					),
					'B' => array(
						'dependencies' => array()
					),
					'C' => array(
						'dependencies' => array('E')
					),
					'D' => array (
						'dependencies' => array('E'),
					),
					'E' => array (
						'dependencies' => array(),
					),
					'F' => array (
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

	/**
	 * @test
	 * @dataProvider buildDependencyGraphForPackagesBuildsCorrectGraphDataProvider
	 */
	public function buildDependencyGraphForPackagesBuildsCorrectGraph($packages, $expectedGraph) {
		$packageManager = $this->getAccessibleMock('\TYPO3\CMS\Core\Package\PackageManager', array('dummy'));
		$packageManager->_set('packageStatesConfiguration', array('packages' => $packages));
		$packageManager->_call('buildDependencyGraphForPackages', array_keys($packages));

		$this->assertEquals($expectedGraph, $packageManager->_get('dependencyGraph'));
	}


	/**
	 * @test
	 * @expectedException \UnexpectedValueException
	 */
	public function getAvailablePackageLoadingOrderThrowsExceptionWhenCycleDetected() {
		$unsorted = array(
			'A' => array(
				'dependencies' => array('B'),
			),
			'B' => array(
				'dependencies' => array('A')
			),
		);
		$unsortedPackageStatesConfiguration = array('packages' => $unsorted);

		$unsortedPackages = array();
		$packageKeys = array_keys($unsorted);
		foreach ($packageKeys as $packageKey) {
			$packageMock = $this->getMock('\TYPO3\Flow\Package\PackageInterface');
			$packageMock->expects($this->any())->method('getPackageKey')->will($this->returnValue($packageKey));
			$unsortedPackages[$packageKey] = $packageMock;
		}

		$typeAssignment = array(
			array('', array('typo3-cms-framework'), $packageKeys),
			array('typo3-cms-framework', array(), array()),
		);

		$packageManager = $this->getAccessibleMock('\TYPO3\CMS\Core\Package\PackageManager', array('resolvePackageDependencies','getPackageKeysOfType'));
		$packageManager->expects($this->any())->method('getPackageKeysOfType')->will($this->returnValueMap($typeAssignment));
		$packageManager->_set('packages', $unsortedPackages);
		$packageManager->_set('packageStatesConfiguration', $unsortedPackageStatesConfiguration);
		$packageManager->_call('getAvailablePackageLoadingOrder');
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
		$packageManager = $this->getAccessibleMock('\TYPO3\CMS\Core\Package\PackageManager', array('dummy'));
		$packageManager->_set('packageStatesConfiguration', array('packages' => $packages));
		$packageManager->_call('buildDependencyGraphForPackages', array_keys($packages));
	}

	/**
	 * @return array
	 */
	public function composerNamesAndPackageKeys() {
		return array(
			array('imagine/Imagine', 'imagine.Imagine'),
			array('imagine/imagine', 'imagine.Imagine'),
			array('typo3/flow', 'TYPO3.Flow'),
			array('TYPO3/Flow', 'TYPO3.Flow')
		);
	}

	/**
	 * @test
	 * @dataProvider composerNamesAndPackageKeys
	 */
	public function getPackageKeyFromComposerNameIgnoresCaseDifferences($composerName, $packageKey) {
		$packageStatesConfiguration = array('packages' =>
			array(
				'TYPO3.Flow' => array(
					'composerName' => 'typo3/flow'
				),
				'imagine.Imagine' => array(
					'composerName' => 'imagine/Imagine'
				)
			)
		);

		$packageManager = $this->getAccessibleMock('\TYPO3\Flow\Package\PackageManager', array('resolvePackageDependencies'));
		$packageManager->_set('packageStatesConfiguration', $packageStatesConfiguration);

		$this->assertEquals($packageKey, $packageManager->_call('getPackageKeyFromComposerName', $composerName));
	}
}
