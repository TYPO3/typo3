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

use org\bovigo\vfs\vfsStream;
use TYPO3\CMS\Core\Cache\Backend\SimpleFileBackend;
use TYPO3\CMS\Core\Cache\Frontend\PhpFrontend;
use TYPO3\CMS\Core\Package\Exception\ProtectedPackageKeyException;
use TYPO3\CMS\Core\Package\Exception\UnknownPackageException;
use TYPO3\CMS\Core\Package\Package;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Service\DependencyOrderingService;

/**
 * Testcase for the default package manager
 */
class PackageManagerTest extends \TYPO3\TestingFramework\Core\Unit\UnitTestCase
{
    /**
     * Subject is not notice free, disable E_NOTICES
     */
    protected static $suppressNotices = true;

    /**
     * @var PackageManager|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\TestingFramework\Core\AccessibleObjectInterface $packageManager
     */
    protected $packageManager;

    /**
     * Sets up this test case
     */
    protected function setUp()
    {
        vfsStream::setup('Test');

        /** @var PhpFrontend|\PHPUnit_Framework_MockObject_MockObject $mockCache */
        $mockCache = $this->getMockBuilder(PhpFrontend::class)
            ->setMethods(['has', 'set', 'getBackend'])
            ->disableOriginalConstructor()
            ->getMock();
        $mockCacheBackend = $this->getMockBuilder(SimpleFileBackend::class)
            ->setMethods(['has', 'set', 'getBackend', 'getCacheDirectory'])
            ->disableOriginalConstructor()
            ->getMock();
        $mockCache->expects($this->any())->method('has')->will($this->returnValue(false));
        $mockCache->expects($this->any())->method('set')->will($this->returnValue(true));
        $mockCache->expects($this->any())->method('getBackend')->will($this->returnValue($mockCacheBackend));
        $mockCacheBackend->expects($this->any())->method('getCacheDirectory')->will($this->returnValue('vfs://Test/Cache'));
        $this->packageManager = $this->getAccessibleMock(
            PackageManager::class,
            ['sortAndSavePackageStates', 'sortActivePackagesByDependencies', 'registerTransientClassLoadingInformationForPackage'],
            [new DependencyOrderingService]
        );

        mkdir('vfs://Test/Packages/Application', 0700, true);
        mkdir('vfs://Test/Configuration');
        file_put_contents('vfs://Test/Configuration/PackageStates.php', "<?php return array ('packages' => array(), 'version' => 5); ");

        $composerNameToPackageKeyMap = [
            'typo3/flow' => 'TYPO3.Flow'
        ];

        $this->packageManager->injectCoreCache($mockCache);
        $this->inject($this->packageManager, 'composerNameToPackageKeyMap', $composerNameToPackageKeyMap);
        $this->packageManager->_set('packagesBasePath', 'vfs://Test/Packages/');
        $this->packageManager->_set('packageStatesPathAndFilename', 'vfs://Test/Configuration/PackageStates.php');
    }

    /**
     * @param string $packageKey
     * @return Package
     */
    protected function createPackage($packageKey)
    {
        $packagePath = 'vfs://Test/Packages/Application/' . $packageKey . '/';
        mkdir($packagePath, 0770, true);
        file_put_contents($packagePath . 'ext_emconf.php', '<?php' . LF . '$EM_CONF[$_EXTKEY] = [];');
        file_put_contents($packagePath . 'composer.json', '{}');
        $package = new Package($this->packageManager, $packageKey, $packagePath);
        $this->packageManager->registerPackage($package);
        $this->packageManager->activatePackage($packageKey);

        return $package;
    }

    /**
     * @test
     */
    public function getPackageReturnsTheSpecifiedPackage()
    {
        $this->createPackage('TYPO3.MyPackage');
        $package = $this->packageManager->getPackage('TYPO3.MyPackage');

        $this->assertInstanceOf(Package::class, $package, 'The result of getPackage() was no valid package object.');
    }

    /**
     * @test
     */
    public function getPackageThrowsExceptionOnUnknownPackage()
    {
        $this->expectException(UnknownPackageException::class);
        $this->expectExceptionCode(1166546734);

        $this->packageManager->getPackage('PrettyUnlikelyThatThisPackageExists');
    }

    /**
     * @test
     */
    public function scanAvailablePackagesTraversesThePackagesDirectoryAndRegistersPackagesItFinds()
    {
        $expectedPackageKeys = [
            $this->getUniqueId('TYPO3.CMS'),
            $this->getUniqueId('TYPO3.CMS.Test'),
            $this->getUniqueId('TYPO3.YetAnotherTestPackage'),
            $this->getUniqueId('Lolli.Pop.NothingElse')
        ];

        foreach ($expectedPackageKeys as $packageKey) {
            $packagePath = 'vfs://Test/Packages/Application/' . $packageKey . '/';

            mkdir($packagePath, 0770, true);
            file_put_contents($packagePath . 'composer.json', '{"name": "' . $packageKey . '", "type": "typo3-test"}');
        }

        $packageManager = $this->getAccessibleMock(PackageManager::class, ['sortAndSavePackageStates'], [new DependencyOrderingService]);
        $packageManager->_set('packagesBasePath', 'vfs://Test/Packages/');
        $packageManager->_set('packageStatesPathAndFilename', 'vfs://Test/Configuration/PackageStates.php');

        $packageManager->_set('packages', []);
        $packageManager->_call('scanAvailablePackages');

        $packageStates = require 'vfs://Test/Configuration/PackageStates.php';
        $actualPackageKeys = array_keys($packageStates['packages']);
        $this->assertEquals(sort($expectedPackageKeys), sort($actualPackageKeys));
    }

    /**
     * @test
     */
    public function scanAvailablePackagesKeepsExistingPackageConfiguration()
    {
        $expectedPackageKeys = [
            $this->getUniqueId('TYPO3.CMS'),
            $this->getUniqueId('TYPO3.CMS.Test'),
            $this->getUniqueId('TYPO3.YetAnotherTestPackage'),
            $this->getUniqueId('Lolli.Pop.NothingElse')
        ];

        $packagePaths = [];
        foreach ($expectedPackageKeys as $packageKey) {
            $packagePath = 'vfs://Test/Packages/Application/' . $packageKey . '/';
            $packagePaths[] = $packagePath;

            mkdir($packagePath, 0770, true);
            file_put_contents($packagePath . 'composer.json', '{"name": "' . $packageKey . '", "type": "typo3-cms-test"}');
            file_put_contents($packagePath . 'ext_emconf.php', '<?php' . LF . '$EM_CONF[$_EXTKEY] = [];');
        }

        /** @var PackageManager|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\TestingFramework\Core\AccessibleObjectInterface $packageManager */
        $packageManager = $this->getAccessibleMock(PackageManager::class, ['dummy'], [new DependencyOrderingService]);
        $packageManager->_set('packagesBasePaths', $packagePaths);
        $packageManager->_set('packagesBasePath', 'vfs://Test/Packages/');
        $packageManager->_set('packageStatesPathAndFilename', 'vfs://Test/Configuration/PackageStates.php');

        $packageKey = $expectedPackageKeys[0];
        $packageManager->_set('packageStatesConfiguration', [
            'packages' => [
                $packageKey => [
                    'packagePath' => 'Application/' . $packageKey . '/'
                ]
            ],
            'version' => 5
        ]);
        $packageManager->_call('scanAvailablePackages');
        $packageManager->_call('sortAndSavePackageStates');

        $packageStates = require 'vfs://Test/Configuration/PackageStates.php';
        $this->assertEquals('Application/' . $packageKey . '/', $packageStates['packages'][$packageKey]['packagePath']);
    }

    /**
     * @test
     */
    public function packageStatesConfigurationContainsRelativePaths()
    {
        $packageKeys = [
            $this->getUniqueId('Lolli.Pop.NothingElse'),
            $this->getUniqueId('TYPO3.Package'),
            $this->getUniqueId('TYPO3.YetAnotherTestPackage')
        ];

        $packagePaths = [];
        foreach ($packageKeys as $packageKey) {
            $packagePath = 'vfs://Test/Packages/Application/' . $packageKey . '/';

            mkdir($packagePath, 0770, true);
            file_put_contents($packagePath . 'composer.json', '{"name": "' . $packageKey . '", "type": "typo3-cms-test"}');
            file_put_contents($packagePath . 'ext_emconf.php', '<?php' . LF . '$EM_CONF[$_EXTKEY] = [];');
            $packagePaths[] = $packagePath;
        }

        /** @var PackageManager|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\TestingFramework\Core\AccessibleObjectInterface $packageManager */
        $packageManager = $this->getAccessibleMock(PackageManager::class, ['sortAndSavePackageStates', 'registerTransientClassLoadingInformationForPackage'], [new DependencyOrderingService]);
        $packageManager->_set('packagesBasePaths', $packagePaths);
        $packageManager->_set('packagesBasePath', 'vfs://Test/Packages/');
        $packageManager->_set('packageStatesPathAndFilename', 'vfs://Test/Configuration/PackageStates.php');

        $packageManager->_set('packages', []);
        $packageManager->_call('scanAvailablePackages');

        $expectedPackageStatesConfiguration = [];
        foreach ($packageKeys as $packageKey) {
            $expectedPackageStatesConfiguration[$packageKey] = [
                'packagePath' => 'Application/' . $packageKey . '/'
            ];
            $packageManager->activatePackage($packageKey);
        }

        $actualPackageStatesConfiguration = $packageManager->_get('packageStatesConfiguration');
        $this->assertEquals($expectedPackageStatesConfiguration, $actualPackageStatesConfiguration['packages']);
    }

    /**
     * Data Provider returning valid package keys and the corresponding path
     *
     * @return array
     */
    public function packageKeysAndPaths()
    {
        return [
            ['TYPO3.YetAnotherTestPackage', 'vfs://Test/Packages/Application/TYPO3.YetAnotherTestPackage/'],
            ['Lolli.Pop.NothingElse', 'vfs://Test/Packages/Application/Lolli.Pop.NothingElse/']
        ];
    }

    /**
     * @test
     * @dataProvider packageKeysAndPaths
     */
    public function createPackageCreatesPackageFolderAndReturnsPackage($packageKey, $expectedPackagePath)
    {
        $actualPackage = $this->createPackage($packageKey);
        $actualPackagePath = $actualPackage->getPackagePath();

        $this->assertEquals($expectedPackagePath, $actualPackagePath);
        $this->assertTrue(is_dir($actualPackagePath), 'Package path should exist after createPackage()');
        $this->assertEquals($packageKey, $actualPackage->getPackageKey());
        $this->assertTrue($this->packageManager->isPackageAvailable($packageKey));
    }

    /**
     * @test
     */
    public function activatePackageAndDeactivatePackageActivateAndDeactivateTheGivenPackage()
    {
        $packageKey = 'Acme.YetAnotherTestPackage';

        $this->createPackage($packageKey);

        $this->packageManager->expects($this->any())->method('sortActivePackagesByDependencies')->will($this->returnValue([]));

        $this->packageManager->deactivatePackage($packageKey);
        $this->assertFalse($this->packageManager->isPackageActive($packageKey));

        $this->packageManager->activatePackage($packageKey);
        $this->assertTrue($this->packageManager->isPackageActive($packageKey));
    }

    /**
     * @test
     */
    public function deactivatePackageThrowsAnExceptionIfPackageIsProtected()
    {
        $this->expectException(ProtectedPackageKeyException::class);
        $this->expectExceptionCode(1308662891);

        $package = $this->createPackage('Acme.YetAnotherTestPackage');
        $package->setProtected(true);
        $this->packageManager->expects($this->any())->method('sortActivePackagesByDependencies')->will($this->returnValue([]));
        $this->packageManager->deactivatePackage('Acme.YetAnotherTestPackage');
    }

    /**
     * @test
     */
    public function deletePackageThrowsErrorIfPackageIsNotAvailable()
    {
        $this->expectException(UnknownPackageException::class);
        $this->expectExceptionCode(1166543253);

        $this->packageManager->expects($this->any())->method('sortActivePackagesByDependencies')->will($this->returnValue([]));
        $this->packageManager->deletePackage('PrettyUnlikelyThatThisPackageExists');
    }

    /**
     * @test
     */
    public function deletePackageThrowsAnExceptionIfPackageIsProtected()
    {
        $this->expectException(ProtectedPackageKeyException::class);
        $this->expectExceptionCode(1220722120);

        $package = $this->createPackage('Acme.YetAnotherTestPackage');
        $package->setProtected(true);
        $this->packageManager->deletePackage('Acme.YetAnotherTestPackage');
    }

    /**
     * @test
     */
    public function deletePackageRemovesPackageFromAvailableAndActivePackagesAndDeletesThePackageDirectory()
    {
        $this->createPackage('Acme.YetAnotherTestPackage');

        $this->packageManager->expects($this->any())->method('sortActivePackagesByDependencies')->will($this->returnValue([]));

        $this->assertTrue($this->packageManager->isPackageActive('Acme.YetAnotherTestPackage'));
        $this->assertTrue($this->packageManager->isPackageAvailable('Acme.YetAnotherTestPackage'));

        $this->packageManager->deletePackage('Acme.YetAnotherTestPackage');

        $this->assertFalse($this->packageManager->isPackageActive('Acme.YetAnotherTestPackage'));
        $this->assertFalse($this->packageManager->isPackageAvailable('Acme.YetAnotherTestPackage'));
    }

    /**
     * @return array
     */
    public function composerNamesAndPackageKeys()
    {
        return [
            ['imagine/Imagine', 'imagine.Imagine'],
            ['imagine/imagine', 'imagine.Imagine'],
            ['typo3/cms', 'TYPO3.CMS'],
            ['TYPO3/CMS', 'TYPO3.CMS']
        ];
    }

    /**
     * @test
     * @dataProvider composerNamesAndPackageKeys
     */
    public function getPackageKeyFromComposerNameIgnoresCaseDifferences($composerName, $packageKey)
    {
        $packageStatesConfiguration = [
            'packages' => [
                'TYPO3.CMS',
                'imagine.Imagine'
            ]
        ];
        $composerNameToPackageKeyMap = [
            'typo3/cms' => 'TYPO3.CMS',
            'imagine/imagine' => 'imagine.Imagine'
        ];

        $packageManager = $this->getAccessibleMock(PackageManager::class, ['resolvePackageDependencies'], [new DependencyOrderingService]);
        $packageManager->_set('packageStatesConfiguration', $packageStatesConfiguration);
        $packageManager->_set('composerNameToPackageKeyMap', $composerNameToPackageKeyMap);

        $this->assertEquals($packageKey, $packageManager->_call('getPackageKeyFromComposerName', $composerName));
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
                    'core',
                    'setup',
                    'openid',
                    'extbase'
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
                    'B',
                    'C',
                    'E'
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
     * @test
     * @param array $unsortedPackageStatesConfiguration
     * @param array $frameworkPackageKeys
     * @param array $expectedGraph
     * @dataProvider buildDependencyGraphBuildsCorrectGraphDataProvider
     */
    public function buildDependencyGraphBuildsCorrectGraph(array $unsortedPackageStatesConfiguration, array $frameworkPackageKeys, array $expectedGraph)
    {
        $packageManager = $this->getAccessibleMock(PackageManager::class, ['findFrameworkPackages'], [new DependencyOrderingService]);
        $packageManager->expects($this->any())->method('findFrameworkPackages')->willReturn($frameworkPackageKeys);

        $dependencyGraph = $packageManager->_call('buildDependencyGraph', $unsortedPackageStatesConfiguration);

        $this->assertEquals($expectedGraph, $dependencyGraph);
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
                    'core',
                    'setup',
                    'openid',
                    'extbase'
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
                    'B',
                    'C',
                    'E'
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

    /**
     * @test
     * @dataProvider packageSortingDataProvider
     * @param array $unsortedPackageStatesConfiguration
     * @param array $frameworkPackageKeys
     */
    public function sortPackageStatesConfigurationByDependencyMakesSureThatDependantPackagesAreStandingBeforeAPackageInTheInternalPackagesAndPackagesConfigurationArrays($unsortedPackageStatesConfiguration, $frameworkPackageKeys, $expectedSortedPackageKeys)
    {
        $packageManager = $this->getAccessibleMock(PackageManager::class, ['findFrameworkPackages'], [new DependencyOrderingService]);
        $packageManager->expects($this->any())->method('findFrameworkPackages')->willReturn($frameworkPackageKeys);

        $sortedPackageKeys = $packageManager->_call('sortPackageStatesConfigurationByDependency', $unsortedPackageStatesConfiguration);

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

        $packageManager = $this->getAccessibleMock(PackageManager::class, ['findFrameworkPackages'], [new DependencyOrderingService]);
        $packageManager->expects($this->any())->method('findFrameworkPackages')->willReturn([]);

        $packageManager->_call('sortPackageStatesConfigurationByDependency', $unsortedPackageStatesConfiguration);
    }
}
