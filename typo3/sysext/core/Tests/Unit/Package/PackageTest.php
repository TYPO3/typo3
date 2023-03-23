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

use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Package\Exception\InvalidPackageKeyException;
use TYPO3\CMS\Core\Package\Exception\InvalidPackagePathException;
use TYPO3\CMS\Core\Package\Package;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Testcase for the package class
 */
class PackageTest extends UnitTestCase
{
    protected string $testRoot;

    protected function setUp(): void
    {
        parent::setUp();
        $this->testRoot = Environment::getVarPath() . '/tests/Packages/';
        GeneralUtility::mkdir_deep($this->testRoot);
        $this->testFilesToDelete[] = $this->testRoot;
    }

    /**
     * @test
     */
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

    /**
     * @test
     * @dataProvider validPackageKeys
     */
    public function constructAcceptsValidPackageKeys($packageKey): void
    {
        $packagePath = $this->testRoot . str_replace('\\', '/', $packageKey) . '/';
        mkdir($packagePath, 0777, true);
        file_put_contents($packagePath . 'composer.json', '{"name": "' . $packageKey . '", "type": "flow-test"}');
        file_put_contents($packagePath . 'ext_emconf.php', '');

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

    /**
     * @test
     * @dataProvider invalidPackageKeys
     */
    public function constructRejectsInvalidPackageKeys($packageKey): void
    {
        $this->expectException(InvalidPackageKeyException::class);
        $this->expectExceptionCode(1217959511);

        $packagePath = $this->testRoot . str_replace('\\', '/', $packageKey) . '/';
        mkdir($packagePath, 0777, true);

        $packageManagerMock = $this->createMock(PackageManager::class);
        new Package($packageManagerMock, $packageKey, $packagePath);
    }

    /**
     * @test
     */
    public function aPackageCanBeFlaggedAsProtected(): void
    {
        $packagePath = $this->testRoot . 'Application/Vendor/Dummy/';
        mkdir($packagePath, 0700, true);
        file_put_contents($packagePath . 'composer.json', '{"name": "vendor/dummy", "type": "flow-test"}');
        file_put_contents($packagePath . 'ext_emconf.php', '');

        $packageManagerMock = $this->createMock(PackageManager::class);
        $packageManagerMock->method('isPackageKeyValid')->willReturn(true);
        $package = new Package($packageManagerMock, 'Vendor.Dummy', $packagePath);

        self::assertFalse($package->isProtected());
        $package->setProtected(true);
        self::assertTrue($package->isProtected());
    }
}
