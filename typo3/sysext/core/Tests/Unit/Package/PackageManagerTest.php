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

use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\CMS\Core\Cache\Backend\SimpleFileBackend;
use TYPO3\CMS\Core\Cache\Frontend\PhpFrontend;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Package\Cache\PackageStatesPackageCache;
use TYPO3\CMS\Core\Package\Exception;
use TYPO3\CMS\Core\Package\Exception\InvalidPackageStateException;
use TYPO3\CMS\Core\Package\Exception\PackageStatesFileNotWritableException;
use TYPO3\CMS\Core\Package\Exception\ProtectedPackageKeyException;
use TYPO3\CMS\Core\Package\Exception\UnknownPackageException;
use TYPO3\CMS\Core\Package\Exception\UnknownPackagePathException;
use TYPO3\CMS\Core\Package\Package;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Service\DependencyOrderingService;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\TestingFramework\Core\AccessibleObjectInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Testcase for the default package manager
 */
class PackageManagerTest extends UnitTestCase
{
    /**
     * @var PackageManager|MockObject|AccessibleObjectInterface $packageManager
     */
    protected $packageManager;

    protected string $testRoot;

    /**
     * Sets up this test case
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->testRoot = Environment::getVarPath() . '/tests/PackageManager/';
        $this->testFilesToDelete[] = $this->testRoot;

        $mockCache = $this->getMockBuilder(PhpFrontend::class)
            ->onlyMethods(['has', 'set', 'getBackend'])
            ->disableOriginalConstructor()
            ->getMock();
        $mockCacheBackend = $this->getMockBuilder(SimpleFileBackend::class)
            ->onlyMethods(['has', 'set', 'getCacheDirectory'])
            ->addMethods(['getBackend'])
            ->disableOriginalConstructor()
            ->getMock();
        $mockCache->method('has')->willReturn(false);
        $mockCache->method('set')->willReturn(true);
        $mockCache->method('getBackend')->willReturn($mockCacheBackend);
        $mockCacheBackend->method('getCacheDirectory')->willReturn($this->testRoot . 'Cache');
        $this->packageManager = $this->getAccessibleMock(
            PackageManager::class,
            ['sortAndSavePackageStates', 'sortActivePackagesByDependencies', 'registerTransientClassLoadingInformationForPackage'],
            [new DependencyOrderingService()]
        );

        if (!is_dir($this->testRoot . 'Packages/Application')) {
            mkdir($this->testRoot . 'Packages/Application', 0700, true);
        }
        if (!is_dir($this->testRoot . 'Configuration')) {
            mkdir($this->testRoot . 'Configuration');
        }
        file_put_contents($this->testRoot . 'Configuration/PackageStates.php', "<?php return array ('packages' => array(), 'version' => 5); ");

        $composerNameToPackageKeyMap = [
            'typo3/flow' => 'TYPO3.Flow',
        ];

        $this->packageManager->setPackageCache(new PackageStatesPackageCache($this->testRoot . 'Configuration/PackageStates.php', $mockCache));
        $this->packageManager->_set('composerNameToPackageKeyMap', $composerNameToPackageKeyMap);
        $this->packageManager->_set('packagesBasePath', $this->testRoot . 'Packages/');
        $this->packageManager->_set('packageStatesPathAndFilename', $this->testRoot . 'Configuration/PackageStates.php');
    }

    protected function createPackage(string $packageKey): Package
    {
        $packagePath = $this->testRoot . 'Packages/Application/' . $packageKey . '/';
        if (!is_dir($packagePath)) {
            mkdir($packagePath, 0770, true);
        }
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
            StringUtility::getUniqueId('Lolli.Pop.NothingElse'),
        ];

        foreach ($expectedPackageKeys as $packageKey) {
            $packagePath = $this->testRoot . 'Packages/Application/' . $packageKey . '/';

            mkdir($packagePath, 0770, true);
            file_put_contents($packagePath . 'composer.json', '{"name": "' . $packageKey . '", "type": "typo3-test"}');
        }

        $packageManager = $this->getAccessibleMock(PackageManager::class, ['sortAndSavePackageStates'], [new DependencyOrderingService()]);
        $packageManager->_set('packagesBasePath', $this->testRoot . 'Packages/');
        $packageManager->_set('packageStatesPathAndFilename', $this->testRoot . 'Configuration/PackageStates.php');

        $packageManager->_set('packages', []);
        $packageManager->_call('scanAvailablePackages');

        $packageStates = require $this->testRoot . 'Configuration/PackageStates.php';
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
            StringUtility::getUniqueId('Lolli.Pop.NothingElse'),
        ];

        $packagePaths = [];
        foreach ($expectedPackageKeys as $packageKey) {
            $packagePath = $this->testRoot . 'Packages/Application/' . $packageKey . '/';
            $packagePaths[] = $packagePath;

            mkdir($packagePath, 0770, true);
            file_put_contents($packagePath . 'composer.json', '{"name": "' . $packageKey . '", "type": "typo3-cms-test"}');
            file_put_contents($packagePath . 'ext_emconf.php', '<?php' . LF . '$EM_CONF[$_EXTKEY] = [];');
        }

        $packageManager = $this->getAccessibleMock(PackageManager::class, ['dummy'], [new DependencyOrderingService()]);
        $packageManager->_set('packagesBasePaths', $packagePaths);
        $packageManager->_set('packagesBasePath', $this->testRoot . 'Packages/');
        $packageManager->_set('packageStatesPathAndFilename', $this->testRoot . 'Configuration/PackageStates.php');
        $mockCache = $this->getMockBuilder(PhpFrontend::class)->disableOriginalConstructor()->getMock();
        $packageManager->_set('packageCache', new PackageStatesPackageCache($this->testRoot . 'Configuration/PackageStates.php', $mockCache));

        $packageKey = $expectedPackageKeys[0];
        $packageManager->_set('packageStatesConfiguration', [
            'packages' => [
                $packageKey => [
                    'packagePath' => 'Application/' . $packageKey . '/',
                ],
            ],
            'version' => 5,
        ]);
        $packageManager->_call('scanAvailablePackages');
        $packageManager->_call('sortAndSavePackageStates');

        $packageStates = require $this->testRoot . 'Configuration/PackageStates.php';
        self::assertEquals('Application/' . $packageKey . '/', $packageStates['packages'][$packageKey]['packagePath']);
    }

    /**
     * @test
     */
    public function extractPackageKeyFromPackagePathFindsPackageKey(): void
    {
        $package = $this->createPackage('typo3/my-package');

        $resolvedPackage = $this->packageManager->extractPackageKeyFromPackagePath('EXT:typo3/my-package/path/to/file');

        self::assertSame('typo3/my-package', $resolvedPackage);
    }

    /**
     * @test
     * @throws UnknownPackagePathException
     */
    public function extractPackageKeyFromPackagePathThrowsExceptionOnNonPackagePaths(): void
    {
        $this->expectException(UnknownPackageException::class);
        $this->expectExceptionCode(1631630764);

        $this->packageManager->extractPackageKeyFromPackagePath($this->testRoot . 'Packages/Application/InvalidPackage/');
    }

    /**
     * @test
     * @throws UnknownPackageException
     */
    public function extractPackageKeyFromPackagePathThrowsExceptionOnInvalidPackagePaths(): void
    {
        $this->expectException(UnknownPackagePathException::class);
        $this->expectExceptionCode(1631630087);

        $this->packageManager->extractPackageKeyFromPackagePath('EXT:typo3/my-package/path/to/file');
    }

    /**
     * @test
     */
    public function packageStatesConfigurationContainsRelativePaths(): void
    {
        $packageKeys = [
            StringUtility::getUniqueId('Lolli.Pop.NothingElse'),
            StringUtility::getUniqueId('TYPO3.Package'),
            StringUtility::getUniqueId('TYPO3.YetAnotherTestPackage'),
        ];

        $packagePaths = [];
        foreach ($packageKeys as $packageKey) {
            $packagePath = $this->testRoot . 'Packages/Application/' . $packageKey . '/';

            mkdir($packagePath, 0770, true);
            file_put_contents($packagePath . 'composer.json', '{"name": "' . $packageKey . '", "type": "typo3-cms-test"}');
            file_put_contents($packagePath . 'ext_emconf.php', '<?php' . LF . '$EM_CONF[$_EXTKEY] = [];');
            $packagePaths[] = $packagePath;
        }

        $packageManager = $this->getAccessibleMock(PackageManager::class, ['sortAndSavePackageStates', 'registerTransientClassLoadingInformationForPackage'], [new DependencyOrderingService()]);
        $packageManager->_set('packagesBasePaths', $packagePaths);
        $packageManager->_set('packagesBasePath', $this->testRoot . 'Packages/');
        $packageManager->_set('packageStatesPathAndFilename', $this->testRoot . 'Configuration/PackageStates.php');

        $packageManager->_set('packages', []);
        $packageManager->_call('scanAvailablePackages');

        $expectedPackageStatesConfiguration = [];
        foreach ($packageKeys as $packageKey) {
            $expectedPackageStatesConfiguration[$packageKey] = [
                'packagePath' => 'Application/' . $packageKey . '/',
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
            ['TYPO3.YetAnotherTestPackage', 'Packages/Application/TYPO3.YetAnotherTestPackage/'],
            ['Lolli.Pop.NothingElse', 'Packages/Application/Lolli.Pop.NothingElse/'],
        ];
    }

    /**
     * @test
     * @dataProvider packageKeysAndPaths
     */
    public function createPackageCreatesPackageFolderAndReturnsPackage($packageKey, $expectedPackagePath): void
    {
        $actualPackage = $this->createPackage($packageKey);
        $actualPackagePath = $actualPackage->getPackagePath();

        self::assertEquals($this->testRoot . $expectedPackagePath, $actualPackagePath);
        self::assertDirectoryExists($actualPackagePath, 'Package path should exist after createPackage()');
        self::assertEquals($packageKey, $actualPackage->getPackageKey());
        self::assertTrue($this->packageManager->isPackageAvailable($packageKey));
    }

    /**
     * @test
     * @throws ProtectedPackageKeyException
     * @throws UnknownPackageException
     * @throws PackageStatesFileNotWritableException
     */
    public function activatePackageAndDeactivatePackageActivateAndDeactivateTheGivenPackage(): void
    {
        $packageKey = 'Acme.YetAnotherTestPackage';

        $this->createPackage($packageKey);

        $this->packageManager->method('sortActivePackagesByDependencies')->willReturn([]);

        $this->packageManager->deactivatePackage($packageKey);
        self::assertFalse($this->packageManager->isPackageActive($packageKey));

        $this->packageManager->activatePackage($packageKey);
        self::assertTrue($this->packageManager->isPackageActive($packageKey));
    }

    /**
     * @test
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
        $this->packageManager->method('sortActivePackagesByDependencies')->willReturn([]);
        $this->packageManager->deactivatePackage('Acme.YetAnotherTestPackage');
    }

    /**
     * @test
     * @throws ProtectedPackageKeyException
     * @throws UnknownPackageException
     * @throws Exception
     */
    public function deletePackageThrowsErrorIfPackageIsNotAvailable(): void
    {
        $this->expectException(UnknownPackageException::class);
        $this->expectExceptionCode(1166543253);

        $this->packageManager->method('sortActivePackagesByDependencies')->willReturn([]);
        $this->packageManager->deletePackage('PrettyUnlikelyThatThisPackageExists');
    }

    /**
     * @test
     * @throws InvalidPackageStateException
     * @throws ProtectedPackageKeyException
     * @throws UnknownPackageException
     * @throws Exception
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
     * @throws Exception
     */
    public function deletePackageRemovesPackageFromAvailableAndActivePackagesAndDeletesThePackageDirectory(): void
    {
        $this->createPackage('Acme.YetAnotherTestPackage');

        $this->packageManager->method('sortActivePackagesByDependencies')->willReturn([]);

        self::assertTrue($this->packageManager->isPackageActive('Acme.YetAnotherTestPackage'));
        self::assertTrue($this->packageManager->isPackageAvailable('Acme.YetAnotherTestPackage'));

        $this->packageManager->deletePackage('Acme.YetAnotherTestPackage');
        // unregister from test file removal, else error will be thrown
        unset($this->testFilesToDelete[ array_search($this->testRoot . 'Packages/Application/' . 'Acme.YetAnotherTestPackage', $this->testFilesToDelete)]);

        self::assertFalse($this->packageManager->isPackageActive('Acme.YetAnotherTestPackage/'));
        self::assertFalse($this->packageManager->isPackageAvailable('Acme.YetAnotherTestPackage'));
    }

    /**
     * @return array
     */
    public function buildDependencyGraphBuildsCorrectGraphDataProvider(): array
    {
        return [
            'TYPO3 CMS Extensions' => [
                [
                    'core' => [
                        'dependencies' => [],
                    ],
                    'setup' => [
                        'dependencies' => ['core'],
                    ],
                    'openid' => [
                        'dependencies' => ['core', 'setup'],
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
                    'extbase',
                ],
                [
                    'core' => [
                        'core' => false,
                        'setup' => false,
                        'openid' => false,
                        'news' => false,
                        'extbase' => false,
                        'pt_extbase' => false,
                        'foo' => false,
                    ],
                    'setup' => [
                        'core' => true,
                        'setup' => false,
                        'openid' => false,
                        'news' => false,
                        'extbase' => false,
                        'pt_extbase' => false,
                        'foo' => false,
                    ],
                    'openid' => [
                        'core' => true,
                        'setup' => true,
                        'openid' => false,
                        'news' => false,
                        'extbase' => false,
                        'pt_extbase' => false,
                        'foo' => false,
                    ],
                    'news' => [
                        'core' => false,
                        'setup' => false,
                        'openid' => true,
                        'news' => false,
                        'extbase' => true,
                        'pt_extbase' => false,
                        'foo' => false,
                    ],
                    'extbase' => [
                        'core' => true,
                        'setup' => false,
                        'openid' => false,
                        'news' => false,
                        'extbase' => false,
                        'pt_extbase' => false,
                        'foo' => false,
                    ],
                    'pt_extbase' => [
                        'core' => false,
                        'setup' => false,
                        'openid' => true,
                        'news' => false,
                        'extbase' => true,
                        'pt_extbase' => false,
                        'foo' => false,
                    ],
                    'foo' => [
                        'core' => false,
                        'setup' => false,
                        'openid' => true,
                        'news' => false,
                        'extbase' => true,
                        'pt_extbase' => false,
                        'foo' => false,
                    ],
                ],
            ],
            'Dummy Packages' => [
                [
                    'A' => [
                        'dependencies' => ['B', 'D', 'C'],
                    ],
                    'B' => [
                        'dependencies' => [],
                    ],
                    'C' => [
                        'dependencies' => ['E'],
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
                    'E',
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
     * @dataProvider buildDependencyGraphBuildsCorrectGraphDataProvider
     */
    public function buildDependencyGraphBuildsCorrectGraph(array $unsortedPackageStatesConfiguration, array $frameworkPackageKeys, array $expectedGraph): void
    {
        $packageManager = $this->getAccessibleMock(PackageManager::class, ['findFrameworkPackages'], [new DependencyOrderingService()]);
        $packageManager->method('findFrameworkPackages')->willReturn($frameworkPackageKeys);

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
                        'dependencies' => ['Symfony.Component.Yaml', 'Doctrine.Common', 'Doctrine.DBAL', 'Doctrine.ORM'],
                    ],
                    'Doctrine.ORM' => [
                        'dependencies' => ['Doctrine.Common', 'Doctrine.DBAL'],
                    ],
                    'Doctrine.Common' => [
                        'dependencies' => [],
                    ],
                    'Doctrine.DBAL' => [
                        'dependencies' => ['Doctrine.Common'],
                    ],
                    'Symfony.Component.Yaml' => [
                        'dependencies' => [],
                    ],
                ],
                [
                    'Doctrine.Common',
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
                        'dependencies' => ['core', 'setup'],
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
                    'extbase',
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
                        'dependencies' => [],
                    ],
                    'C' => [
                        'dependencies' => ['E'],
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
                    'E',
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
     */
    public function sortPackageStatesConfigurationByDependencyMakesSureThatDependantPackagesAreStandingBeforeAPackageInTheInternalPackagesAndPackagesConfigurationArrays(
        array $unsortedPackageStatesConfiguration,
        array $frameworkPackageKeys,
        array $expectedSortedPackageKeys
    ): void {
        $packageManager = $this->getAccessibleMock(PackageManager::class, ['findFrameworkPackages'], [new DependencyOrderingService()]);
        $packageManager->method('findFrameworkPackages')->willReturn($frameworkPackageKeys);

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
                'dependencies' => ['A'],
            ],
        ];

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionCode(1381960494);

        $packageManager = $this->getAccessibleMock(PackageManager::class, ['findFrameworkPackages'], [new DependencyOrderingService()]);
        $packageManager->method('findFrameworkPackages')->willReturn([]);

        $packageManager->_call('sortPackageStatesConfigurationByDependency', $unsortedPackageStatesConfiguration);
    }
}
