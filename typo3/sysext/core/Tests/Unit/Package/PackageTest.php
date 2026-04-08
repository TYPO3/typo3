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
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
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

        $packageManagerMock = $this->createMock(PackageManager::class);
        $packageManagerMock->method('isPackageKeyValid')->willReturn(true);
        new Package($packageManagerMock, 'Vendor.TestPackage', './ThisPackageSurelyDoesNotExist');
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

        $packageManagerMock = $this->createMock(PackageManager::class);
        $packageManagerMock->method('isPackageKeyValid')->willReturn(true);
        $package = new Package($packageManagerMock, $packageKey, $packagePath);
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

        $packageManagerMock = $this->createMock(PackageManager::class);
        new Package($packageManagerMock, $packageKey, $packagePath);
    }

    #[Test]
    public function aPackageCanBeFlaggedAsProtected(): void
    {
        $packagePath = $this->testRoot . 'Application/Vendor/Dummy/';
        mkdir($packagePath, 0700, true);
        file_put_contents($packagePath . 'composer.json', '{"name": "vendor/dummy", "type": "flow-test"}');

        $packageManagerMock = $this->createMock(PackageManager::class);
        $packageManagerMock->method('isPackageKeyValid')->willReturn(true);
        $package = new Package($packageManagerMock, 'Vendor.Dummy', $packagePath);

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
    public function noVersionPopulatesOneZero(): void
    {
        $package = $this->createPackage(
            'vendor_dummy',
            [
                'name' => 'vendor/dummy',
                'extra' => [
                    'typo3/cms' => [
                        'extension-key' => 'vendor_dummy',
                    ],
                ],
            ]
        );
        $metaData = $package->getPackageMetaData();
        self::assertSame('1.0', $metaData->getVersion(), 'Version does not match');
        self::assertSame(Stability::stable, $metaData->getStability(), 'Stability does not match');
        self::assertSame('no-version-set', $metaData->getBuild(), 'Build does not match');
    }

    #[Test]
    public function titleAndDescriptionFromEmconfAreUsedAsProvided(): void
    {
        $package = $this->createPackage(
            'vendor_dummy',
            [
                'name' => 'vendor/dummy',
                'title' => 'title',
                'description' => 'description',
                'extra' => [
                    'typo3/cms' => [
                        'extension-key' => 'vendor_dummy',
                    ],
                ],
            ]
        );
        $metaData = $package->getPackageMetaData();
        self::assertSame('title', $metaData->getTitle(), 'Title does not match');
        self::assertSame('description', $metaData->getDescription(), 'Description does not match');
    }

    #[Test]
    #[IgnoreDeprecations]
    public function excludeFromUpdatesFromEmconfAreUsedAsProvided(): void
    {
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
            __DIR__ . '/Fixtures/ext_emconf.php',
        );
        $metaData = $package->getPackageMetaData();
        self::assertSame('EMCONF: Title Test', $metaData->getTitle(), 'Title does not match');
        self::assertSame('EMCONF: Description Test', $metaData->getDescription(), 'Description does not match');
        self::assertTrue($metaData->isExcludedFromUpdates(), 'Exclude from updates not populated');
        self::assertSame(Stability::stable, $metaData->getStability(), 'Stability is taken from state');

        $requires = $metaData->getConstraintsByType($metaData::CONSTRAINT_TYPE_DEPENDS);
        self::assertCount(2, $requires);
        self::assertSame('php', $metaData->getConstraintsByType($metaData::CONSTRAINT_TYPE_DEPENDS)[0]->getValue());
    }

    public static function stateFromEmconfIsConvertedToStabilityOrBuildDataProvider(): \Generator
    {
        yield 'valid stability "stable" in state is converted to stability' => [
            'state' => 'stable',
            'stability' => Stability::stable,
            'build' => null,
        ];
        yield 'valid stability "alpha" in state is converted to stability' => [
            'state' => 'alpha',
            'stability' => Stability::alpha,
            'build' => null,
        ];
        yield 'invalid stability "experimental" in state is converted to stability' => [
            'state' => 'experimental',
            'stability' => Stability::stable,
            'build' => 'experimental',
        ];
        yield 'invalid stability "deprecated" in state is converted to stability' => [
            'state' => 'deprecated',
            'stability' => Stability::stable,
            'build' => 'deprecated',
        ];
    }

    #[Test]
    #[DataProvider('stateFromEmconfIsConvertedToStabilityOrBuildDataProvider')]
    public function stateFromEmconfIsConvertedToStabilityOrBuild(string $state, Stability $stability, ?string $build): void
    {
        $package = $this->createPackage(
            'vendor_dummy',
            [
                'name' => 'vendor/dummy',
                'version' => '1.2.3',
                'state' => $state,
                'extra' => [
                    'typo3/cms' => [
                        'extension-key' => 'vendor_dummy',
                    ],
                ],
            ]
        );
        $metaData = $package->getPackageMetaData();
        self::assertSame($stability, $metaData->getStability(), 'Stability does not match');
        self::assertSame($build, $metaData->getBuild(), 'Build does not match');
    }

    private function createPackage(string $packageKey, array $manifest, ?string $pathToEmConf = null): Package
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
        if ($pathToEmConf !== null) {
            file_put_contents(
                $packagePath . 'ext_emconf.php',
                (string)file_get_contents($pathToEmConf),
            );
        }
        $packageManagerMock = $this->getMockBuilder(PackageManager::class)
            ->onlyMethods([
                'setPackageCache',
            ])
            ->disableOriginalConstructor()
            ->getMock();
        return new Package($packageManagerMock, $packageKey, $packagePath);
    }
}
