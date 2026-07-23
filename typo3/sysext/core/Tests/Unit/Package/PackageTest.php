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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Package\Exception\InvalidPackageKeyException;
use TYPO3\CMS\Core\Package\Exception\InvalidPackagePathException;
use TYPO3\CMS\Core\Package\Package;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Package\Stability;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Testcase for the package class
 */
final class PackageTest extends UnitTestCase
{
    private string $testRoot;

    protected function setUp(): void
    {
        parent::setUp();
        $this->testRoot = Environment::getVarPath() . '/tests/Packages/';
        GeneralUtility::mkdir_deep($this->testRoot);
        $this->testFilesToDelete[] = $this->testRoot;
    }

    #[Test]
    public function constructThrowsPackageDoesNotExistException(): void
    {
        $this->expectException(InvalidPackagePathException::class);
        $this->expectExceptionCode(1166631890);

        $packageManagerStub = self::createStub(PackageManager::class);
        $packageManagerStub->method('isPackageKeyValid')->willReturn(true);
        new Package($packageManagerStub, 'Vendor.TestPackage', './ThisPackageSurelyDoesNotExist');
    }

    public static function validPackageKeys(): array
    {
        return [
            ['Doctrine.DBAL'],
            ['TYPO3.CMS'],
            ['My.Own.TwitterPackage'],
            ['GoFor.IT'],
            ['Symfony.Symfony'],
        ];
    }

    #[DataProvider('validPackageKeys')]
    #[Test]
    public function constructAcceptsValidPackageKeys($packageKey): void
    {
        $packagePath = $this->testRoot . str_replace('\\', '/', $packageKey) . '/';
        mkdir($packagePath, 0777, true);
        file_put_contents($packagePath . 'composer.json', '{"name": "' . $packageKey . '", "type": "flow-test"}');

        $packageManagerStub = self::createStub(PackageManager::class);
        $packageManagerStub->method('isPackageKeyValid')->willReturn(true);
        $package = new Package($packageManagerStub, $packageKey, $packagePath);
        self::assertEquals($packageKey, $package->getPackageKey());
    }

    public static function invalidPackageKeys(): array
    {
        return [
            ['TYPO3..Flow'],
            ['RobertLemke.Flow. Twitter'],
            ['Schalke*4'],
        ];
    }

    #[DataProvider('invalidPackageKeys')]
    #[Test]
    public function constructRejectsInvalidPackageKeys($packageKey): void
    {
        $this->expectException(InvalidPackageKeyException::class);
        $this->expectExceptionCode(1217959511);

        $packagePath = $this->testRoot . str_replace('\\', '/', $packageKey) . '/';
        mkdir($packagePath, 0777, true);

        $packageManagerStub = self::createStub(PackageManager::class);
        new Package($packageManagerStub, $packageKey, $packagePath);
    }

    #[Test]
    public function aPackageCanBeFlaggedAsProtected(): void
    {
        $packagePath = $this->testRoot . 'Application/Vendor/Dummy/';
        mkdir($packagePath, 0700, true);
        file_put_contents($packagePath . 'composer.json', '{"name": "vendor/dummy", "type": "flow-test"}');

        $packageManagerStub = self::createStub(PackageManager::class);
        $packageManagerStub->method('isPackageKeyValid')->willReturn(true);
        $package = new Package($packageManagerStub, 'Vendor.Dummy', $packagePath);

        self::assertFalse($package->isProtected());
        $package->setProtected(true);
        self::assertTrue($package->isProtected());
    }

    #[Test]
    public function metaDataIsPulledInCorrectlyFromComposerJson(): void
    {
        $package = $this->createPackage(
            'vendor_dummy',
            [
                'name' => 'vendor/dummy',
                'description' => 'title - description',
                'extra' => [
                    'typo3/cms' => [
                        'extension-key' => 'vendor_dummy',
                        'exclude-from-updates' => true,
                        'version' => '1.2.3-alpha4',
                        'Package' => [
                            'providesPackages' => [],
                        ],
                    ],
                ],
            ],
        );
        $metaData = $package->getPackageMetaData();
        self::assertTrue($metaData->isExcludedFromUpdates(), 'Package not excluded from updates');
        self::assertSame('1.2.3-alpha4', $metaData->getVersion(), 'Version does not match');
        self::assertSame(Stability::alpha, $metaData->getStability(), 'Stability does not match');
        self::assertSame('title', $metaData->getTitle(), 'Title does not match');
        self::assertSame('description', $metaData->getDescription(), 'Description does not match');
    }

    #[Test]
    public function versionIsAlsoPulledFromComposerVersionField(): void
    {
        $package = $this->createPackage(
            'vendor_dummy',
            [
                'name' => 'vendor/dummy',
                'version' => '1.2.3-rc3',
                'description' => 'title and description',
                'extra' => [
                    'typo3/cms' => [
                        'extension-key' => 'vendor_dummy',
                        'Package' => [
                            'providesPackages' => [],
                        ],
                    ],
                ],
            ],
        );
        $metaData = $package->getPackageMetaData();
        self::assertSame('1.2.3-RC3', $metaData->getVersion(), 'Version does not match');
        self::assertSame(Stability::RC, $metaData->getStability(), 'Stability does not match');
        self::assertSame('title and description', $metaData->getTitle(), 'Title not pulled from description');
        self::assertNull($metaData->getDescription(), 'Description not null when title is the same');
    }

    #[Test]
    public function versionFromExtraTakesPrecedence(): void
    {
        $package = $this->createPackage(
            'vendor_dummy',
            [
                'name' => 'vendor/dummy',
                'version' => '2.0.0-rc3',
                'extra' => [
                    'typo3/cms' => [
                        'extension-key' => 'vendor_dummy',
                        'version' => '1.2.3',
                        'Package' => [
                            'providesPackages' => [],
                        ],
                    ],
                ],
            ]
        );
        $metaData = $package->getPackageMetaData();
        self::assertSame('1.2.3', $metaData->getVersion(), 'Version does not match');
        self::assertSame(Stability::stable, $metaData->getStability(), 'Stability does not match');
        self::assertNull($metaData->getTitle(), 'Title not null when no description is set');
        self::assertNull($metaData->getDescription(), 'Description not null when no description is set');
    }

    #[Test]
    public function noVersionPopulatesOneZeroWhenBuildingPackageArtifact(): void
    {
        // The version and "providesPackages" are only mandatory in classic mode. When building the
        // Composer package artifact that check is skipped, and a missing version falls back to "1.0.0".
        $package = $this->createPackage(
            'vendor_dummy',
            [
                'name' => 'vendor/dummy',
                'extra' => [
                    'typo3/cms' => [
                        'extension-key' => 'vendor_dummy',
                    ],
                ],
            ],
            true
        );
        $metaData = $package->getPackageMetaData();
        self::assertSame('1.0.0', $metaData->getVersion(), 'Version does not match');
        self::assertSame(Stability::stable, $metaData->getStability(), 'Stability does not match');
        self::assertSame('no-version-set', $metaData->getBuild(), 'Build does not match');
    }

    private function createPackage(string $packageKey, array $manifest, bool $isBuildingPackageArtifact = false): Package
    {
        $packagePath = $this->testRoot . 'Application/Vendor/Dummy/';
        mkdir($packagePath, 0700, true);
        file_put_contents(
            $packagePath . 'composer.json',
            json_encode(
                $manifest,
                JSON_THROW_ON_ERROR,
            )
        );
        $packageManagerMock = $this->getMockBuilder(PackageManager::class)
            ->onlyMethods([
                'setPackageCache',
            ])
            ->disableOriginalConstructor()
            ->getMock();
        return new Package($packageManagerMock, $packageKey, $packagePath, $isBuildingPackageArtifact);
    }
}
