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

namespace TYPO3\CMS\Core\Tests\Unit\Package;

use org\bovigo\vfs\vfsStream;
use TYPO3\CMS\Core\Cache\Backend\SimpleFileBackend;
use TYPO3\CMS\Core\Cache\Frontend\PhpFrontend;
use TYPO3\CMS\Core\Package\Exception\InvalidPackageStateException;
use TYPO3\CMS\Core\Package\Exception\PackageStatesFileNotWritableException;
use TYPO3\CMS\Core\Package\Exception\ProtectedPackageKeyException;
use TYPO3\CMS\Core\Package\Exception\UnknownPackageException;
use TYPO3\CMS\Core\Package\Package;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Service\DependencyOrderingService;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Testcase for the default package manager
 */
class PackageManagerTest extends UnitTestCase
{
    /**
     * @var PackageManager|\PHPUnit\Framework\MockObject\MockObject|\TYPO3\TestingFramework\Core\AccessibleObjectInterface $packageManager
     */
    protected $packageManager;

    /**
     * Sets up this test case
     */
    protected function setUp(): void
    {
        parent::setUp();
        vfsStream::setup('Test');

        /** @var PhpFrontend|\PHPUnit\Framework\MockObject\MockObject $mockCache */
        $mockCache = $this->getMockBuilder(PhpFrontend::class)
            ->setMethods(['has', 'set', 'getBackend'])
            ->disableOriginalConstructor()
            ->getMock();
        $mockCacheBackend = $this->getMockBuilder(SimpleFileBackend::class)
            ->setMethods(['has', 'set', 'getBackend', 'getCacheDirectory'])
            ->disableOriginalConstructor()
            ->getMock();
        $mockCache->expects(self::any())->method('has')->willReturn(false);
        $mockCache->expects(self::any())->method('set')->willReturn(true);
        $mockCache->expects(self::any())->method('getBackend')->willReturn($mockCacheBackend);
        $mockCacheBackend->expects(self::any())->method('getCacheDirectory')->willReturn('vfs://Test/Cache');
        $this->packageManager = $this->getAccessibleMock(
            PackageManager::class,
            ['sortAndSavePackageStates', 'sortActivePackagesByDependencies', 'registerTransientClassLoadingInformationForPackage'],
            [new DependencyOrderingService()]
        );

        mkdir('vfs://Test/Packages/Application', 0700, true);
        mkdir('vfs://Test/Configuration');
        file_put_contents('vfs://Test/Configuration/PackageStates.php', "<?php return array ('packages' => array(), 'version' => 5); ");

        $composerNameToPackageKeyMap = [
            'typo3/flow' => 'TYPO3.Flow'
        ];

        $this->packageManager->injectCoreCache($mockCache);
        $this->packageManager->_set('composerNameToPackageKeyMap', $composerNameToPackageKeyMap);
        $this->packageManager->_set('packagesBasePath', 'vfs://Test/Packages/');
        $this->packageManager->_set('packageStatesPathAndFilename', 'vfs://Test/Configuration/PackageStates.php');
    }

    /**
     * @param string $packageKey
     * @return Package
     * @throws InvalidPackageStateException
     */
    protected function createPackage(string $packageKey): Package
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
     * @throws UnknownPackageException
     * @throws InvalidPackageStateException
     */
    public function getPackageReturnsTheSpecifiedPackage(): void
    {
        $this->createPackage('TYPO3.MyPackage');
        $package = $this->packageManager->getPackage('TYPO3.MyPackage');

        self::assertInstanceOf(Package::class, $package, 'The result of getPackage() was no valid package object.');
    }

    /**
     * @test
     * @throws UnknownPackageException
     */
    public function getPackageThrowsExceptionOnUnknownPackage(): void
    {
        $this->expectException(UnknownPackageException::class);
        $this->expectExceptionCode(1166546734);

        $this->packageManager->getPackage('PrettyUnlikelyThatThisPackageExists');
    }

    /**
     * @test
     */
    public function scanAvailablePackagesTraversesThePackagesDirectoryAndRegistersPackagesItFinds(): void
    {
        $expectedPackageKeys = [
            StringUtility::getUniqueId('TYPO3.CMS'),
            StringUtility::getUniqueId('TYPO3.CMS.Test'),
            StringUtility::getUniqueId('TYPO3.YetAnotherTestPackage'),
            StringUtility::getUniqueId('Lolli.Pop.NothingElse')
        ];

        foreach ($expectedPackageKeys as $packageKey) {
            $packagePath = 'vfs://Test/Packages/Application/' . $packageKey . '/';

            mkdir($packagePath, 0770, true);
            file_put_contents($packagePath . 'composer.json', '{"name": "' . $packageKey . '", "type": "typo3-test"}');
        }

        $packageManager = $this->getAccessibleMock(PackageManager::class, ['sortAndSavePackageStates'], [new DependencyOrderingService()]);
        $packageManager->_set('packagesBasePath', 'vfs://Test/Packages/');
        $packageManager->_set('packageStatesPathAndFilename', 'vfs://Test/Configuration/PackageStates.php');

        $packageManager->_set('packages', []);
        $packageManager->_call('scanAvailablePackages');

        $packageStates = require 'vfs://Test/Configuration/PackageStates.php';
        $actualPackageKeys = array_keys($packageStates['packages']);
        self::assertEquals(sort($expectedPackageKeys), sort($actualPackageKeys));
    }

    /**
     * @test
     */
    public function scanAvailablePackagesKeepsExistingPackageConfiguration(): void
    {
        $expectedPackageKeys = [
            StringUtility::getUniqueId('TYPO3.CMS'),
            StringUtility::getUniqueId('TYPO3.CMS.Test'),
            StringUtility::getUniqueId('TYPO3.YetAnotherTestPackage'),
            StringUtility::getUniqueId('Lolli.Pop.NothingElse')
        ];

        $packagePaths = [];
        foreach ($expectedPackageKeys as $packageKey) {
            $packagePath = 'vfs://Test/Packages/Application/' . $packageKey . '/';
            $packagePaths[] = $packagePath;

            mkdir($packagePath, 0770, true);
            file_put_contents($packagePath . 'composer.json', '{"name": "' . $packageKey . '", "type": "typo3-cms-test"}');
            file_put_contents($packagePath . 'ext_emconf.php', '<?php' . LF . '$EM_CONF[$_EXTKEY] = [];');
        }

        /** @var PackageManager|\PHPUnit\Framework\MockObject\MockObject|\TYPO3\TestingFramework\Core\AccessibleObjectInterface $packageManager */
        $packageManager = $this->getAccessibleMock(PackageManager::class, ['dummy'], [new DependencyOrderingService()]);
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
        self::assertEquals('Application/' . $packageKey . '/', $packageStates['packages'][$packageKey]['packagePath']);
    }

    /**
     * @test
     */
    public function packageStatesConfigurationContainsRelativePaths(): void
    {
        $packageKeys = [
            StringUtility::getUniqueId('Lolli.Pop.NothingElse'),
            StringUtility::getUniqueId('TYPO3.Package'),
            StringUtility::getUniqueId('TYPO3.YetAnotherTestPackage')
        ];

        $packagePaths = [];
        foreach ($packageKeys as $packageKey) {
            $packagePath = 'vfs://Test/Packages/Application/' . $packageKey . '/';

            mkdir($packagePath, 0770, true);
            file_put_contents($packagePath . 'composer.json', '{"name": "' . $packageKey . '", "type": "typo3-cms-test"}');
            file_put_contents($packagePath . 'ext_emconf.php', '<?php' . LF . '$EM_CONF[$_EXTKEY] = [];');
            $packagePaths[] = $packagePath;
        }

        /** @var PackageManager|\PHPUnit\Framework\MockObject\MockObject|\TYPO3\TestingFramework\Core\AccessibleObjectInterface $packageManager */
        $packageManager = $this->getAccessibleMock(PackageManager::class, ['sortAndSavePackageStates', 'registerTransientClassLoadingInformationForPackage'], [new DependencyOrderingService()]);
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
        self::assertEquals($expectedPackageStatesConfiguration, $actualPackageStatesConfiguration['packages']);
    }

    /**
     * Data Provider returning valid package keys and the corresponding path
     *
     * @return array
     */
    public function packageKeysAndPaths(): array
    {
        return [
            ['TYPO3.YetAnotherTestPackage', 'vfs://Test/Packages/Application/TYPO3.YetAnotherTestPackage/'],
            ['Lolli.Pop.NothingElse', 'vfs://Test/Packages/Application/Lolli.Pop.NothingElse/']
        ];
    }

    /**
     * @test
     * @dataProvider packageKeysAndPaths
     * @throws InvalidPackageStateException
     */
    public function createPackageCreatesPackageFolderAndReturnsPackage($packageKey, $expectedPackagePath): void
    {
        $actualPackage = $this->createPackage($packageKey);
        $actualPackagePath = $actualPackage->getPackagePath();

        self::assertEquals($expectedPackagePath, $actualPackagePath);
        self::assertTrue(is_dir($actualPackagePath), 'Package path should exist after createPackage()');
        self::assertEquals($packageKey, $actualPackage->getPackageKey());
        self::assertTrue($this->packageManager->isPackageAvailable($packageKey));
    }

    /**
     * @test
     * @throws InvalidPackageStateException
     * @throws ProtectedPackageKeyException
     * @throws UnknownPackageException
     * @throws PackageStatesFileNotWritableException
     */
    public function activatePackageAndDeactivatePackageActivateAndDeactivateTheGivenPackage(): void
    {
        $packageKey = 'Acme.YetAnotherTestPackage';

        $this->createPackage($packageKey);

        $this->packageManager->expects(self::any())->method('sortActivePackagesByDependencies')->willReturn([]);

        $this->packageManager->deactivatePackage($packageKey);
        self::assertFalse($this->packageManager->isPackageActive($packageKey));

        $this->packageManager->activatePackage($packageKey);
        self::assertTrue($this->packageManager->isPackageActive($packageKey));
    }

    /**
     * @test
     * @throws InvalidPackageStateException
     * @throws PackageStatesFileNotWritableException
     * @throws ProtectedPackageKeyException
     * @throws UnknownPackageException
     */
    public function deactivatePackageThrowsAnExceptionIfPackageIsProtected(): void
    {
        $this->expectException(ProtectedPackageKeyException::class);
        $this->expectExceptionCode(1308662891);

        $package = $this->createPackage('Acme.YetAnotherTestPackage');
        $package->setProtected(true);
        $this->packageManager->expects(self::any())->method('sortActivePackagesByDependencies')->willReturn([]);
        $this->packageManager->deactivatePackage('Acme.YetAnotherTestPackage');
    }

    /**
     * @test
     * @throws ProtectedPackageKeyException
     * @throws UnknownPackageException
     * @throws \TYPO3\CMS\Core\Package\Exception
     */
    public function deletePackageThrowsErrorIfPackageIsNotAvailable(): void
    {
        $this->expectException(UnknownPackageException::class);
        $this->expectExceptionCode(1166543253);

        $this->packageManager->expects(self::any())->method('sortActivePackagesByDependencies')->willReturn([]);
        $this->packageManager->deletePackage('PrettyUnlikelyThatThisPackageExists');
    }

    /**
     * @test
     * @throws InvalidPackageStateException
     * @throws ProtectedPackageKeyException
     * @throws UnknownPackageException
     * @throws \TYPO3\CMS\Core\Package\Exception
     */
    public function deletePackageThrowsAnExceptionIfPackageIsProtected(): void
    {
        $this->expectException(ProtectedPackageKeyException::class);
        $this->expectExceptionCode(1220722120);

        $package = $this->createPackage('Acme.YetAnotherTestPackage');
        $package->setProtected(true);
        $this->packageManager->deletePackage('Acme.YetAnotherTestPackage');
    }

    /**
     * @test
     * @throws InvalidPackageStateException
     * @throws ProtectedPackageKeyException
     * @throws UnknownPackageException
     * @throws \TYPO3\CMS\Core\Package\Exception
     */
    public function deletePackageRemovesPackageFromAvailableAndActivePackagesAndDeletesThePackageDirectory(): void
    {
        $this->createPackage('Acme.YetAnotherTestPackage');

        $this->packageManager->expects(self::any())->method('sortActivePackagesByDependencies')->willReturn([]);

        self::assertTrue($this->packageManager->isPackageActive('Acme.YetAnotherTestPackage'));
        self::assertTrue($this->packageManager->isPackageAvailable('Acme.YetAnotherTestPackage'));

        $this->packageManager->deletePackage('Acme.YetAnotherTestPackage');

        self::assertFalse($this->packageManager->isPackageActive('Acme.YetAnotherTestPackage'));
        self::assertFalse($this->packageManager->isPackageAvailable('Acme.YetAnotherTestPackage'));
    }

    /**
     * @return array
     */
    public function composerNamesAndPackageKeys(): array
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
     * @param string $composerName
     * @param string $packageKey
     */
    public function getPackageKeyFromComposerNameIgnoresCaseDifferences(string $composerName, string $packageKey): void
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

        $packageManager = $this->getAccessibleMock(PackageManager::class, ['resolvePackageDependencies'], [new DependencyOrderingService()]);
        $packageManager->_set('packageStatesConfiguration', $packageStatesConfiguration);
        $packageManager->_set('composerNameToPackageKeyMap', $composerNameToPackageKeyMap);

        self::assertEquals($packageKey, $packageManager->_call('getPackageKeyFromComposerName', $composerName));
    }

    /**
     * @return array
     */
    public function buildDependencyGraphBuildsCorrectGraphDataProvider(): array
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
    public function buildDependencyGraphBuildsCorrectGraph(array $unsortedPackageStatesConfiguration, array $frameworkPackageKeys, array $expectedGraph): void
    {
        $packageManager = $this->getAccessibleMock(PackageManager::class, ['findFrameworkPackages'], [new DependencyOrderingService()]);
        $packageManager->expects(self::any())->method('findFrameworkPackages')->willReturn($frameworkPackageKeys);

        $dependencyGraph = $packageManager->_call('buildDependencyGraph', $unsortedPackageStatesConfiguration);

        self::assertEquals($expectedGraph, $dependencyGraph);
    }

    /**
     * @return array
     */
    public function packageSortingDataProvider(): array
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
    public function sortPackageStatesConfigurationByDependencyMakesSureThatDependantPackagesAreStandingBeforeAPackageInTheInternalPackagesAndPackagesConfigurationArrays($unsortedPackageStatesConfiguration, $frameworkPackageKeys, $expectedSortedPackageKeys): void
    {
        $packageManager = $this->getAccessibleMock(PackageManager::class, ['findFrameworkPackages'], [new DependencyOrderingService()]);
        $packageManager->expects(self::any())->method('findFrameworkPackages')->willReturn($frameworkPackageKeys);

        $sortedPackageKeys = $packageManager->_call('sortPackageStatesConfigurationByDependency', $unsortedPackageStatesConfiguration);

        self::assertEquals($expectedSortedPackageKeys, $sortedPackageKeys, 'The package states configurations have not been ordered according to their dependencies!');
    }

    /**
     * @test
     */
    public function sortPackageStatesConfigurationByDependencyThrowsExceptionWhenCycleDetected(): void
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

        $packageManager = $this->getAccessibleMock(PackageManager::class, ['findFrameworkPackages'], [new DependencyOrderingService()]);
        $packageManager->expects(self::any())->method('findFrameworkPackages')->willReturn([]);

        $packageManager->_call('sortPackageStatesConfigurationByDependency', $unsortedPackageStatesConfiguration);
    }
}
