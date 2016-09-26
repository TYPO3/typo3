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
use TYPO3\CMS\Core\Package\DependencyResolver;
use TYPO3\CMS\Core\Package\Package;
use TYPO3\CMS\Core\Package\PackageManager;

/**
 * Testcase for the default package manager
 *
 */
class PackageManagerTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @var PackageManager
     */
    protected $packageManager;

    /**
     * Sets up this test case
     *
     */
    protected function setUp()
    {
        vfsStream::setup('Test');

        /** @var PhpFrontend|\PHPUnit_Framework_MockObject_MockObject $mockCache */
        $mockCache = $this->getMock(PhpFrontend::class, ['has', 'set', 'getBackend'], [], '', false);
        $mockCacheBackend = $this->getMock(SimpleFileBackend::class, ['has', 'set', 'getBackend'], [], '', false);
        $mockCache->expects($this->any())->method('has')->will($this->returnValue(false));
        $mockCache->expects($this->any())->method('set')->will($this->returnValue(true));
        $mockCache->expects($this->any())->method('getBackend')->will($this->returnValue($mockCacheBackend));
        $mockCacheBackend->expects($this->any())->method('getCacheDirectory')->will($this->returnValue('vfs://Test/Cache'));
        $this->packageManager = $this->getAccessibleMock(PackageManager::class, ['sortAndSavePackageStates', 'sortAvailablePackagesByDependencies', 'registerTransientClassLoadingInformationForPackage']);

        mkdir('vfs://Test/Packages/Application', 0700, true);
        mkdir('vfs://Test/Configuration');
        file_put_contents('vfs://Test/Configuration/PackageStates.php', "<?php return array ('packages' => array(), 'version' => 4); ");

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
     * @expectedException \TYPO3\CMS\Core\Package\Exception\UnknownPackageException
     */
    public function getPackageThrowsExceptionOnUnknownPackage()
    {
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

        $packageManager = $this->getAccessibleMock(PackageManager::class, ['sortAndSavePackageStates']);
        $packageManager->_set('packagesBasePath', 'vfs://Test/Packages/');
        $packageManager->_set('packageStatesPathAndFilename', 'vfs://Test/Configuration/PackageStates.php');

        $packageManager->_set('packages', []);
        $packageManager->_call('scanAvailablePackages');

        $packageStates = require('vfs://Test/Configuration/PackageStates.php');
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

        foreach ($expectedPackageKeys as $packageKey) {
            $packagePath = 'vfs://Test/Packages/Application/' . $packageKey . '/';

            mkdir($packagePath, 0770, true);
            file_put_contents($packagePath . 'composer.json', '{"name": "' . $packageKey . '", "type": "typo3-cms-test"}');
            file_put_contents($packagePath . 'ext_emconf.php', '<?php' . LF . '$EM_CONF[$_EXTKEY] = [];');
        }

        /** @var PackageManager|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface $packageManager */
        $packageManager = $this->getAccessibleMock(PackageManager::class, ['dummy']);
        $packageManager->_set('packagesBasePaths', ['local' => 'vfs://Test/Packages/Application']);
        $packageManager->_set('packagesBasePath', 'vfs://Test/Packages/');
        $packageManager->_set('packageStatesPathAndFilename', 'vfs://Test/Configuration/PackageStates.php');

        /** @var DependencyResolver|\PHPUnit_Framework_MockObject_MockObject $dependencyResolver */
        $dependencyResolver = $this->getMock(DependencyResolver::class);
        $dependencyResolver
            ->expects($this->any())
            ->method('sortPackageStatesConfigurationByDependency')
            ->willReturnArgument(0);

        $packageManager->injectDependencyResolver($dependencyResolver);

        $packageKey = $expectedPackageKeys[0];
        $packageManager->_set('packageStatesConfiguration', [
            'packages' => [
                $packageKey => [
                    'state' => 'inactive',
                    'packagePath' => 'Application/' . $packageKey . '/'
                ]
            ],
            'version' => 4
        ]);
        $packageManager->_call('scanAvailablePackages');
        $packageManager->_call('sortAndSavePackageStates');

        $packageStates = require('vfs://Test/Configuration/PackageStates.php');
        $this->assertEquals('inactive', $packageStates['packages'][$packageKey]['state']);
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

        foreach ($packageKeys as $packageKey) {
            $packagePath = 'vfs://Test/Packages/Application/' . $packageKey . '/';

            mkdir($packagePath, 0770, true);
            file_put_contents($packagePath . 'composer.json', '{"name": "' . $packageKey . '", "type": "typo3-cms-test"}');
            file_put_contents($packagePath . 'ext_emconf.php', '<?php' . LF . '$EM_CONF[$_EXTKEY] = [];');
        }

        /** @var PackageManager|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface $packageManager */
        $packageManager = $this->getAccessibleMock(PackageManager::class, ['dummy']);
        $packageManager->_set('packagesBasePaths', ['local' => 'vfs://Test/Packages/Application']);
        $packageManager->_set('packagesBasePath', 'vfs://Test/Packages/');
        $packageManager->_set('packageStatesPathAndFilename', 'vfs://Test/Configuration/PackageStates.php');

        /** @var DependencyResolver|\PHPUnit_Framework_MockObject_MockObject $dependencyResolver */
        $dependencyResolver = $this->getMock(DependencyResolver::class);
        $dependencyResolver
            ->expects($this->any())
            ->method('sortPackageStatesConfigurationByDependency')
            ->willReturnArgument(0);

        $packageManager->injectDependencyResolver($dependencyResolver);

        $packageManager->_set('packages', []);
        $packageManager->_call('scanAvailablePackages');

        $expectedPackageStatesConfiguration = [];
        foreach ($packageKeys as $packageKey) {
            $expectedPackageStatesConfiguration[$packageKey] = [
                'state' => 'inactive',
                'packagePath' => 'Application/' . $packageKey . '/',
                'composerName' => $packageKey,
                'suggestions' => [],
            ];
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

        $this->packageManager->deactivatePackage($packageKey);
        $this->assertFalse($this->packageManager->isPackageActive($packageKey));

        $this->packageManager->activatePackage($packageKey);
        $this->assertTrue($this->packageManager->isPackageActive($packageKey));
    }

    /**
     * @test
     * @expectedException \TYPO3\CMS\Core\Package\Exception\ProtectedPackageKeyException
     */
    public function deactivatePackageThrowsAnExceptionIfPackageIsProtected()
    {
        $package = $this->createPackage('Acme.YetAnotherTestPackage');
        $package->setProtected(true);
        $this->packageManager->deactivatePackage('Acme.YetAnotherTestPackage');
    }

    /**
     * @test
     * @expectedException \TYPO3\CMS\Core\Package\Exception\UnknownPackageException
     */
    public function deletePackageThrowsErrorIfPackageIsNotAvailable()
    {
        $this->packageManager->deletePackage('PrettyUnlikelyThatThisPackageExists');
    }

    /**
     * @test
     * @expectedException \TYPO3\CMS\Core\Package\Exception\ProtectedPackageKeyException
     */
    public function deletePackageThrowsAnExceptionIfPackageIsProtected()
    {
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
        $packageStatesConfiguration = ['packages' =>
            [
                'TYPO3.CMS' => [
                    'composerName' => 'typo3/cms'
                ],
                'imagine.Imagine' => [
                    'composerName' => 'imagine/Imagine'
                ]
            ]
        ];

        $packageManager = $this->getAccessibleMock(PackageManager::class, ['resolvePackageDependencies']);
        $packageManager->_set('packageStatesConfiguration', $packageStatesConfiguration);

        $this->assertEquals($packageKey, $packageManager->_call('getPackageKeyFromComposerName', $composerName));
    }
}
