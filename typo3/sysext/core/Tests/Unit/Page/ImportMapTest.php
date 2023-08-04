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

namespace TYPO3\CMS\Core\Tests\Unit\Page;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Crypto\HashService;
use TYPO3\CMS\Core\Package\MetaData;
use TYPO3\CMS\Core\Package\PackageInterface;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Page\ImportMap;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\ConsumableNonce;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class ImportMapTest extends UnitTestCase
{
    protected array $packages = [];

    protected bool $backupEnvironment = true;

    protected ?PackageManager $backupPackageManager = null;
    protected HashService $hashService;

    protected function setUp(): void
    {
        parent::setUp();

        Environment::initialize(
            Environment::getContext(),
            Environment::isCli(),
            Environment::isComposerMode(),
            Environment::getProjectPath(),
            __DIR__,
            Environment::getVarPath(),
            Environment::getConfigPath(),
            Environment::getCurrentScript(),
            Environment::isWindows() ? 'WINDOWS' : 'UNIX'
        );
        $this->backupPackageManager = \Closure::bind(fn(): PackageManager => ExtensionManagementUtility::$packageManager, null, ExtensionManagementUtility::class)();
        $this->hashService = new HashService();
        ExtensionManagementUtility::setPackageManager($this->mockPackageManager());
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'] = '';
    }

    protected function tearDown(): void
    {
        ExtensionManagementUtility::setPackageManager($this->backupPackageManager);
        $this->backupPackageManager = null;
        $this->packages = [];
        parent::tearDown();
    }

    #[Test]
    public function emptyOutputIfNoModuleIsLoaded(): void
    {
        $this->packages = ['core'];

        $importMap = new ImportMap($this->hashService, $this->getPackages());
        $output = $importMap->render('/', new ConsumableNonce());

        self::assertSame('', $output);
    }

    #[Test]
    public function emptyOutputIfNoModuleIsDefined(): void
    {
        $this->packages = ['package1'];

        $importMap = new ImportMap($this->hashService, $this->getPackages());
        $url = $importMap->resolveImport('lit');
        $output = $importMap->render('/', new ConsumableNonce());

        self::assertSame('', $output);
        self::assertNull($url);
    }

    #[Test]
    public function resolveImport(): void
    {
        $this->packages = ['core'];

        $importMap = new ImportMap($this->hashService, $this->getPackages());
        $url = $importMap->resolveImport('lit');

        self::assertStringStartsWith('Fixtures/ImportMap/core/Resources/Public/JavaScript/Contrib/lit/index.js?bust=', $url);
    }

    #[Test]
    public function resolveNestedUrlImportWithBustSuffix(): void
    {
        $this->packages = ['core'];
        $importMap = new ImportMap($this->hashService, $this->getPackages());
        $nestedUrl = $importMap->resolveImport('@typo3/core/nested/module.js');

        self::assertStringStartsWith('Fixtures/ImportMap/core/Resources/Public/JavaScript/nested/module.js?bust=', $nestedUrl);
    }

    #[Test]
    public function resolveNestedUrlImportWithoutBustSuffix(): void
    {
        $this->packages = ['core'];

        $importMap = new ImportMap($this->hashService, $this->getPackages(), null, '', null, false);
        $nestedUrl = $importMap->resolveImport('@typo3/core/nested/module.js');

        self::assertEquals('Fixtures/ImportMap/core/Resources/Public/JavaScript/nested/module.js', $nestedUrl);
    }

    #[Test]
    public function resolveAndImplicitlyIncludeModuleConfiguration(): void
    {
        $this->packages = ['core'];

        $importMap = new ImportMap($this->hashService, $this->getPackages());
        $url = $importMap->resolveImport('lit');
        $output = $importMap->render('/', new ConsumableNonce());

        self::assertStringStartsWith('Fixtures/ImportMap/core/Resources/Public/JavaScript/Contrib/lit/index.js?bust=', $url);
        self::assertStringContainsString('"lit/":"/Fixtures/ImportMap/core/Resources/Public/JavaScript/Contrib/lit/"', $output);
        self::assertStringContainsString('"@typo3/core/Module1.js":"/Fixtures/ImportMap/core/Resources/Public/JavaScript/Module1.js?bust=', $output);
        ExtensionManagementUtility::setPackageManager($this->createMock(PackageManager::class));
    }

    #[Test]
    public function renderIncludedImportConfiguration(): void
    {
        $this->packages = ['core'];

        $importMap = new ImportMap($this->hashService, $this->getPackages());
        $importMap->includeImportsFor('@typo3/core/Module1.js');
        $output = $importMap->render('/', new ConsumableNonce());

        self::assertStringContainsString('"@typo3/core/":"/Fixtures/ImportMap/core/Resources/Public/JavaScript/', $output);
        self::assertStringContainsString('"@typo3/core/Module1.js":"/Fixtures/ImportMap/core/Resources/Public/JavaScript/Module1.js?bust=', $output);
    }

    #[Test]
    public function composesTwoImportMaps(): void
    {
        $this->packages = ['core', 'package2'];

        $importMap = new ImportMap($this->hashService, $this->getPackages());
        $importMap->includeImportsFor('@typo3/core/Module1.js');
        $output = $importMap->render('/', new ConsumableNonce());

        self::assertStringContainsString('"@typo3/core/":"/Fixtures/ImportMap/core/Resources/Public/JavaScript/', $output);
        self::assertStringContainsString('"@typo3/core/Module1.js":"/Fixtures/ImportMap/core/Resources/Public/JavaScript/Module1.js?bust=', $output);
        ExtensionManagementUtility::setPackageManager($this->createMock(PackageManager::class));
    }

    #[Test]
    public function handlesImportMapOverwrites(): void
    {
        $this->packages = ['package2', 'package3'];

        $importMap = new ImportMap($this->hashService, $this->getPackages());
        $importMap->includeImportsFor('@typo3/package2/File.js');
        $output = $importMap->render('/', new ConsumableNonce());

        self::assertStringContainsString('"@typo3/package2/File.js":"/Fixtures/ImportMap/package3/Resources/Public/JavaScript/Overrides/Package2/File.js?bust=', $output);
        self::assertThat(
            $output,
            self::logicalNot(
                self::stringContains('"@typo3/package2/File.js":"/Fixtures/ImportMap/package2/')
            )
        );
    }

    #[Test]
    public function dependenciesAreLoaded(): void
    {
        $this->packages = ['core', 'package2'];
        $importMap = new ImportMap($this->hashService, $this->getPackages());
        $importMap->includeImportsFor('@typo3/package2/file.js');
        $output = $importMap->render('/', new ConsumableNonce());

        self::assertStringContainsString('@typo3/core/', $output);
    }

    #[Test]
    public function unusedConfigurationsAreOmitted(): void
    {
        $this->packages = ['core', 'package2', 'package4'];
        $importMap = new ImportMap($this->hashService, $this->getPackages());
        $importMap->includeImportsFor('@typo3/package2/file.js');
        $output = $importMap->render('/', new ConsumableNonce());

        self::assertThat(
            $output,
            self::logicalNot(
                self::stringContains('@acme/package4/Backend/Helper.js')
            )
        );
    }

    #[Test]
    public function includeAllImportsIsRespected(): void
    {
        $this->packages = ['core', 'package2', 'package4'];
        $importMap = new ImportMap($this->hashService, $this->getPackages());
        $importMap->includeAllImports();
        $importMap->includeImportsFor('@typo3/package2/file.js');
        $output = $importMap->render('/', new ConsumableNonce());

        self::assertStringContainsString('@typo3/core/', $output);
        self::assertStringContainsString('@acme/package4/Backend/Helper.js', $output);
    }

    protected function getPackages(): array
    {
        $packageInstances = [];
        foreach ($this->packages as $key) {
            $packageMock = $this->createMock(PackageInterface::class);
            $packageMock->method('getPackagePath')->willReturn(__DIR__ . '/Fixtures/ImportMap/' . $key . '/');
            $packageMock->method('getPackageKey')->willReturn($key);
            $packageMetadataMock = $this->createMock(MetaData::class);
            $packageMetadataMock->method('getVersion')->willReturn('1.0.0');
            $packageMock->method('getPackageMetadata')->willReturn($packageMetadataMock);
            $packageInstances[$key] = $packageMock;
        }

        return $packageInstances;
    }

    protected function mockPackageManager(): PackageManager
    {
        $test = $this;
        $packageManagerMock = $this->createMock(PackageManager::class);
        $packageManagerMock->method('resolvePackagePath')->willReturnCallback(
            fn(string $path): string => str_replace(
                array_map(fn(PackageInterface $package): string => 'EXT:' . $package->getPackageKey() . '/', $test->getPackages()),
                array_map(fn(PackageInterface $package): string => $package->getPackagePath(), $test->getPackages()),
                $path
            )
        );
        return $packageManagerMock;
    }
}
