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

namespace TYPO3\CMS\Core\Tests\Functional\Page;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Crypto\HashService;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Page\ImportMap;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\ConsumableNonce;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class ImportMapTest extends FunctionalTestCase
{
    protected bool $initializeDatabase = false;

    protected array $testExtensionsToLoad = [
        'typo3/sysext/core/Tests/Functional/Page/Fixtures/ImportMap/test_importmap_core',
        'typo3/sysext/core/Tests/Functional/Page/Fixtures/ImportMap/test_importmap_package1',
        'typo3/sysext/core/Tests/Functional/Page/Fixtures/ImportMap/test_importmap_package2',
        'typo3/sysext/core/Tests/Functional/Page/Fixtures/ImportMap/test_importmap_package3',
        'typo3/sysext/core/Tests/Functional/Page/Fixtures/ImportMap/test_importmap_package4',
    ];

    #[Test]
    public function emptyOutputIfNoModuleIsLoaded(): void
    {
        $packages = ['test_importmap_core'];

        $importMap = new ImportMap(new HashService(), $this->getPackages($packages));
        $output = $importMap->render('/', new ConsumableNonce());

        self::assertSame('', $output);
    }

    #[Test]
    public function emptyOutputIfNoModuleIsDefined(): void
    {
        $packages = ['test_importmap_package1'];

        $importMap = new ImportMap(new HashService(), $this->getPackages($packages));
        $url = $importMap->resolveImport('lit');
        $output = $importMap->render('/', new ConsumableNonce());

        self::assertSame('', $output);
        self::assertNull($url);
    }

    #[Test]
    public function resolveImport(): void
    {
        $packages = ['test_importmap_core'];

        $importMap = new ImportMap(new HashService(), $this->getPackages($packages));
        $url = $importMap->resolveImport('lit');

        self::assertStringStartsWith('typo3conf/ext/test_importmap_core/Resources/Public/JavaScript/Contrib/lit/index.js?bust=', $url);
    }

    #[Test]
    public function resolveNestedUrlImportWithBustSuffix(): void
    {
        $packages = ['test_importmap_core'];
        $importMap = new ImportMap(new HashService(), $this->getPackages($packages));
        $nestedUrl = $importMap->resolveImport('@typo3/core/nested/module.js');

        self::assertStringStartsWith('typo3conf/ext/test_importmap_core/Resources/Public/JavaScript/nested/module.js?bust=', $nestedUrl);
    }

    #[Test]
    public function resolveNestedUrlImportWithoutBustSuffix(): void
    {
        $packages = ['test_importmap_core'];

        $importMap = new ImportMap(new HashService(), $this->getPackages($packages), null, null, '', null, false);
        $nestedUrl = $importMap->resolveImport('@typo3/core/nested/module.js');

        self::assertEquals('typo3conf/ext/test_importmap_core/Resources/Public/JavaScript/nested/module.js', $nestedUrl);
    }

    #[Test]
    public function resolveAndImplicitlyIncludeModuleConfiguration(): void
    {
        $packages = ['test_importmap_core'];

        $importMap = new ImportMap(new HashService(), $this->getPackages($packages));
        $url = $importMap->resolveImport('lit');
        $output = $importMap->render('/', new ConsumableNonce());

        self::assertStringStartsWith('typo3conf/ext/test_importmap_core/Resources/Public/JavaScript/Contrib/lit/index.js?bust=', $url);
        self::assertStringContainsString('"lit/":"/typo3conf/ext/test_importmap_core/Resources/Public/JavaScript/Contrib/lit/"', $output);
        self::assertStringContainsString('"@typo3/core/Module1.js":"/typo3conf/ext/test_importmap_core/Resources/Public/JavaScript/Module1.js?bust=', $output);
        ExtensionManagementUtility::setPackageManager($this->createMock(PackageManager::class));
    }

    #[Test]
    public function renderIncludedImportConfiguration(): void
    {
        $packages = ['test_importmap_core'];

        $importMap = new ImportMap(new HashService(), $this->getPackages($packages));
        $importMap->includeImportsFor('@typo3/core/Module1.js');
        $output = $importMap->render('/', new ConsumableNonce());

        self::assertStringContainsString('"@typo3/core/":"/typo3conf/ext/test_importmap_core/Resources/Public/JavaScript/', $output);
        self::assertStringContainsString('"@typo3/core/Module1.js":"/typo3conf/ext/test_importmap_core/Resources/Public/JavaScript/Module1.js?bust=', $output);
    }

    #[Test]
    public function composesTwoImportMaps(): void
    {
        $packages = ['test_importmap_core', 'test_importmap_package2'];

        $importMap = new ImportMap(new HashService(), $this->getPackages($packages));
        $importMap->includeImportsFor('@typo3/core/Module1.js');
        $output = $importMap->render('/', new ConsumableNonce());

        self::assertStringContainsString('"@typo3/core/":"/typo3conf/ext/test_importmap_core/Resources/Public/JavaScript/', $output);
        self::assertStringContainsString('"@typo3/core/Module1.js":"/typo3conf/ext/test_importmap_core/Resources/Public/JavaScript/Module1.js?bust=', $output);
        ExtensionManagementUtility::setPackageManager($this->createMock(PackageManager::class));
    }

    #[Test]
    public function handlesImportMapOverwrites(): void
    {
        $packages = ['test_importmap_package2', 'test_importmap_package3'];

        $importMap = new ImportMap(new HashService(), $this->getPackages($packages));
        $importMap->includeImportsFor('@typo3/package2/File.js');
        $output = $importMap->render('/', new ConsumableNonce());

        self::assertStringContainsString('"@typo3/package2/File.js":"/typo3conf/ext/test_importmap_package3/Resources/Public/JavaScript/Overrides/Package2/File.js?bust=', $output);
        self::assertThat(
            $output,
            self::logicalNot(
                self::stringContains('"@typo3/package2/File.js":"/typo3conf/ext/test_importmap_package2/')
            )
        );
    }

    #[Test]
    public function dependenciesAreLoaded(): void
    {
        $packages = ['test_importmap_core', 'test_importmap_package2'];
        $importMap = new ImportMap(new HashService(), $this->getPackages($packages));
        $importMap->includeImportsFor('@typo3/package2/file.js');
        $output = $importMap->render('/', new ConsumableNonce());

        self::assertStringContainsString('@typo3/core/', $output);
    }

    #[Test]
    public function unusedConfigurationsAreOmitted(): void
    {
        $packages = ['test_importmap_core', 'test_importmap_package2', 'test_importmap_package4'];
        $importMap = new ImportMap(new HashService(), $this->getPackages($packages));
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
        $packages = ['test_importmap_core', 'test_importmap_package2', 'test_importmap_package4'];
        $importMap = new ImportMap(new HashService(), $this->getPackages($packages));
        $importMap->includeAllImports();
        $importMap->includeImportsFor('@typo3/package2/file.js');
        $output = $importMap->render('/', new ConsumableNonce());

        self::assertStringContainsString('@typo3/core/', $output);
        self::assertStringContainsString('@acme/package4/Backend/Helper.js', $output);
    }

    protected function getPackages(array $packages): array
    {
        $packageInstances = [];
        $packageManager = $this->get(PackageManager::class);
        foreach ($packages as $key) {
            $package = $packageManager->getPackage($key);
            $packageInstances[$key] = $package;
        }

        return $packageInstances;
    }
}
